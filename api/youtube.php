<?php
/**
 * YouTube API Endpoint
 * Handles YouTube authentication, channel management, and video uploads
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$dataDir = __DIR__ . '/../data';
$youtubeCredsDir = $dataDir . '/youtube_credentials';
$channelsFile = $dataDir . '/youtube_channels.json';
$pythonCmd = 'python';
$pythonDir = __DIR__ . '/../python';

if (!is_dir($youtubeCredsDir)) { mkdir($youtubeCredsDir, 0777, true); }

/**
 * Load channels data
 */
function loadChannels($file) {
    if (!file_exists($file)) {
        return ['channels' => []];
    }
    return json_decode(file_get_contents($file), true) ?: ['channels' => []];
}

/**
 * Save channels data
 */
function saveChannels($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Read JSON input once if POST
$jsonInput = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
}

// Get action from query string, form data, or JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? ($jsonInput['action'] ?? '');

// GET: List channels
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'channels') {
    $data = loadChannels($channelsFile);
    echo json_encode($data);
    exit;
}

// POST: Authenticate (initiate OAuth flow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'auth') {
    // Run Python auth script
    $cmd = sprintf(
        '%s "%s/youtube/auth.py" 2>&1',
        $pythonCmd,
        $pythonDir
    );
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0) {
        // Success - reload channels
        $data = loadChannels($channelsFile);
        echo json_encode([
            'success' => true,
            'channels' => $data['channels'],
            'message' => 'Kimlik doğrulama başarılı'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Kimlik doğrulama başarısız',
            'output' => implode("\n", $output)
        ]);
    }
    exit;
}

// POST: Disconnect channel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'disconnect') {
    $input = $jsonInput ?: json_decode(file_get_contents('php://input'), true);
    $channelId = $input['channel_id'] ?? '';
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'channel_id gerekli']);
        exit;
    }
    
    // Remove token file
    $tokenFile = $youtubeCredsDir . '/' . $channelId . '_token.pickle';
    if (file_exists($tokenFile)) {
        unlink($tokenFile);
    }
    
    // Remove from channels list
    $data = loadChannels($channelsFile);
    $data['channels'] = array_values(array_filter($data['channels'], function($ch) use ($channelId) {
        return $ch['channel_id'] !== $channelId;
    }));
    saveChannels($channelsFile, $data);
    
    echo json_encode(['success' => true, 'message' => 'Kanal bağlantısı kesildi']);
    exit;
}

// POST: Set default channel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set_default') {
    $input = $jsonInput ?: json_decode(file_get_contents('php://input'), true);
    $channelId = $input['channel_id'] ?? '';
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'channel_id gerekli']);
        exit;
    }
    
    $data = loadChannels($channelsFile);
    
    // Set all to non-default, then set target to default
    foreach ($data['channels'] as &$channel) {
        $channel['is_default'] = ($channel['channel_id'] === $channelId);
    }
    
    saveChannels($channelsFile, $data);
    
    echo json_encode(['success' => true, 'message' => 'Varsayılan kanal güncellendi']);
    exit;
}

// POST: Upload video (immediate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    $input = $jsonInput ?: json_decode(file_get_contents('php://input'), true);
    
    $jobId = $input['job_id'] ?? '';
    $videoPath = $input['video_path'] ?? '';
    $channelId = $input['channel_id'] ?? '';
    $metadata = $input['metadata'] ?? [];
    
    if (empty($jobId) || empty($videoPath)) {
        echo json_encode(['success' => false, 'error' => 'job_id ve video_path gerekli']);
        exit;
    }
    
    // Video path relative ise full path yap
    if (strpos($videoPath, 'output/') === 0 || strpos($videoPath, '/output/') === 0) {
        $fullPath = __DIR__ . '/../' . ltrim($videoPath, '/');
    } else {
        $fullPath = __DIR__ . '/../' . $videoPath;
    }
    
    if (!file_exists($fullPath)) {
        echo json_encode(['success' => false, 'error' => 'Video dosyası bulunamadı: ' . $videoPath]);
        exit;
    }
    
    // Thumbnail path - jobId'den belirle
    $thumbnailPath = '';
    $possibleThumbnails = [
        __DIR__ . '/../output/' . $jobId . '/thumbnail.jpg',
        __DIR__ . '/../output/' . $jobId . '/thumbnail.png'
    ];
    foreach ($possibleThumbnails as $path) {
        if (file_exists($path)) {
            $thumbnailPath = $path;
            break;
        }
    }
    
    // Prepare metadata arguments
    $title       = $metadata['title'] ?? '';
    $description = $metadata['description'] ?? '';
    $category    = $metadata['category_id'] ?? '28';
    $privacy     = $metadata['privacy_status'] ?? 'public';
    $tags        = isset($metadata['tags']) ? implode(',', $metadata['tags']) : '';

    // Baslik yoksa veya 'YouTube Short' gibi varsayilansa AI optimize et
    $needsOptimize = (empty($title) || $title === 'YouTube Short' || $title === 'Video');
    if ($needsOptimize) {
        $configFile = $dataDir . '/config.json';
        $config     = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        $geminiKey  = $config['geminiKey'] ?? '';

        // metadata_cli.py ile AI metadata uret
        $metadataCmd = sprintf(
            'cd "%s" && python metadata_cli.py --job-id "%s" --platform youtube --base-dir "%s" 2>NUL',
            $pythonDir,
            $jobId,
            escapeshellarg(dirname($dataDir))
        );
        $metaOutput = [];
        exec($metadataCmd, $metaOutput, $metaCode);

        if ($metaCode === 0 && !empty($metaOutput)) {
            $generatedMeta = json_decode(implode('', $metaOutput), true);
            if (!empty($generatedMeta['title'])) {
                $title       = $generatedMeta['title'];
                $description = $generatedMeta['description'] ?? $description;
                if (!empty($generatedMeta['tags'])) {
                    $tags = implode(',', $generatedMeta['tags']);
                }
            }
        }
    }

    // Hala bos kalirsa son care
    if (empty($title))       { $title = 'YouTube Short'; }
    if (empty($tags))        { $tags  = '#Shorts'; }
    
    // Build command with thumbnail support - use python -m to avoid import errors
    $cmd = sprintf(
        'cd "%s" && %s -m youtube.uploader "%s" "%s" "%s" "%s" "%s" "%s" "%s" 2>&1',
        $pythonDir,
        $pythonCmd,
        $fullPath,
        str_replace('"', '\"', $title),
        str_replace('"', '\"', $description),
        $privacy,
        $category,
        $tags,
        $thumbnailPath
    );
    
    // Execute upload (this will block)
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0) {
        // Parse output to get video ID
        $videoId = null;
        $videoUrl = null;
        $thumbnailUploaded = false;
        
        foreach ($output as $line) {
            if (strpos($line, 'Video ID:') !== false) {
                $videoId = trim(str_replace('Video ID:', '', $line));
            }
            if (strpos($line, 'URL:') !== false) {
                $videoUrl = trim(str_replace('URL:', '', $line));
            }
            if (strpos($line, 'Thumbnail: uploaded') !== false) {
                $thumbnailUploaded = true;
            }
        }
        
        // Update job JSON with upload info and metadata
        $jobFile = $dataDir . '/jobs/' . $jobId . '.json';
        if (file_exists($jobFile)) {
            $job = json_decode(file_get_contents($jobFile), true);
            $job['youtube_upload'] = [
                'status' => 'uploaded',
                'video_id' => $videoId,
                'video_url' => $videoUrl,
                'thumbnail_uploaded' => $thumbnailUploaded,
                'uploaded_at' => gmdate('Y-m-d\TH:i:s\Z'),
                'metadata' => [
                    'title' => $title,
                    'description' => $description,
                    'tags' => $metadata['tags'] ?? [],
                    'category_id' => $category,
                    'privacy_status' => $privacy
                ]
            ];
            file_put_contents($jobFile, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        echo json_encode([
            'success' => true,
            'video_id' => $videoId,
            'video_url' => $videoUrl,
            'thumbnail_uploaded' => $thumbnailUploaded,
            'message' => 'Video başarıyla yüklendi'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Yükleme başarısız: ' . implode("\n", array_slice($output, -5)),
            'output' => $output
        ]);
    }
    exit;
}

// Default response
echo json_encode(['error' => 'Invalid action']);
