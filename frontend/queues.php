<?php $page_title = 'Kuyruklar - Video Otomasyon'; $active_page = 'queues'; ?>
<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === '1' }" :class="{ 'dark': darkMode }">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    @keyframes slide-in-right { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    .anim-fade { animation: fade-in .3s ease-out both; }
    .anim-slide-right { animation: slide-in-right .3s ease-out both; }
    .pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
    
    /* Drag & Drop */
    .sortable-ghost { opacity: 0.4; background: #dbeafe !important; }
    .sortable-drag { opacity: 0.9; transform: scale(1.02); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    
    /* Platform Preview Frames */
    .phone-frame { 
      background: linear-gradient(145deg, #1a1a1a 0%, #0a0a0a 100%);
      border-radius: 32px;
      padding: 8px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    }
    .phone-screen {
      background: #000;
      border-radius: 24px;
      overflow: hidden;
      position: relative;
    }
    .phone-notch {
      position: absolute;
      top: 8px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 24px;
      background: #000;
      border-radius: 12px;
      z-index: 10;
    }
    
    /* Tabs */
    .tab-active { 
      border-bottom: 3px solid #6366f1;
      color: #6366f1;
      font-weight: 600;
    }
    
    /* Custom scrollbar */
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .dark .custom-scroll::-webkit-scrollbar-track { background: #1e293b; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background: #475569; }
  </style>
  
  <!-- Sortable.js for drag & drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors duration-300" x-data="queuesApp()">
  <!-- Sidebar -->
  <?php include __DIR__ . '/components/_sidebar.php'; ?>

  <div class="md:ml-56 flex flex-col min-h-screen">
    <!-- Header -->
    <?php include __DIR__ . '/components/_header.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-4 lg:p-6">
      <div class="max-w-[1800px] mx-auto">
        
        <!-- Page Header with Tabs -->
        <div class="mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📦 Kuyruk Yönetimi</h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">Video paylaşım kuyruklarınızı yönetin</p>
            </div>
            <button @click="openCreateModal()" 
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-lg shadow-indigo-500/30 transition flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Yeni Kuyruk
            </button>
          </div>
          
          <!-- Queue Tabs -->
          <div class="flex items-center gap-1 border-b border-gray-200 dark:border-slate-700 overflow-x-auto pb-px">
            <button @click="activeTab = 'all'; filterVideos()"
                    :class="activeTab === 'all' ? 'tab-active' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                    class="px-4 py-3 text-sm font-medium whitespace-nowrap transition">
              📋 Tüm Kuyruklar
              <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-slate-700" x-text="queues.length"></span>
            </button>
            <template x-for="queue in queues" :key="queue.id">
              <button @click="selectQueue(queue)"
                      :class="selectedQueue?.id === queue.id ? 'tab-active' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                      class="px-4 py-3 text-sm font-medium whitespace-nowrap transition flex items-center gap-2">
                <span x-text="queue.name"></span>
                <span class="px-2 py-0.5 text-xs rounded-full"
                      :class="queue.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400'"
                      x-text="(queue.videos?.length || 0) + ' video'"></span>
              </button>
            </template>
          </div>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
          <div class="flex items-center justify-center py-20">
            <div class="text-center">
              <svg class="w-12 h-12 mx-auto text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <p class="mt-4 text-gray-600 dark:text-gray-400">Kuyruklar yükleniyor...</p>
            </div>
          </div>
        </template>

        <!-- Main Two-Column Layout -->
        <template x-if="!loading">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left Column: Video List -->
            <div class="lg:col-span-5 xl:col-span-4">
              <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                
                <!-- List Header -->
                <div class="p-4 border-b border-gray-100 dark:border-slate-700">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <select x-model="filterStatus" @change="filterVideos()"
                              class="text-sm border border-gray-200 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white">
                        <option value="all">Tümü</option>
                        <option value="pending">⏳ Bekleyen</option>
                        <option value="published">✅ Paylaşılan</option>
                        <option value="failed">❌ Başarısız</option>
                      </select>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="filteredVideos.length + ' video'"></span>
                  </div>
                </div>
                
                <!-- Video List -->
                <div class="max-h-[calc(100vh-320px)] overflow-y-auto custom-scroll" id="videoList">
                  <template x-if="filteredVideos.length === 0">
                    <div class="p-8 text-center">
                      <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                      </div>
                      <p class="text-gray-500 dark:text-gray-400">Henüz video yok</p>
                      <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Dashboard'dan video ekleyebilirsiniz</p>
                    </div>
                  </template>
                  
                  <template x-for="(video, index) in filteredVideos" :key="video.job_id">
                    <div @click="selectVideo(video)"
                         :class="selectedVideo?.job_id === video.job_id ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'hover:bg-gray-50 dark:hover:bg-slate-700/50 border-l-4 border-transparent'"
                         class="p-4 cursor-pointer transition-all group"
                         :data-id="video.job_id"
                         draggable="true">
                      <div class="flex gap-3">
                        <!-- Thumbnail -->
                        <div class="w-20 h-14 rounded-lg overflow-hidden bg-gray-200 dark:bg-slate-700 flex-shrink-0 relative">
                          <template x-if="video.thumbnailUrl">
                            <img :src="video.thumbnailUrl" class="w-full h-full object-cover">
                          </template>
                          <template x-if="!video.thumbnailUrl">
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </div>
                          </template>
                          <!-- Position Badge -->
                          <div class="absolute top-1 left-1 w-5 h-5 rounded bg-black/60 text-white text-xs flex items-center justify-center font-medium" x-text="index + 1"></div>
                        </div>
                        
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                          <h4 class="font-medium text-gray-900 dark:text-white text-sm truncate" x-text="video.title || 'Video'"></h4>
                          
                          <!-- Platform Status Pills -->
                          <div class="flex flex-wrap gap-1 mt-1.5">
                            <template x-for="(status, platform) in video.platform_status" :key="platform">
                              <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs"
                                    :class="{
                                      'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': status === 'published',
                                      'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': status === 'pending',
                                      'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': status === 'failed',
                                      'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': status === 'publishing'
                                    }">
                                <span x-text="getPlatformIcon(platform)"></span>
                                <span class="pulse-dot w-1.5 h-1.5 rounded-full" x-show="status === 'publishing'"
                                      :class="status === 'publishing' ? 'bg-blue-500' : ''"></span>
                              </span>
                            </template>
                          </div>
                          
                          <!-- Scheduled Time -->
                          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="video.scheduled_at">
                            🕐 <span x-text="formatDate(video.scheduled_at)"></span>
                          </p>
                        </div>
                        
                        <!-- Drag Handle -->
                        <div class="opacity-0 group-hover:opacity-100 transition cursor-grab text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/></svg>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>

            <!-- Right Column: Video Details & Preview -->
            <div class="lg:col-span-7 xl:col-span-8">
              <template x-if="!selectedVideo">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 p-12 text-center">
                  <div class="w-20 h-20 mx-auto bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Video Seçin</h3>
                  <p class="text-gray-500 dark:text-gray-400">Detayları görüntülemek için sol listeden bir video seçin</p>
                </div>
              </template>
              
              <template x-if="selectedVideo">
                <div class="space-y-6 anim-slide-right">
                  
                  <!-- Video Details Card -->
                  <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <!-- Header -->
                    <div class="p-5 border-b border-gray-100 dark:border-slate-700">
                      <div class="flex items-start justify-between">
                        <div>
                          <h2 class="text-xl font-bold text-gray-900 dark:text-white" x-text="selectedVideo.title || 'Video Detayları'"></h2>
                          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Kuyruk: <span class="font-medium text-indigo-600 dark:text-indigo-400" x-text="selectedVideo.queue_name || selectedQueue?.name"></span>
                          </p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                          <button @click="openMoveModal(selectedVideo)" 
                                  class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition"
                                  title="Taşı">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                          </button>
                          <button @click="removeFromQueue(selectedVideo.job_id)" 
                                  class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                  title="Kuyruktan Çıkar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Platform Status Grid -->
                    <div class="p-5">
                      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Platform Durumları</h3>
                      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <template x-for="(status, platform) in selectedVideo.platform_status" :key="platform">
                          <div class="relative rounded-xl p-4 border-2 transition-all"
                               :class="{
                                 'border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-700': status === 'published',
                                 'border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-700': status === 'pending',
                                 'border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700': status === 'failed',
                                 'border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700': status === 'publishing'
                               }">
                            <div class="flex items-center gap-3">
                              <span class="text-2xl" x-text="getPlatformIcon(platform)"></span>
                              <div>
                                <p class="font-semibold text-gray-900 dark:text-white capitalize" x-text="platform"></p>
                                <p class="text-xs"
                                   :class="{
                                     'text-green-600 dark:text-green-400': status === 'published',
                                     'text-yellow-600 dark:text-yellow-400': status === 'pending',
                                     'text-red-600 dark:text-red-400': status === 'failed',
                                     'text-blue-600 dark:text-blue-400': status === 'publishing'
                                   }"
                                   x-text="getStatusText(status)"></p>
                              </div>
                            </div>
                            
                            <!-- Link to published post -->
                            <template x-if="status === 'published' && selectedVideo.post_urls?.[platform]">
                              <a :href="selectedVideo.post_urls[platform]" target="_blank"
                                 class="absolute top-2 right-2 p-1.5 bg-white dark:bg-slate-800 rounded-lg shadow-sm hover:shadow-md transition"
                                 title="Paylaşımı Görüntüle">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                              </a>
                            </template>
                            
                            <!-- Live status indicator -->
                            <template x-if="status === 'publishing'">
                              <div class="absolute top-2 right-2">
                                <span class="flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                                </span>
                              </div>
                            </template>
                          </div>
                        </template>
                      </div>
                    </div>
                    
                    <!-- Schedule Info -->
                    <div class="px-5 pb-5">
                      <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl flex items-center justify-center">
                          <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                          <p class="text-sm text-gray-500 dark:text-gray-400">Zamanlanmış Paylaşım</p>
                          <p class="font-semibold text-gray-900 dark:text-white" x-text="selectedVideo.scheduled_at ? formatDate(selectedVideo.scheduled_at) : 'Belirlenmedi'"></p>
                        </div>
                        <button @click="openScheduleModal(selectedVideo)" 
                                class="ml-auto px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                          Düzenle
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Platform Previews -->
                  <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-slate-700">
                      <h3 class="font-semibold text-gray-900 dark:text-white">📱 Platform Önizlemeleri</h3>
                      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Videunuz platformlarda nasıl görünecek</p>
                    </div>
                    
                    <!-- Preview Tabs -->
                    <div class="flex gap-2 p-4 border-b border-gray-100 dark:border-slate-700 overflow-x-auto">
                      <template x-for="(status, platform) in selectedVideo.platform_status" :key="'preview-' + platform">
                        <button @click="previewPlatform = platform"
                                :class="previewPlatform === platform ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                          <span x-text="getPlatformIcon(platform)"></span>
                          <span class="capitalize" x-text="platform"></span>
                        </button>
                      </template>
                    </div>
                    
                    <!-- Preview Content -->
                    <div class="p-6">
                      <div class="flex justify-center">
                        <!-- Phone Frame -->
                        <div class="phone-frame w-72">
                          <div class="phone-screen aspect-[9/16] relative">
                            <div class="phone-notch"></div>
                            
                            <!-- Video Preview -->
                            <div class="absolute inset-0 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900">
                              <template x-if="selectedVideo.thumbnailUrl">
                                <img :src="selectedVideo.thumbnailUrl" class="w-full h-full object-cover opacity-80">
                              </template>
                              
                              <!-- Platform-specific UI Overlay -->
                              <!-- TikTok Style -->
                              <template x-if="previewPlatform === 'tiktok'">
                                <div class="absolute inset-0 flex">
                                  <!-- Right sidebar -->
                                  <div class="absolute right-3 bottom-20 flex flex-col items-center gap-5">
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">❤️</div>
                                      <span class="text-white text-xs mt-1">12.5K</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">💬</div>
                                      <span class="text-white text-xs mt-1">234</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">🔖</div>
                                      <span class="text-white text-xs mt-1">1.2K</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">↗️</div>
                                      <span class="text-white text-xs mt-1">Share</span>
                                    </div>
                                  </div>
                                  <!-- Bottom info -->
                                  <div class="absolute bottom-4 left-3 right-16">
                                    <p class="text-white font-semibold text-sm">@kullanici</p>
                                    <p class="text-white/90 text-xs mt-1 line-clamp-2" x-text="selectedVideo.title"></p>
                                    <p class="text-white/70 text-xs mt-2">#fyp #viral #kesfet</p>
                                  </div>
                                </div>
                              </template>
                              
                              <!-- YouTube Style -->
                              <template x-if="previewPlatform === 'youtube'">
                                <div class="absolute inset-0 flex flex-col">
                                  <!-- Top bar -->
                                  <div class="p-3 flex items-center justify-between">
                                    <span class="text-white text-lg font-bold">Shorts</span>
                                    <div class="flex gap-3">
                                      <span class="text-white">🔍</span>
                                      <span class="text-white">⋮</span>
                                    </div>
                                  </div>
                                  <!-- Right sidebar -->
                                  <div class="absolute right-3 bottom-20 flex flex-col items-center gap-4">
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">👍</div>
                                      <span class="text-white text-xs mt-1">15K</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">👎</div>
                                      <span class="text-white text-xs mt-1">Dislike</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">💬</div>
                                      <span class="text-white text-xs mt-1">456</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">↗️</div>
                                      <span class="text-white text-xs mt-1">Share</span>
                                    </div>
                                  </div>
                                  <!-- Bottom info -->
                                  <div class="absolute bottom-4 left-3 right-16">
                                    <div class="flex items-center gap-2 mb-2">
                                      <div class="w-8 h-8 bg-red-500 rounded-full"></div>
                                      <span class="text-white text-sm font-medium">@VideoKur</span>
                                      <button class="px-3 py-1 bg-white text-black text-xs rounded-full font-semibold">Abone Ol</button>
                                    </div>
                                    <p class="text-white/90 text-xs line-clamp-2" x-text="selectedVideo.title"></p>
                                  </div>
                                </div>
                              </template>
                              
                              <!-- Instagram Style -->
                              <template x-if="previewPlatform === 'instagram'">
                                <div class="absolute inset-0 flex flex-col">
                                  <!-- Top bar -->
                                  <div class="p-3 flex items-center justify-between bg-gradient-to-b from-black/50 to-transparent">
                                    <span class="text-white font-semibold">Reels</span>
                                    <span class="text-white">📷</span>
                                  </div>
                                  <!-- Right sidebar -->
                                  <div class="absolute right-3 bottom-20 flex flex-col items-center gap-4">
                                    <div class="text-center">
                                      <div class="w-9 h-9 flex items-center justify-center">❤️</div>
                                      <span class="text-white text-xs">8.2K</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-9 h-9 flex items-center justify-center">💬</div>
                                      <span class="text-white text-xs">123</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-9 h-9 flex items-center justify-center">📤</div>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-9 h-9 flex items-center justify-center">⋯</div>
                                    </div>
                                  </div>
                                  <!-- Bottom info -->
                                  <div class="absolute bottom-4 left-3 right-14">
                                    <div class="flex items-center gap-2 mb-2">
                                      <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full p-0.5">
                                        <div class="w-full h-full bg-gray-800 rounded-full"></div>
                                      </div>
                                      <span class="text-white text-sm font-semibold">videokur</span>
                                      <button class="px-3 py-1 border border-white text-white text-xs rounded-lg">Takip Et</button>
                                    </div>
                                    <p class="text-white/90 text-xs line-clamp-2" x-text="selectedVideo.title"></p>
                                    <p class="text-white/70 text-xs mt-1">#reels #kesfet #viral</p>
                                  </div>
                                </div>
                              </template>
                              
                              <!-- Facebook Style -->
                              <template x-if="previewPlatform === 'facebook'">
                                <div class="absolute inset-0 flex flex-col">
                                  <!-- Top bar -->
                                  <div class="p-3 flex items-center gap-3 bg-gradient-to-b from-black/50 to-transparent">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">VK</div>
                                    <div>
                                      <p class="text-white font-semibold text-sm">Video Kur</p>
                                      <p class="text-white/60 text-xs">Sponsorlu</p>
                                    </div>
                                  </div>
                                  <!-- Right sidebar -->
                                  <div class="absolute right-3 bottom-20 flex flex-col items-center gap-4">
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">👍</div>
                                      <span class="text-white text-xs mt-1">5.4K</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">💬</div>
                                      <span class="text-white text-xs mt-1">89</span>
                                    </div>
                                    <div class="text-center">
                                      <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">↗️</div>
                                      <span class="text-white text-xs mt-1">Paylaş</span>
                                    </div>
                                  </div>
                                  <!-- Bottom info -->
                                  <div class="absolute bottom-4 left-3 right-16">
                                    <p class="text-white/90 text-sm line-clamp-3" x-text="selectedVideo.title"></p>
                                  </div>
                                </div>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                </div>
              </template>
            </div>
          </div>
        </template>
        
      </div>
    </main>
  </div>

  <!-- Create Queue Modal -->
  <div x-show="createModal" x-cloak
       class="fixed inset-0 z-50 overflow-y-auto" 
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModals()"></div>
      
      <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 anim-fade">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">➕ Yeni Kuyruk Oluştur</h2>
        
        <div class="space-y-5">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kuyruk Adı</label>
            <input type="text" x-model="form.name" 
                   placeholder="örn: Günlük Paylaşımlar"
                   class="w-full px-4 py-3 border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
          </div>
          
          <!-- Platforms -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platformlar</label>
            <div class="grid grid-cols-2 gap-3">
              <template x-for="p in platformOptions" :key="p.id">
                <button type="button" @click="togglePlatform(p.id)"
                        :class="form.platforms.includes(p.id) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                        class="p-3 rounded-xl border-2 transition flex items-center gap-3">
                  <span class="text-2xl" x-text="p.icon"></span>
                  <span class="font-medium text-gray-900 dark:text-white" x-text="p.name"></span>
                  <svg x-show="form.platforms.includes(p.id)" class="w-5 h-5 ml-auto text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </button>
              </template>
            </div>
          </div>
          
          <!-- Schedule Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zamanlama Tipi</label>
            <div class="space-y-2">
              <template x-for="opt in scheduleOptions" :key="opt.id">
                <label :class="form.scheduleType === opt.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-slate-600'"
                       class="flex items-center p-3 rounded-xl border-2 cursor-pointer transition">
                  <input type="radio" :value="opt.id" x-model="form.scheduleType" class="sr-only">
                  <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white" x-text="opt.name"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="opt.desc"></p>
                  </div>
                  <div x-show="form.scheduleType === opt.id" class="w-5 h-5 bg-indigo-600 rounded-full flex items-center justify-center">
                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                  </div>
                </label>
              </template>
            </div>
            
            <!-- Interval Hours -->
            <div x-show="form.scheduleType === 'interval'" class="mt-4">
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Saat Aralığı</label>
              <select x-model="form.intervalHours" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 dark:text-white">
                <option value="1">Her 1 saat</option>
                <option value="2">Her 2 saat</option>
                <option value="4">Her 4 saat</option>
                <option value="6">Her 6 saat</option>
                <option value="12">Her 12 saat</option>
                <option value="24">Günde 1</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="flex gap-3 mt-8">
          <button @click="closeModals()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition">
            İptal
          </button>
          <button @click="createQueue()" :disabled="submitting" 
                  class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition disabled:opacity-50 flex items-center justify-center gap-2">
            <svg x-show="submitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="submitting ? 'Oluşturuluyor...' : 'Oluştur'"></span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Move Video Modal -->
  <div x-show="moveModal" x-cloak
       class="fixed inset-0 z-50 overflow-y-auto"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="moveModal = false"></div>
      
      <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📦 Videoyu Taşı</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Videoyu başka bir kuyruğa taşıyın</p>
        
        <div class="space-y-2 max-h-60 overflow-y-auto">
          <template x-for="queue in queues.filter(q => q.id !== selectedQueue?.id)" :key="queue.id">
            <button @click="moveVideoToQueue(queue.id)"
                    class="w-full p-4 text-left rounded-xl border border-gray-200 dark:border-slate-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition">
              <p class="font-medium text-gray-900 dark:text-white" x-text="queue.name"></p>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <span x-text="queue.videos?.length || 0"></span> video · 
                <template x-for="p in queue.platforms" :key="p">
                  <span x-text="getPlatformIcon(p)" class="mr-1"></span>
                </template>
              </p>
            </button>
          </template>
        </div>
        
        <button @click="moveModal = false" class="w-full mt-4 px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition">
          İptal
        </button>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/components/_footer.php'; ?>
  <?php include __DIR__ . '/components/_dark_mode.php'; ?>

  <script>
  function queuesApp() {
    return {
      sidebarOpen: false,
      darkMode: localStorage.getItem('darkMode') === '1',
      loading: true,
      
      // Data
      queues: [],
      selectedQueue: null,
      selectedVideo: null,
      filteredVideos: [],
      
      // UI State
      activeTab: 'all',
      filterStatus: 'all',
      previewPlatform: 'youtube',
      
      // Modals
      createModal: false,
      editModal: false,
      moveModal: false,
      scheduleModal: false,
      
      // Form
      form: {
        name: '',
        platforms: [],
        scheduleType: 'interval',
        intervalHours: 2,
        specificTimes: ['09:00', '15:00', '21:00']
      },
      submitting: false,
      
      // Options
      platformOptions: [
        { id: 'youtube', name: 'YouTube', icon: '📺', color: 'red' },
        { id: 'tiktok', name: 'TikTok', icon: '🎵', color: 'cyan' },
        { id: 'instagram', name: 'Instagram', icon: '📸', color: 'pink' },
        { id: 'facebook', name: 'Facebook', icon: '📘', color: 'blue' }
      ],
      scheduleOptions: [
        { id: 'now', name: 'Hemen Paylaş', desc: 'Video eklenince hemen paylaşılır' },
        { id: 'interval', name: 'Aralıklı', desc: 'Belirli saat aralıklarında paylaşılır' },
        { id: 'specific', name: 'Belirli Saatler', desc: 'Günün belirli saatlerinde paylaşılır' }
      ],
      
      async init() {
        this.darkMode = localStorage.getItem('darkMode') === '1';
        await this.loadQueues();
        this.initSortable();
      },
      
      async loadQueues() {
        this.loading = true;
        try {
          const r = await fetch('/api/queues.php?action=list');
          const d = await r.json();
          if (d.success) {
            this.queues = d.queues;
            if (this.queues.length > 0 && !this.selectedQueue) {
              await this.selectQueue(this.queues[0]);
            }
          }
        } catch(e) {
          console.error('Kuyruklar yüklenemedi:', e);
        } finally {
          this.loading = false;
        }
      },
      
      async selectQueue(queue) {
        this.activeTab = queue.id;
        this.selectedVideo = null;
        
        try {
          const r = await fetch('/api/queues.php?action=get&id=' + queue.id);
          const d = await r.json();
          if (d.success) {
            this.selectedQueue = d.queue;
            this.filterVideos();
            
            // Set first platform for preview
            if (this.selectedQueue.platforms?.length > 0) {
              this.previewPlatform = this.selectedQueue.platforms[0];
            }
            
            this.$nextTick(() => this.initSortable());
          }
        } catch(e) {
          console.error('Kuyruk detayı yüklenemedi:', e);
        }
      },
      
      filterVideos() {
        if (!this.selectedQueue?.videos) {
          this.filteredVideos = [];
          return;
        }
        
        let videos = [...this.selectedQueue.videos];
        
        if (this.filterStatus !== 'all') {
          videos = videos.filter(v => {
            if (this.filterStatus === 'pending') return v.status === 'queued' || v.status === 'pending';
            if (this.filterStatus === 'published') return v.status === 'published';
            if (this.filterStatus === 'failed') return v.status === 'failed';
            return true;
          });
        }
        
        this.filteredVideos = videos.sort((a, b) => (a.position || 0) - (b.position || 0));
      },
      
      selectVideo(video) {
        this.selectedVideo = video;
        if (video.platform_status) {
          const platforms = Object.keys(video.platform_status);
          if (platforms.length > 0) {
            this.previewPlatform = platforms[0];
          }
        }
      },
      
      initSortable() {
        const el = document.getElementById('videoList');
        if (!el || !window.Sortable) return;
        
        if (el._sortable) el._sortable.destroy();
        
        el._sortable = new Sortable(el, {
          animation: 150,
          ghostClass: 'sortable-ghost',
          dragClass: 'sortable-drag',
          handle: '.cursor-grab',
          onEnd: async (evt) => {
            const items = el.querySelectorAll('[data-id]');
            const order = Array.from(items).map(item => item.dataset.id);
            await this.reorderVideos(order);
          }
        });
      },
      
      async reorderVideos(order) {
        try {
          await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'reorder',
              queue_id: this.selectedQueue.id,
              video_order: order
            })
          });
          // Reload to sync
          await this.selectQueue({ id: this.selectedQueue.id });
        } catch(e) {
          console.error('Sıralama hatası:', e);
        }
      },
      
      // Helpers
      getPlatformIcon(platform) {
        const icons = { youtube: '📺', tiktok: '🎵', instagram: '📸', facebook: '📘' };
        return icons[platform] || '📱';
      },
      
      getStatusText(status) {
        const texts = {
          pending: 'Bekliyor',
          publishing: 'Yayınlanıyor...',
          published: 'Yayınlandı',
          failed: 'Başarısız'
        };
        return texts[status] || status;
      },
      
      formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('tr-TR', {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      },
      
      togglePlatform(platformId) {
        const idx = this.form.platforms.indexOf(platformId);
        if (idx > -1) {
          this.form.platforms.splice(idx, 1);
        } else {
          this.form.platforms.push(platformId);
        }
      },
      
      openCreateModal() {
        this.form = {
          name: '',
          platforms: [],
          scheduleType: 'interval',
          intervalHours: 2,
          specificTimes: ['09:00', '15:00', '21:00']
        };
        this.createModal = true;
      },
      
      closeModals() {
        this.createModal = false;
        this.editModal = false;
        this.moveModal = false;
        this.scheduleModal = false;
      },
      
      openMoveModal(video) {
        this.moveModal = true;
      },
      
      openScheduleModal(video) {
        this.scheduleModal = true;
      },
      
      async createQueue() {
        if (!this.form.name.trim()) {
          alert('Kuyruk ismi gerekli!');
          return;
        }
        if (this.form.platforms.length === 0) {
          alert('En az bir platform seçmelisiniz!');
          return;
        }
        
        this.submitting = true;
        
        try {
          const schedule = {
            type: this.form.scheduleType,
            interval_hours: parseInt(this.form.intervalHours),
            specific_times: this.form.specificTimes,
            timezone: 'Europe/Istanbul'
          };
          
          const response = await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'create',
              name: this.form.name.trim(),
              platforms: this.form.platforms,
              schedule: schedule
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            this.closeModals();
            await this.loadQueues();
            await this.selectQueue(result.queue);
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
        } finally {
          this.submitting = false;
        }
      },
      
      async removeFromQueue(jobId) {
        if (!confirm('Bu videoyu kuyruktan çıkarmak istediğinizden emin misiniz?')) {
          return;
        }
        
        try {
          const response = await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'remove_video',
              queue_id: this.selectedQueue.id,
              job_id: jobId
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            this.selectedVideo = null;
            await this.selectQueue({ id: this.selectedQueue.id });
            await this.loadQueues();
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
        }
      },
      
      async moveVideoToQueue(targetQueueId) {
        if (!this.selectedVideo) return;
        
        try {
          // First remove from current queue
          await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'remove_video',
              queue_id: this.selectedQueue.id,
              job_id: this.selectedVideo.job_id
            })
          });
          
          // Then add to target queue
          await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'add_video',
              queue_id: targetQueueId,
              job_id: this.selectedVideo.job_id
            })
          });
          
          this.moveModal = false;
          this.selectedVideo = null;
          await this.selectQueue({ id: this.selectedQueue.id });
          await this.loadQueues();
          
        } catch (error) {
          alert('Hata: ' + error.message);
        }
      }
    }
  }
  </script>
</body>
</html>
