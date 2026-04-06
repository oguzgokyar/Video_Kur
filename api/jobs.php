<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$dataDir = __DIR__ . '/../data';
$jobsDir = $dataDir . '/jobs';
$outputDir = __DIR__ . '/../output';
$pythonCmd = 'python';

if (!is_dir($jobsDir)) { mkdir($jobsDir, 0777, true); }
if (!is_dir($outputDir)) { mkdir($outputDir, 0777, true); }

// Helper: Detect resume point for a job
function detectResumePoint($jobId) {
    global $outputDir;
    
    $jobOutputDir = "$outputDir/$jobId";
    
    if (!is_dir($jobOutputDir)) {
        return [
            'resume_from' => 'scraping',
            'completed_stages' => [],
            'missing_files' => ['output directory'],
            'can_resume' => true,
            'message' => 'No output files - will start from beginning',
            'progress' => '0/6',
            'progress_percent' => 0
        ];
    }
    
    $completed = [];
    $resumeFrom = 'scraping';
    $missingFiles = [];
    
    // Check scraping (news.json)
    if (file_exists("$jobOutputDir/news.json")) {
        $completed[] = 'scraping';
        $resumeFrom = 'scripting';
    } else {
        $missingFiles[] = 'news.json';
        return buildResumeResult('scraping', $completed, $missingFiles, true, 
            'Will start from scraping stage');
    }
    
    // Check scripting (script.json)
    if (file_exists("$jobOutputDir/script.json")) {
        $completed[] = 'scripting';
        $resumeFrom = 'imaging';
    } else {
        $missingFiles[] = 'script.json';
        return buildResumeResult('scripting', $completed, $missingFiles, true,
            'Will resume from script generation');
    }
    
    // Check imaging (images/*.png)
    if (is_dir("$jobOutputDir/images")) {
        $images = glob("$jobOutputDir/images/*.png");
        if (count($images) > 0) {
            $completed[] = 'imaging';
            $resumeFrom = 'tts';
        } else {
            $missingFiles[] = 'images/*.png';
            return buildResumeResult('imaging', $completed, $missingFiles, true,
                'Will resume from image generation');
        }
    } else {
        $missingFiles[] = 'images/';
        return buildResumeResult('imaging', $completed, $missingFiles, true,
            'Will resume from image generation');
    }
    
    // Check TTS (audio.mp3 or audio_segments/*.mp3)
    if (file_exists("$jobOutputDir/audio.mp3") || 
        (is_dir("$jobOutputDir/audio_segments") && count(glob("$jobOutputDir/audio_segments/*.mp3")) > 0)) {
        $completed[] = 'tts';
        $resumeFrom = 'subtitling';
    } else {
        $missingFiles[] = 'audio.mp3';
        return buildResumeResult('tts', $completed, $missingFiles, true,
            'Will resume from TTS generation');
    }
    
    // Check subtitling (subtitles.srt)
    if (file_exists("$jobOutputDir/subtitles.srt")) {
        $completed[] = 'subtitling';
        $resumeFrom = 'composing';
    } else {
        $missingFiles[] = 'subtitles.srt';
        return buildResumeResult('subtitling', $completed, $missingFiles, true,
            'Will resume from subtitle generation');
    }
    
    // Check composing (final_video.mp4)
    if (file_exists("$jobOutputDir/final_video.mp4")) {
        $completed[] = 'composing';
        return buildResumeResult('done', $completed, [], false,
            'Job already completed - video exists');
    } else {
        $missingFiles[] = 'final_video.mp4';
        return buildResumeResult('composing', $completed, $missingFiles, true,
            'Will resume from video composition (fastest!)');
    }
}

function buildResumeResult($resumeFrom, $completed, $missing, $canResume, $message) {
    $totalStages = 6;
    $completedCount = count($completed);
    
    return [
        'resume_from' => $resumeFrom,
        'completed_stages' => $completed,
        'missing_files' => $missing,
        'can_resume' => $canResume,
        'message' => $message,
        'progress' => "$completedCount/$totalStages",
        'progress_percent' => intval(($completedCount / $totalStages) * 100)
    ];
}


// POST: Yeni iş oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $url = $input['url'] ?? '';
    $template = $input['template'] ?? 'short_haber';
    $scriptId = $input['scriptId'] ?? '';
    $contentType = trim($input['contentType'] ?? 'haber');
    $videoWidth = intval($input['videoWidth'] ?? 1080);
    $videoHeight = intval($input['videoHeight'] ?? 1920);
    $subtitleStyle = $input['subtitleStyle'] ?? null;

    // Ebat sınırları kontrolü
    $videoWidth = max(360, min(4096, $videoWidth));
    $videoHeight = max(360, min(4096, $videoHeight));

    if (empty($url)) {
        echo json_encode(['error' => 'URL gerekli']);
        exit;
    }

    $jobId = uniqid('job_', true);
    
    // URL'den basit bir başlık oluştur
    $parsedUrl = parse_url($url);
    $path = $parsedUrl['path'] ?? '';
    $titleGuess = basename($path);
    $titleGuess = preg_replace('/[^a-zA-Z0-9\s-]/', ' ', urldecode($titleGuess));
    $titleGuess = ucfirst(trim($titleGuess)) ?: 'Yeni Video';
    
    $jobData = [
        'id' => $jobId,
        'url' => $url,
        'template' => $template,
        'scriptId' => $scriptId,
        'contentType' => $contentType,
        'videoWidth' => $videoWidth,
        'videoHeight' => $videoHeight,
        'subtitleStyle' => $subtitleStyle,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'previewUrl' => '',
        'subtitles' => '',
        'error' => '',
        'title' => $titleGuess
    ];

    file_put_contents("$jobsDir/$jobId.json", json_encode($jobData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $jobOutputDir = "$outputDir/$jobId";
    if (!is_dir($jobOutputDir)) { mkdir($jobOutputDir, 0777, true); }

    // Manuel sistem: Her zaman direkt pipeline başlat
    $pythonScript = __DIR__ . '/../python/pipeline.py';
    $configFile = "$dataDir/config.json";

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = "start /B $pythonCmd \"$pythonScript\" \"$jobId\" \"$url\" \"$template\" \"$configFile\" > \"$jobOutputDir/log.txt\" 2>&1";
    } else {
        $cmd = "$pythonCmd \"$pythonScript\" \"$jobId\" \"$url\" \"$template\" \"$configFile\" > \"$jobOutputDir/log.txt\" 2>&1 &";
    }

    pclose(popen($cmd, 'r'));
    
    echo json_encode(['jobId' => $jobId, 'status' => 'pending', 'message' => 'Video üretimi başlatıldı']);
    exit;
}

// PATCH: Pause/Resume/Retry or Update YouTube Metadata
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $jobId = $input['jobId'] ?? '';
    $action = $input['action'] ?? '';

    if (empty($jobId) || !in_array($action, ['pause', 'resume', 'retry', 'update_youtube_metadata'])) {
        echo json_encode(['error' => 'Geçersiz jobId veya action']);
        exit;
    }

    $jobFile = "$jobsDir/$jobId.json";
    if (!file_exists($jobFile)) {
        echo json_encode(['error' => 'İş bulunamadı']);
        exit;
    }

    $jobData = json_decode(file_get_contents($jobFile), true) ?: [];

    if ($action === 'pause') {
        $jobData['pausedAt'] = $jobData['status'];
        $jobData['status'] = 'paused';
    } elseif ($action === 'resume') {
        // Smart resume: Detect where to continue from
        $resumeInfo = detectResumePoint($jobId);
        
        if (!$resumeInfo['can_resume']) {
            echo json_encode([
                'error' => $resumeInfo['message'],
                'resume_info' => $resumeInfo
            ]);
            exit;
        }
        
        // Update job status
        $jobData['status'] = 'waiting';
        $jobData['resume_from'] = $resumeInfo['resume_from'];
        $jobData['resume_info'] = $resumeInfo;
        $jobData['error'] = '';
        unset($jobData['pausedAt']);
        
        // Save updated job
        file_put_contents($jobFile, json_encode($jobData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Add to production queue with resume flag
        $prodQueueFile = "$dataDir/production_queue.json";
        $prodQueueData = file_exists($prodQueueFile) 
            ? json_decode(file_get_contents($prodQueueFile), true) 
            : ['production_queue' => [], 'current_production' => null, 'max_concurrent' => 1, 'metadata' => []];
        
        $prodItem = [
            'prod_queue_id' => 'prod_' . bin2hex(random_bytes(8)),
            'job_id' => $jobId,
            'queue_id' => $jobData['queue_id'] ?? 'default',
            'status' => 'waiting',
            'priority' => 10,  // Higher priority for resumes
            'is_resume' => true,
            'resume_from' => $resumeInfo['resume_from'],
            'added_at' => date('c'),
            'started_at' => null,
            'completed_at' => null,
            'error' => null
        ];
        
        $prodQueueData['production_queue'][] = $prodItem;
        $prodQueueData['metadata']['last_updated'] = date('c');
        file_put_contents($prodQueueFile, json_encode($prodQueueData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode([
            'success' => true,
            'message' => 'Job queued for resume',
            'resume_info' => $resumeInfo,
            'job' => $jobData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($action === 'retry') {
        // Retry: Reset to waiting and re-add to production queue
        $jobData['status'] = 'waiting';
        $jobData['error'] = '';
        
        // Get queue_id from original job data or use default
        $queue_id = $jobData['queue_id'] ?? 'default';
        
        // Add back to production queue
        $prodQueueData = file_exists("$dataDir/production_queue.json") 
            ? json_decode(file_get_contents("$dataDir/production_queue.json"), true) 
            : ['production_queue' => [], 'current_production' => null, 'max_concurrent' => 1, 'metadata' => []];
        
        $prodItem = [
            'prod_queue_id' => 'prod_' . bin2hex(random_bytes(8)),
            'job_id' => $jobId,
            'queue_id' => $queue_id,
            'status' => 'waiting',
            'priority' => 0,
            'added_at' => date('c'),
            'started_at' => null,
            'completed_at' => null,
            'error' => null
        ];
        
        $prodQueueData['production_queue'][] = $prodItem;
        $prodQueueData['metadata']['last_updated'] = date('c');
        file_put_contents("$dataDir/production_queue.json", json_encode($prodQueueData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } elseif ($action === 'update_youtube_metadata') {
        $metadata = $input['metadata'] ?? [];
        $jobData['youtube_metadata'] = $metadata;
    }

    file_put_contents($jobFile, json_encode($jobData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true, 'status' => $jobData['status']]);
    exit;
}

// GET: İş durumu veya liste
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['list'])) {
        $jobs = [];
        foreach (glob("$jobsDir/*.json") as $file) {
            $job = json_decode(file_get_contents($file), true);
            if ($job && isset($job['id'])) {
                // news.json'dan gerçek başlığı almaya çalış
                $newsFile = "$outputDir/{$job['id']}/news.json";
                if (file_exists($newsFile)) {
                    $news = json_decode(file_get_contents($newsFile), true);
                    if (isset($news['title'])) {
                        $job['title'] = $news['title'];
                    }
                }
                $jobs[] = $job;
            }
        }
        usort($jobs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        echo json_encode(['jobs' => $jobs]);
        exit;
    }

    $jobId = $_GET['jobId'] ?? '';
    if (empty($jobId)) {
        echo json_encode(['error' => 'jobId gerekli']);
        exit;
    }

    $jobFile = "$jobsDir/$jobId.json";
    if (!file_exists($jobFile)) {
        echo json_encode(['error' => 'İş bulunamadı']);
        exit;
    }

    $job = json_decode(file_get_contents($jobFile), true);
    
    // news.json'dan gerçek başlığı al
    $newsFile = "$outputDir/$jobId/news.json";
    if (file_exists($newsFile)) {
        $news = json_decode(file_get_contents($newsFile), true);
        if (isset($news['title'])) {
            $job['title'] = $news['title'];
        }
    }

    echo json_encode($job);
    exit;
}

// DELETE: İşi sil
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $jobId = $input['jobId'] ?? '';

    if (empty($jobId)) {
        echo json_encode(['error' => 'jobId gerekli']);
        exit;
    }

    $jobFile = "$jobsDir/$jobId.json";
    if (!file_exists($jobFile)) {
        echo json_encode(['error' => 'İş bulunamadı']);
        exit;
    }

    // Output klasörünü sil (tüm içeriğiyle)
    $jobOutputDir = "$outputDir/$jobId";
    if (is_dir($jobOutputDir)) {
        function deleteDirectory($dir) {
            if (!is_dir($dir)) return false;
            $items = array_diff(scandir($dir), ['.', '..']);
            foreach ($items as $item) {
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                is_dir($path) ? deleteDirectory($path) : unlink($path);
            }
            return rmdir($dir);
        }
        deleteDirectory($jobOutputDir);
    }

    // Job meta dosyasını sil
    unlink($jobFile);

    echo json_encode(['success' => true, 'message' => 'İş silindi']);
    exit;
}
