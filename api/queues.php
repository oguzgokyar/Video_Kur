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

/**
 * social_queue.json'dan job_id bazında gerçek platform durumlarını oku.
 * social_history.json'u da kontrol eder (tamamlanmış işlemler oraya taşınır).
 * 
 * Döndürülen yapı: [ job_id => [ platform => [ status, post_url, ... ] ] ]
 */
function loadSocialPlatformStatus() {
    global $dataDir;
    $result = [];

    $files = [
        $dataDir . '/social_queue.json',
        $dataDir . '/social_history.json'
    ];

    foreach ($files as $file) {
        if (!file_exists($file)) continue;
        $raw = json_decode(file_get_contents($file), true);
        // social_queue.json key: 'queue', social_history.json key: 'history'
        $items = $raw['queue'] ?? $raw['history'] ?? [];
        foreach ($items as $item) {
            $jobId = $item['job_id'] ?? null;
            if (!$jobId) continue;
            $platformStatus = $item['platform_status'] ?? [];
            // Merge: bir job için birden fazla social_queue girişi olabilir, en güncel olanlar kazansın
            if (!isset($result[$jobId])) {
                $result[$jobId] = [];
            }
            foreach ($platformStatus as $platform => $ps) {
                // Eğer zaten bu platform için daha iyi bir durum varsa override etme
                $existingStatus = $result[$jobId][$platform]['status'] ?? 'pending';
                $newStatus = is_array($ps) ? ($ps['status'] ?? 'pending') : $ps;
                // Öncelik: success > failed > processing > pending
                $priority = ['success' => 4, 'failed' => 3, 'processing' => 2, 'pending' => 1];
                $existingPrio = $priority[$existingStatus] ?? 0;
                $newPrio = $priority[$newStatus] ?? 0;
                if ($newPrio >= $existingPrio) {
                    $result[$jobId][$platform] = is_array($ps) ? $ps : ['status' => $ps];
                }
            }
        }
    }

    return $result;
}

// Config'den varsayılan video ayarlarını al
function getDefaultVideoSettings() {
    global $dataDir;
    $configFile = $dataDir . '/config.json';
    
    $defaults = [
        'dimensionPreset' => 'vertical',
        'videoWidth' => 1080,
        'videoHeight' => 1920,
        'subtitleMode' => 'config',
        'subtitlePreset' => 'classic',
        'customSubtitle' => null
    ];
    
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if ($config && isset($config['subtitleStyle'])) {
            $defaults['configSubtitle'] = $config['subtitleStyle'];
        }
    }
    
    return $defaults;
}

// Kuyruktan video ayarlarını çöz (subtitle style dahil)
function resolveVideoSettings($queue) {
    global $dataDir;
    
    $settings = $queue['video_settings'] ?? getDefaultVideoSettings();
    
    // Varsayılan değerler
    $videoWidth = $settings['videoWidth'] ?? 1080;
    $videoHeight = $settings['videoHeight'] ?? 1920;
    
    // Altyazı stilini çöz
    $subtitleStyle = null;
    $subtitleMode = $settings['subtitleMode'] ?? 'config';
    
    if ($subtitleMode === 'config') {
        // Config'den al
        $configFile = $dataDir . '/config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config && isset($config['subtitleStyle'])) {
                $subtitleStyle = $config['subtitleStyle'];
                $subtitleStyle['preset'] = 'config';
            }
        }
    } elseif ($subtitleMode === 'preset') {
        // Hazır preset
        $presets = [
            'classic' => ['FontName' => 'Arial', 'FontSize' => 24, 'PrimaryColour' => '#FFFFFF', 'OutlineColour' => '#000000', 'Outline' => 2, 'MarginV' => 60, 'Bold' => 1],
            'neon' => ['FontName' => 'Arial', 'FontSize' => 26, 'PrimaryColour' => '#00FF00', 'OutlineColour' => '#000000', 'Outline' => 2, 'MarginV' => 60, 'Bold' => 1],
            'cinematic' => ['FontName' => 'Arial', 'FontSize' => 22, 'PrimaryColour' => '#F5F5DC', 'OutlineColour' => '#2C2C2C', 'Outline' => 1, 'MarginV' => 80, 'Bold' => 0],
            'bold' => ['FontName' => 'Arial', 'FontSize' => 28, 'PrimaryColour' => '#FFD700', 'OutlineColour' => '#000000', 'Outline' => 3, 'MarginV' => 50, 'Bold' => 1],
            'minimal' => ['FontName' => 'Arial', 'FontSize' => 20, 'PrimaryColour' => '#FFFFFF', 'OutlineColour' => '#333333', 'Outline' => 1, 'MarginV' => 70, 'Bold' => 0],
            'news' => ['FontName' => 'Arial', 'FontSize' => 24, 'PrimaryColour' => '#FFFFFF', 'OutlineColour' => '#CC0000', 'Outline' => 2, 'MarginV' => 55, 'Bold' => 1]
        ];
        $presetName = $settings['subtitlePreset'] ?? 'classic';
        $subtitleStyle = $presets[$presetName] ?? $presets['classic'];
        $subtitleStyle['preset'] = $presetName;
    } elseif ($subtitleMode === 'custom') {
        // Özel ayar
        $subtitleStyle = $settings['customSubtitle'] ?? null;
        if ($subtitleStyle) {
            $subtitleStyle['preset'] = 'custom';
        }
    }
    
    // Fallback
    if (!$subtitleStyle) {
        $subtitleStyle = [
            'FontName' => 'Arial',
            'FontSize' => 24,
            'PrimaryColour' => '#FFFFFF',
            'OutlineColour' => '#000000',
            'Outline' => 2,
            'Shadow' => 1,
            'MarginV' => 60,
            'Bold' => 1,
            'preset' => 'classic'
        ];
    }
    
    return [
        'videoWidth' => $videoWidth,
        'videoHeight' => $videoHeight,
        'subtitleStyle' => $subtitleStyle
    ];
}

// GET istekleri
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    $data = loadQueues();
    
    switch ($action) {
        case 'list':
            // Her kuyruk için video detaylarını ekle
            $socialStatus = loadSocialPlatformStatus(); // Gerçek platform durumları
            $queuesWithDetails = [];
            foreach ($data['queues'] as $queue) {
                $videosWithDetails = [];
                foreach ($queue['videos'] ?? [] as $video) {
                    $job = loadJob($video['job_id']);
                    $jobId = $video['job_id'];
                    
                    // Thumbnail URL'sini bul
                    $thumbnailUrl = null;
                    $outputDir = __DIR__ . '/../output/' . $jobId;
                    
                    if (file_exists($outputDir . '/images/hook.png')) {
                        $thumbnailUrl = '/output/' . $jobId . '/images/hook.png';
                    } elseif (file_exists($outputDir . '/thumbnail.png')) {
                        $thumbnailUrl = '/output/' . $jobId . '/thumbnail.png';
                    }

                    // Gerçek platform durumunu social_queue'dan override et
                    $platformStatus = $video['platform_status'] ?? [];
                    if (isset($socialStatus[$jobId])) {
                        foreach ($socialStatus[$jobId] as $platform => $ps) {
                            $platformStatus[$platform] = $ps;
                        }
                    }
                    
                    $videoData = array_merge($video, [
                        'title'           => $job['title'] ?? 'İsimsiz Video',
                        'thumbnailUrl'    => $thumbnailUrl,
                        'job_status'      => $job['status'] ?? 'pending',
                        'platform_status' => $platformStatus,
                    ]);
                    $videosWithDetails[] = $videoData;
                }
                $queue['videos'] = $videosWithDetails;
                $queuesWithDetails[] = $queue;
            }
            
            echo json_encode([
                'success' => true,
                'queues'  => $queuesWithDetails
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
                $socialStatus = loadSocialPlatformStatus(); // Gerçek platform durumları
                $videosWithDetails = [];
                foreach ($queue['videos'] ?? [] as $video) {
                    $job = loadJob($video['job_id']);
                    $jobId = $video['job_id'];
                    
                    // Thumbnail URL'sini bul (hook.png öncelikli - video kapağı)
                    $thumbnailUrl = null;
                    $outputDir = __DIR__ . '/../output/' . $jobId;
                    
                    // Önce hook.png'yi kontrol et (montaj kapağı)
                    if (file_exists($outputDir . '/images/hook.png')) {
                        $thumbnailUrl = '/output/' . $jobId . '/images/hook.png';
                    } elseif (file_exists($outputDir . '/thumbnail.png')) {
                        $thumbnailUrl = '/output/' . $jobId . '/thumbnail.png';
                    } elseif (file_exists($outputDir . '/thumbnail.jpg')) {
                        $thumbnailUrl = '/output/' . $jobId . '/thumbnail.jpg';
                    }
                    
                    // Video URL'sini bul
                    $videoUrl = null;
                    if (file_exists($outputDir . '/final_video.mp4')) {
                        $videoUrl = '/output/' . $jobId . '/final_video.mp4';
                    }

                    // Gerçek platform durumunu social_queue'dan override et
                    $platformStatus = $video['platform_status'] ?? [];
                    if (isset($socialStatus[$jobId])) {
                        foreach ($socialStatus[$jobId] as $platform => $ps) {
                            $platformStatus[$platform] = $ps;
                        }
                    }
                    
                    $videosWithDetails[] = array_merge($video, [
                        'title'           => $job['title'] ?? 'Video',
                        'previewUrl'      => $job['previewUrl'] ?? null,
                        'thumbnailUrl'    => $thumbnailUrl,
                        'videoUrl'        => $videoUrl,
                        'created_at'      => $job['created_at'] ?? null,
                        'queue_name'      => $queue['name'] ?? null,
                        'scheduled_at'    => $video['scheduled_at'] ?? null,
                        'job_status'      => $job['status'] ?? 'pending',
                        'platform_status' => $platformStatus,
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
            $videoSettings = $input['video_settings'] ?? null;
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Kuyruk ismi gerekli']);
                exit;
            }
            
            if (empty($platforms)) {
                echo json_encode(['success' => false, 'error' => 'En az bir platform seçmelisiniz']);
                exit;
            }
            
            // Video ayarları yoksa config'den varsayılanları al
            if (!$videoSettings) {
                $videoSettings = getDefaultVideoSettings();
            }
            
            $queue = [
                'id' => generateId($name),
                'name' => $name,
                'platforms' => $platforms,
                'schedule' => $schedule,
                'video_settings' => $videoSettings,
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
                    if (isset($updates['video_settings'])) $queue['video_settings'] = $updates['video_settings'];
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
        
        case 'pause':
            // Kuyruk durdur
            $queueId = $input['queue_id'] ?? '';
            
            $found = false;
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    $queue['is_active'] = false;
                    $queue['paused_at'] = date('c');
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                saveQueues($data);
                echo json_encode(['success' => true, 'message' => 'Kuyruk durduruldu']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Kuyruk bulunamadı']);
            }
            break;
        
        case 'resume':
            // Kuyruk devam ettir
            $queueId = $input['queue_id'] ?? '';
            
            $found = false;
            foreach ($data['queues'] as &$queue) {
                if ($queue['id'] === $queueId) {
                    $queue['is_active'] = true;
                    $queue['resumed_at'] = date('c');
                    unset($queue['paused_at']);
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                saveQueues($data);
                echo json_encode(['success' => true, 'message' => 'Kuyruk devam ediyor']);
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
            $jobId = $input['job_id'] ?? $input['video_id'] ?? '';
            
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
