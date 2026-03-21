<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataDir = __DIR__ . '/../data';
$queuesFile = $dataDir . '/queues.json';
$jobsDir = $dataDir . '/jobs';

// Kuyruk verilerini yükle
function loadQueues() {
    global $queuesFile;
    if (!file_exists($queuesFile)) {
        return ['queues' => []];
    }
    $content = file_get_contents($queuesFile);
    return json_decode($content, true) ?: ['queues' => []];
}

// Kuyruk verilerini kaydet
function saveQueues($data) {
    global $queuesFile;
    file_put_contents($queuesFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Job bilgisini yükle
function loadJob($jobId) {
    global $jobsDir;
    // Önce klasör yapısını dene
    $jobFile = $jobsDir . '/' . $jobId . '/job.json';
    if (file_exists($jobFile)) {
        return json_decode(file_get_contents($jobFile), true);
    }
    // Sonra düz dosya yapısını dene
    $jobFile = $jobsDir . '/' . $jobId . '.json';
    if (file_exists($jobFile)) {
        return json_decode(file_get_contents($jobFile), true);
    }
    return null;
}

// Job bilgisini kaydet
function saveJob($jobId, $data) {
    global $jobsDir;
    // Önce klasör yapısını dene
    $jobFile = $jobsDir . '/' . $jobId . '/job.json';
    if (file_exists(dirname($jobFile))) {
        file_put_contents($jobFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return;
    }
    // Sonra düz dosya yapısını dene
    $jobFile = $jobsDir . '/' . $jobId . '.json';
    file_put_contents($jobFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Benzersiz ID oluştur
function generateId($name) {
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');
    return $slug . '-' . substr(uniqid(), -6);
}

// GET istekleri
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    $data = loadQueues();
    
    switch ($action) {
        case 'list':
            echo json_encode([
                'success' => true,
                'queues' => $data['queues']
            ]);
            break;
            
        case 'get':
            $queueId = $_GET['id'] ?? '';
            $queue = null;
            foreach ($data['queues'] as $q) {
                if ($q['id'] === $queueId) {
                    $queue = $q;
                    break;
                }
            }
            if ($queue) {
                // Video detaylarını ekle
                $videosWithDetails = [];
                foreach ($queue['videos'] ?? [] as $video) {
                    $job = loadJob($video['job_id']);
                    $videosWithDetails[] = array_merge($video, [
                        'title' => $job['title'] ?? 'Video',
                        'previewUrl' => $job['previewUrl'] ?? null,
                        'created_at' => $job['created_at'] ?? null
                    ]);
                }
                $queue['videos'] = $videosWithDetails;
                echo json_encode(['success' => true, 'queue' => $queue]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Kuyruk bulunamadı']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Geçersiz action']);
    }
    exit;
}

// POST istekleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $data = loadQueues();
    
    switch ($action) {
        case 'create':
            $name = trim($input['name'] ?? '');
            $platforms = $input['platforms'] ?? [];
            $schedule = $input['schedule'] ?? ['type' => 'interval', 'interval_hours' => 2];
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Kuyruk ismi gerekli']);
                exit;
            }
            
            if (empty($platforms)) {
                echo json_encode(['success' => false, 'error' => 'En az bir platform seçmelisiniz']);
                exit;
            }
            
            $queue = [
                'id' => generateId($name),
                'name' => $name,
                'platforms' => $platforms,
                'schedule' => $schedule,
                'videos' => [],
                'created_at' => date('c'),
                'last_publish' => null,
                'is_active' => true
            ];
            
            $data['queues'][] = $queue;
            saveQueues($data);
            
            echo json_encode(['success' => true, 'queue' => $queue]);
            break;
            
        case 'update':
            $queueId = $input['queue_id'] ?? '';
            $updates = $input['updates'] ?? [];
            
            $found = false;
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    if (isset($updates['name'])) $queue['name'] = $updates['name'];
                    if (isset($updates['platforms'])) $queue['platforms'] = $updates['platforms'];
                    if (isset($updates['schedule'])) $queue['schedule'] = $updates['schedule'];
                    if (isset($updates['is_active'])) $queue['is_active'] = $updates['is_active'];
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                saveQueues($data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Kuyruk bulunamadı']);
            }
            break;
            
        case 'delete':
            $queueId = $input['queue_id'] ?? '';
            
            $newQueues = [];
            $found = false;
            foreach ($data['queues'] as $queue) {
                if ($queue['id'] === $queueId) {
                    $found = true;
                    // Videoların queue_status'unu temizle
                    foreach ($queue['videos'] ?? [] as $video) {
                        $job = loadJob($video['job_id']);
                        if ($job) {
                            unset($job['queue_status']);
                            saveJob($video['job_id'], $job);
                        }
                    }
                } else {
                    $newQueues[] = $queue;
                }
            }
            
            if ($found) {
                $data['queues'] = $newQueues;
                saveQueues($data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Kuyruk bulunamadı']);
            }
            break;
            
        case 'add_video':
            $queueId = $input['queue_id'] ?? '';
            $jobId = $input['job_id'] ?? '';
            
            if (empty($queueId) || empty($jobId)) {
                echo json_encode(['success' => false, 'error' => 'queue_id ve job_id gerekli']);
                exit;
            }
            
            // Job var mı kontrol et
            $job = loadJob($jobId);
            if (!$job) {
                echo json_encode(['success' => false, 'error' => 'Video bulunamadı']);
                exit;
            }
            
            $found = false;
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    // Video zaten kuyrukta mı kontrol et
                    foreach ($queue['videos'] ?? [] as $v) {
                        if ($v['job_id'] === $jobId) {
                            echo json_encode(['success' => false, 'error' => 'Video zaten bu kuyrukta']);
                            exit;
                        }
                    }
                    
                    // Platform durumlarını oluştur
                    $platformStatus = [];
                    foreach ($queue['platforms'] as $platform) {
                        $platformStatus[$platform] = 'pending';
                    }
                    
                    $videoEntry = [
                        'job_id' => $jobId,
                        'added_at' => date('c'),
                        'status' => 'queued',
                        'platform_status' => $platformStatus,
                        'position' => count($queue['videos'] ?? [])
                    ];
                    
                    $queue['videos'][] = $videoEntry;
                    $found = true;
                    
                    // Job'a queue_status ekle
                    $job['queue_status'] = [
                        'queue_id' => $queueId,
                        'queue_name' => $queue['name'],
                        'status' => 'queued',
                        'added_at' => date('c')
                    ];
                    saveJob($jobId, $job);
                    
                    break;
                }
            }
            
            if ($found) {
                saveQueues($data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Kuyruk bulunamadı']);
            }
            break;
            
        case 'remove_video':
            $queueId = $input['queue_id'] ?? '';
            $jobId = $input['job_id'] ?? '';
            
            $found = false;
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    $newVideos = [];
                    foreach ($queue['videos'] ?? [] as $video) {
                        if ($video['job_id'] === $jobId) {
                            $found = true;
                            // Job'dan queue_status'u kaldır
                            $job = loadJob($jobId);
                            if ($job) {
                                unset($job['queue_status']);
                                saveJob($jobId, $job);
                            }
                        } else {
                            $newVideos[] = $video;
                        }
                    }
                    $queue['videos'] = $newVideos;
                    break;
                }
            }
            
            if ($found) {
                saveQueues($data);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Video kuyrukta bulunamadı']);
            }
            break;
            
        case 'reorder':
            $queueId = $input['queue_id'] ?? '';
            $videoOrder = $input['video_order'] ?? []; // job_id listesi
            
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    $newVideos = [];
                    foreach ($videoOrder as $position => $jobId) {
                        foreach ($queue['videos'] as $video) {
                            if ($video['job_id'] === $jobId) {
                                $video['position'] = $position;
                                $newVideos[] = $video;
                                break;
                            }
                        }
                    }
                    $queue['videos'] = $newVideos;
                    break;
                }
            }
            
            saveQueues($data);
            echo json_encode(['success' => true]);
            break;
            
        case 'mark_published':
            $queueId = $input['queue_id'] ?? '';
            $jobId = $input['job_id'] ?? '';
            $platform = $input['platform'] ?? '';
            $postUrl = $input['post_url'] ?? null;
            
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    foreach ($queue['videos'] as &$video) {
                        if ($video['job_id'] === $jobId) {
                            $video['platform_status'][$platform] = 'published';
                            if ($postUrl) {
                                $video['post_urls'][$platform] = $postUrl;
                            }
                            
                            // Tüm platformlar yayınlandı mı kontrol et
                            $allPublished = true;
                            foreach ($video['platform_status'] as $status) {
                                if ($status !== 'published') {
                                    $allPublished = false;
                                    break;
                                }
                            }
                            if ($allPublished) {
                                $video['status'] = 'published';
                            }
                            
                            // Job'u güncelle
                            $job = loadJob($jobId);
                            if ($job) {
                                $job['queue_status']['status'] = $video['status'];
                                $job['queue_status']['platform_status'] = $video['platform_status'];
                                saveJob($jobId, $job);
                            }
                            
                            break;
                        }
                    }
                    $queue['last_publish'] = date('c');
                    break;
                }
            }
            
            saveQueues($data);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Geçersiz action: ' . $action]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Desteklenmeyen HTTP metodu']);
