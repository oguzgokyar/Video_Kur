<?php
/**
 * Scheduler API Endpoint
 * Handles upload scheduling, queue management, and history
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$dataDir = __DIR__ . '/../data';
$queueFile = $dataDir . '/upload_queue.json';
$historyFile = $dataDir . '/upload_history.json';
$jobsDir = $dataDir . '/jobs';
$pythonCmd = 'python';
$pythonDir = __DIR__ . '/../python';

/**
 * Load JSON file
 */
function loadJson($file, $default = []) {
    if (!file_exists($file)) {
        return $default;
    }
    return json_decode(file_get_contents($file), true) ?: $default;
}

/**
 * Save JSON file
 */
function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET: List queue
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['queue'])) {
    $data = loadJson($queueFile, ['queue' => []]);
    echo json_encode($data);
    exit;
}

// GET: List history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['history'])) {
    $data = loadJson($historyFile, ['history' => []]);
    $limit = intval($_GET['limit'] ?? 50);
    $data['history'] = array_slice($data['history'], 0, $limit);
    echo json_encode($data);
    exit;
}

// POST: Schedule upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'schedule') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $jobId = $input['job_id'] ?? '';
    $videoPath = $input['video_path'] ?? '';
    $channelId = $input['channel_id'] ?? '';
    $scheduledTime = $input['scheduled_time'] ?? '';
    $metadata = $input['metadata'] ?? [];
    $priority = intval($input['priority'] ?? 0);
    
    if (empty($jobId) || empty($videoPath) || empty($scheduledTime)) {
        echo json_encode(['success' => false, 'error' => 'job_id, video_path ve scheduled_time gerekli']);
        exit;
    }
    
    // Generate queue ID
    $queueId = 'upload_' . uniqid('', true);
    
    // Add to queue
    $queue = loadJson($queueFile, ['queue' => []]);
    
    $item = [
        'queue_id' => $queueId,
        'job_id' => $jobId,
        'video_path' => $videoPath,
        'channel_id' => $channelId,
        'scheduled_time' => $scheduledTime,
        'status' => 'pending',
        'priority' => $priority,
        'metadata' => $metadata,
        'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'retry_count' => 0,
        'last_error' => null
    ];
    
    $queue['queue'][] = $item;
    saveJson($queueFile, $queue);
    
    // Update job JSON
    $jobFile = $jobsDir . '/' . $jobId . '.json';
    if (file_exists($jobFile)) {
        $job = json_decode(file_get_contents($jobFile), true);
        $job['youtube_upload'] = [
            'status' => 'scheduled',
            'queue_id' => $queueId,
            'scheduled_time' => $scheduledTime,
            'video_id' => null,
            'video_url' => null
        ];
        file_put_contents($jobFile, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    echo json_encode([
        'success' => true,
        'queue_id' => $queueId,
        'message' => 'Zamanlama eklendi'
    ]);
    exit;
}

// POST: Auto schedule (called after video generation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'auto_schedule') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $jobId = $input['job_id'] ?? '';
    $videoPath = $input['video_path'] ?? '';
    $channelId = $input['channel_id'] ?? '';
    $metadata = $input['metadata'] ?? [];
    
    if (empty($jobId) || empty($videoPath)) {
        echo json_encode(['success' => false, 'error' => 'job_id ve video_path gerekli']);
        exit;
    }
    
    // Use Python timing optimizer to get next optimal time
    $cmd = sprintf(
        '%s -c "from scheduler.timing_optimizer import TimingOptimizer; from datetime import datetime, timezone; opt = TimingOptimizer(3); t = opt.get_next_optimal_time(strategy=\'smart\'); print(t.isoformat())" 2>&1',
        $pythonCmd
    );
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode !== 0 || empty($output)) {
        // Fallback: schedule for 2 hours from now
        $scheduledTime = gmdate('Y-m-d\TH:i:s\Z', time() + 7200);
    } else {
        $scheduledTime = trim($output[0]);
    }
    
    // Generate queue ID
    $queueId = 'upload_' . uniqid('', true);
    
    // Add to queue
    $queue = loadJson($queueFile, ['queue' => []]);
    
    $item = [
        'queue_id' => $queueId,
        'job_id' => $jobId,
        'video_path' => $videoPath,
        'channel_id' => $channelId,
        'scheduled_time' => $scheduledTime,
        'status' => 'pending',
        'priority' => 0,
        'metadata' => $metadata,
        'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'retry_count' => 0,
        'last_error' => null
    ];
    
    $queue['queue'][] = $item;
    saveJson($queueFile, $queue);
    
    // Update job JSON
    $jobFile = $jobsDir . '/' . $jobId . '.json';
    if (file_exists($jobFile)) {
        $job = json_decode(file_get_contents($jobFile), true);
        $job['youtube_upload'] = [
            'status' => 'scheduled',
            'queue_id' => $queueId,
            'scheduled_time' => $scheduledTime,
            'video_id' => null,
            'video_url' => null
        ];
        file_put_contents($jobFile, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    echo json_encode([
        'success' => true,
        'queue_id' => $queueId,
        'scheduled_time' => $scheduledTime,
        'message' => 'Otomatik zamanlama yapıldı'
    ]);
    exit;
}

// PATCH: Update schedule
if ($_SERVER['REQUEST_METHOD'] === 'PATCH' && $action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $queueId = $input['queue_id'] ?? '';
    $updates = $input['updates'] ?? [];
    
    if (empty($queueId)) {
        echo json_encode(['success' => false, 'error' => 'queue_id gerekli']);
        exit;
    }
    
    $queue = loadJson($queueFile, ['queue' => []]);
    $found = false;
    
    foreach ($queue['queue'] as &$item) {
        if ($item['queue_id'] === $queueId) {
            $item = array_merge($item, $updates);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        saveJson($queueFile, $queue);
        echo json_encode(['success' => true, 'message' => 'Zamanlama güncellendi']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Queue item bulunamadı']);
    }
    exit;
}

// DELETE: Cancel schedule
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'cancel') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $queueId = $input['queue_id'] ?? '';
    
    if (empty($queueId)) {
        echo json_encode(['success' => false, 'error' => 'queue_id gerekli']);
        exit;
    }
    
    $queue = loadJson($queueFile, ['queue' => []]);
    $originalCount = count($queue['queue']);
    
    $queue['queue'] = array_values(array_filter($queue['queue'], function($item) use ($queueId) {
        return $item['queue_id'] !== $queueId;
    }));
    
    if (count($queue['queue']) < $originalCount) {
        saveJson($queueFile, $queue);
        echo json_encode(['success' => true, 'message' => 'Zamanlama iptal edildi']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Queue item bulunamadı']);
    }
    exit;
}

// Default response
echo json_encode(['error' => 'Invalid action']);
