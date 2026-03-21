<?php $page_title = 'Kuyruklar - Video Otomasyon'; $active_page = 'queues'; ?>
<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === '1' }" :class="{ 'dark': darkMode }">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <style>
    @keyframes fade-in-up { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .anim-fade-in { animation: fade-in-up .4s ease-out both; }
    .sortable-ghost { opacity: 0.4; background: #dbeafe !important; }
    .sortable-drag { box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
  function queuesApp() {
    return {
      sidebarOpen: false,
      darkMode: false,
      loading: true,
      queues: [],
      selectedQueue: null,
      selectedVideo: null,
      filteredVideos: [],
      filterStatus: 'all',
      previewPlatform: 'youtube',
      
      // Modal states
      createModal: false,
      editModal: false,
      detailModal: false,
      moveModal: false,
      
      // Form data
      form: {
        name: '',
        platforms: [],
        scheduleType: 'interval',
        intervalHours: 2,
        specificTimes: ['09:00', '15:00', '21:00']
      },
      
      submitting: false,
      
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
      
      openEditModal(queue) {
        this.selectedQueue = queue;
        this.form = {
          name: queue.name,
          platforms: [...queue.platforms],
          scheduleType: queue.schedule?.type || 'interval',
          intervalHours: queue.schedule?.interval_hours || 2,
          specificTimes: queue.schedule?.specific_times || ['09:00', '15:00', '21:00']
        };
        this.editModal = true;
      },
      
      async openDetailModal(queue) {
        this.selectedQueue = null;
        this.detailModal = true;
        
        try {
          const r = await fetch('/api/queues.php?action=get&id=' + queue.id);
          const d = await r.json();
          if (d.success) {
            this.selectedQueue = d.queue;
          }
        } catch(e) {
          console.error('Kuyruk detayı yüklenemedi:', e);
        }
      },
      
      closeModals() {
        this.createModal = false;
        this.editModal = false;
        this.detailModal = false;
        this.moveModal = false;
        this.selectedQueue = null;
      },
      
      // New: Select queue for two-column view
      async selectQueueForDetail(queue) {
        try {
          const r = await fetch('/api/queues.php?action=get&id=' + queue.id);
          const d = await r.json();
          if (d.success) {
            this.selectedQueue = d.queue;
            this.filterVideos();
            this.selectedVideo = null;
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
        if (this.filterStatus === 'pending') {
          videos = videos.filter(v => v.status !== 'published');
        } else if (this.filterStatus === 'published') {
          videos = videos.filter(v => v.status === 'published');
        }
        this.filteredVideos = videos;
      },
      
      selectVideo(video) {
        this.selectedVideo = video;
        if (video.platform_status) {
          const platforms = Object.keys(video.platform_status);
          if (platforms.length > 0) this.previewPlatform = platforms[0];
        }
      },
      
      initSortable() {
        const el = document.getElementById('videoSortList');
        if (!el || !window.Sortable) return;
        if (el._sortable) el._sortable.destroy();
        el._sortable = new Sortable(el, {
          animation: 150,
          ghostClass: 'sortable-ghost',
          handle: '.drag-handle',
          onEnd: async (evt) => {
            const items = el.querySelectorAll('[data-job-id]');
            const order = Array.from(items).map(item => item.dataset.jobId);
            await this.reorderVideos(order);
          }
        });
      },
      
      async reorderVideos(order) {
        try {
          await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reorder', queue_id: this.selectedQueue.id, video_order: order })
          });
          await this.selectQueueForDetail({ id: this.selectedQueue.id });
        } catch(e) { console.error('Sıralama hatası:', e); }
      },
      
      openMoveModal() {
        this.moveModal = true;
      },
      
      async moveVideoToQueue(targetQueueId) {
        if (!this.selectedVideo) return;
        try {
          // Remove from current
          await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_video', queue_id: this.selectedQueue.id, job_id: this.selectedVideo.job_id })
          });
          // Add to target
          await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_video', queue_id: targetQueueId, job_id: this.selectedVideo.job_id })
          });
          this.moveModal = false;
          this.selectedVideo = null;
          await this.selectQueueForDetail({ id: this.selectedQueue.id });
          await this.loadQueues();
        } catch(e) { alert('Hata: ' + e.message); }
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
            interval_hours: this.form.intervalHours,
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
            this.loadQueues();
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
        } finally {
          this.submitting = false;
        }
      },
      
      async updateQueue() {
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
            interval_hours: this.form.intervalHours,
            specific_times: this.form.specificTimes,
            timezone: 'Europe/Istanbul'
          };
          
          const response = await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'update',
              queue_id: this.selectedQueue.id,
              updates: {
                name: this.form.name.trim(),
                platforms: this.form.platforms,
                schedule: schedule
              }
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            this.closeModals();
            this.loadQueues();
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
        } finally {
          this.submitting = false;
        }
      },
      
      async deleteQueue(queue) {
        if (!confirm('Bu kuyruğu silmek istediğinizden emin misiniz?\n\nKuyruk: ' + queue.name + '\nİçindeki ' + (queue.videos?.length || 0) + ' video kuyruktan çıkarılacak.')) {
          return;
        }
        
        try {
          const response = await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'delete',
              queue_id: queue.id
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            this.loadQueues();
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
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
            // Reload with new method if in two-column view
            if (!this.detailModal) {
              await this.selectQueueForDetail({id: this.selectedQueue.id});
            } else {
              this.openDetailModal({id: this.selectedQueue.id});
            }
            this.loadQueues();
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
        }
      },
      
      getScheduleText(queue) {
        const s = queue.schedule;
        if (!s) return 'Ayarlanmamış';
        if (s.type === 'now') return '⚡ Hemen';
        if (s.type === 'interval') return '⏰ Her ' + (s.interval_hours || 2) + ' saatte bir';
        if (s.type === 'specific') return '📅 ' + (s.specific_times?.join(', ') || '');
        return 'Bilinmiyor';
      },
      
      getPendingCount(queue) {
        return (queue.videos || []).filter(v => v.status !== 'published').length;
      },
      
      getPublishedCount(queue) {
        return (queue.videos || []).filter(v => v.status === 'published').length;
      },
      
      toggleDark() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode ? '1' : '0');
        document.documentElement.classList.toggle('dark', this.darkMode);
      },
      
      async loadQueues() {
        try {
          const r = await fetch('/api/queues.php?action=list');
          const d = await r.json();
          this.queues = d.queues || [];
        } catch(e) {
          console.error('Kuyruklar yüklenemedi:', e);
        }
        this.loading = false;
      },
      
      init() {
        this.darkMode = localStorage.getItem('darkMode') === '1';
        this.loadQueues();
      }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="queuesApp()" x-init="init()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-5xl mx-auto">
          <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Kuyruklar</h1>
            <button 
              @click="openCreateModal()"
              class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition shadow-sm"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
              Yeni Kuyruk
            </button>
          </div>

          <!-- Loading -->
          <template x-if="loading">
            <div class="flex items-center justify-center py-16">
              <svg class="w-8 h-8 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span class="ml-3 text-gray-500 font-medium">Yükleniyor...</span>
            </div>
          </template>

          <!-- Empty State -->
          <template x-if="!loading && queues.length === 0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              <p class="text-gray-400 text-lg mb-2">Henüz kuyruk oluşturulmamış.</p>
              <p class="text-gray-400 text-sm mb-4">Videolarınızı organize etmek ve çoklu platformlara paylaşmak için bir kuyruk oluşturun.</p>
              <button 
                @click="openCreateModal()"
                class="inline-flex items-center gap-2 mt-3 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                İlk Kuyruğu Oluştur
              </button>
            </div>
          </template>

          <!-- Queue List -->
          <template x-if="!loading && queues.length > 0">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
              
              <!-- Left: Queue Tabs & Videos -->
              <div class="lg:col-span-4 space-y-4">
                <!-- Queue List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                  <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">📦 Kuyruklar</h2>
                    <span class="text-xs text-gray-400" x-text="queues.length + ' kuyruk'"></span>
                  </div>
                  <div class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
                    <template x-for="queue in queues" :key="queue.id">
                      <div 
                        @click="selectQueueForDetail(queue)"
                        class="p-4 cursor-pointer transition"
                        :class="selectedQueue?.id === queue.id ? 'bg-indigo-50 border-l-4 border-indigo-500' : 'hover:bg-gray-50 border-l-4 border-transparent'"
                      >
                        <div class="flex items-center justify-between">
                          <div class="min-w-0">
                            <h3 class="font-medium text-gray-800 truncate" x-text="queue.name"></h3>
                            <div class="flex items-center gap-1 mt-1">
                              <template x-for="p in queue.platforms" :key="p">
                                <span class="text-sm" x-text="p === 'youtube' ? '📺' : p === 'tiktok' ? '🎵' : p === 'instagram' ? '📸' : '📘'"></span>
                              </template>
                            </div>
                          </div>
                          <div class="text-right flex-shrink-0">
                            <span 
                              class="text-xs font-medium px-2 py-1 rounded-full"
                              :class="queue.is_active !== false ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                              x-text="queue.is_active !== false ? 'Aktif' : 'Pasif'"
                            ></span>
                            <p class="text-xs text-gray-400 mt-1" x-text="(queue.videos?.length || 0) + ' video'"></p>
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>
                
                <!-- Selected Queue Videos -->
                <template x-if="selectedQueue">
                  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden anim-fade-in">
                    <div class="p-4 border-b border-gray-100">
                      <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-800">Videolar</h3>
                        <div class="flex items-center gap-2">
                          <button 
                            @click="openEditModal(selectedQueue)"
                            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                            title="Düzenle"
                          >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                          </button>
                          <button 
                            @click="deleteQueue(selectedQueue)"
                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                            title="Sil"
                          >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                          </button>
                        </div>
                      </div>
                      <div class="flex items-center gap-2">
                        <select x-model="filterStatus" @change="filterVideos()" class="text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white">
                          <option value="all">Tümü</option>
                          <option value="pending">⏳ Bekleyen</option>
                          <option value="published">✅ Yayınlanan</option>
                        </select>
                        <span class="text-xs text-gray-400" x-text="filteredVideos.length + ' video'"></span>
                      </div>
                    </div>
                    
                    <div class="max-h-96 overflow-y-auto" id="videoSortList">
                      <template x-if="filteredVideos.length === 0">
                        <div class="p-8 text-center">
                          <p class="text-gray-400 text-sm">Video yok</p>
                          <a href="dashboard.php" class="text-indigo-600 text-sm hover:underline mt-2 inline-block">Videolar'dan ekleyin →</a>
                        </div>
                      </template>
                      
                      <template x-for="(video, idx) in filteredVideos" :key="video.job_id">
                        <div 
                          @click="selectVideo(video)"
                          class="p-3 cursor-pointer transition border-b border-gray-100 last:border-0"
                          :class="selectedVideo?.job_id === video.job_id ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                          :data-job-id="video.job_id"
                        >
                          <div class="flex items-center gap-3">
                            <!-- Drag handle -->
                            <div class="drag-handle cursor-grab text-gray-300 hover:text-gray-500">
                              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6-12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                            </div>
                            
                            <!-- Thumbnail -->
                            <div class="w-12 h-10 rounded-lg overflow-hidden bg-gray-200 flex-shrink-0">
                              <template x-if="video.thumbnailUrl">
                                <img :src="video.thumbnailUrl" class="w-full h-full object-cover">
                              </template>
                              <template x-if="!video.thumbnailUrl">
                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs font-bold" x-text="idx + 1"></div>
                              </template>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                              <p class="text-sm font-medium text-gray-800 truncate" x-text="video.title || 'Video'"></p>
                              <div class="flex gap-1 mt-1">
                                <template x-for="(status, platform) in video.platform_status" :key="platform">
                                  <span 
                                    class="text-xs px-1.5 py-0.5 rounded"
                                    :class="{
                                      'bg-green-100 text-green-700': status === 'published',
                                      'bg-yellow-100 text-yellow-700': status === 'pending',
                                      'bg-red-100 text-red-700': status === 'failed'
                                    }"
                                  >
                                    <span x-text="platform === 'youtube' ? '📺' : platform === 'tiktok' ? '🎵' : platform === 'instagram' ? '📸' : '📘'"></span>
                                  </span>
                                </template>
                              </div>
                            </div>
                          </div>
                        </div>
                      </template>
                    </div>
                  </div>
                </template>
                
                <!-- No Queue Selected -->
                <template x-if="!selectedQueue">
                  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <p class="text-gray-400">Kuyruk seçin</p>
                  </div>
                </template>
              </div>
              
              <!-- Right: Video Details & Preview -->
              <div class="lg:col-span-8">
                <template x-if="!selectedVideo">
                  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Video Seçin</h3>
                    <p class="text-gray-400">Sol listeden bir video seçerek detayları görüntüleyin</p>
                  </div>
                </template>
                
                <template x-if="selectedVideo">
                  <div class="space-y-4 anim-fade-in">
                    <!-- Video Info Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                      <div class="p-5 border-b border-gray-100">
                        <div class="flex items-start justify-between">
                          <div class="min-w-0">
                            <h2 class="text-xl font-bold text-gray-800 truncate" x-text="selectedVideo?.title || 'Video'"></h2>
                            <p class="text-sm text-gray-500 mt-1">
                              Kuyruk: <span class="text-indigo-600 font-medium" x-text="selectedQueue?.name"></span>
                            </p>
                          </div>
                          <div class="flex gap-2 flex-shrink-0">
                            <button 
                              @click="openMoveModal()"
                              class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                              title="Başka Kuyruğa Taşı"
                            >
                              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </button>
                            <button 
                              @click="removeFromQueue(selectedVideo.job_id)"
                              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                              title="Kuyruktan Çıkar"
                            >
                              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Platform Status Cards -->
                      <div class="p-5">
                        <h3 class="text-sm font-semibold text-gray-600 mb-3">Platform Durumları</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                          <template x-for="(status, platform) in selectedVideo?.platform_status || {}" :key="platform">
                            <div 
                              class="rounded-xl p-3 border-2 transition relative"
                              :class="{
                                'border-green-300 bg-green-50': status === 'published',
                                'border-yellow-300 bg-yellow-50': status === 'pending',
                                'border-red-300 bg-red-50': status === 'failed'
                              }"
                            >
                              <div class="flex items-center gap-2">
                                <span class="text-xl" x-text="platform === 'youtube' ? '📺' : platform === 'tiktok' ? '🎵' : platform === 'instagram' ? '📸' : '📘'"></span>
                                <div>
                                  <p class="font-medium text-gray-800 capitalize text-sm" x-text="platform"></p>
                                  <p 
                                    class="text-xs"
                                    :class="{
                                      'text-green-600': status === 'published',
                                      'text-yellow-600': status === 'pending',
                                      'text-red-600': status === 'failed'
                                    }"
                                    x-text="status === 'published' ? '✓ Yayınlandı' : status === 'pending' ? '⏳ Bekliyor' : '✗ Başarısız'"
                                  ></p>
                                </div>
                              </div>
                              <!-- Link to published post -->
                              <template x-if="status === 'published' && selectedVideo?.post_urls?.[platform]">
                                <a 
                                  :href="selectedVideo.post_urls[platform]" 
                                  target="_blank"
                                  class="absolute top-2 right-2 p-1 bg-white rounded shadow hover:shadow-md transition"
                                  title="Paylaşıma Git"
                                >
                                  <svg class="w-3.5 h-3.5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                              </template>
                            </div>
                          </template>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Platform Preview -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                      <div class="p-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">📱 Platform Önizlemesi</h3>
                      </div>
                      
                      <!-- Platform Tabs -->
                      <div class="flex gap-2 p-4 border-b border-gray-100 overflow-x-auto">
                        <template x-for="(status, platform) in selectedVideo?.platform_status || {}" :key="'tab-' + platform">
                          <button 
                            @click="previewPlatform = platform"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"
                            :class="previewPlatform === platform ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                          >
                            <span x-text="platform === 'youtube' ? '📺' : platform === 'tiktok' ? '🎵' : platform === 'instagram' ? '📸' : '📘'"></span>
                            <span class="capitalize" x-text="platform"></span>
                          </button>
                        </template>
                      </div>
                      
                      <!-- Phone Preview -->
                      <div class="p-6 flex justify-center bg-gray-900">
                        <div class="w-56 bg-gradient-to-b from-gray-800 to-black rounded-3xl p-1.5 shadow-2xl">
                          <div class="bg-black rounded-2xl overflow-hidden aspect-[9/16] relative">
                            <!-- Video Background -->
                            <div class="absolute inset-0 bg-gradient-to-b from-gray-800 to-gray-900">
                              <template x-if="selectedVideo?.thumbnailUrl">
                                <img :src="selectedVideo.thumbnailUrl" class="w-full h-full object-cover opacity-80">
                              </template>
                            </div>
                            
                            <!-- TikTok UI -->
                            <div x-show="previewPlatform === 'tiktok'" class="absolute inset-0">
                              <div class="absolute right-2 bottom-14 flex flex-col items-center gap-3">
                                <div class="text-center"><div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-xs">❤️</div><span class="text-white text-[10px]">12K</span></div>
                                <div class="text-center"><div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-xs">💬</div><span class="text-white text-[10px]">234</span></div>
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-xs">↗️</div>
                              </div>
                              <div class="absolute bottom-2 left-2 right-10">
                                <p class="text-white font-semibold text-xs">@kullanici</p>
                                <p class="text-white/90 text-[10px] mt-0.5 line-clamp-2" x-text="selectedVideo?.title"></p>
                                <p class="text-white/60 text-[10px] mt-0.5">#fyp #viral</p>
                              </div>
                            </div>
                            
                            <!-- YouTube UI -->
                            <div x-show="previewPlatform === 'youtube'" class="absolute inset-0">
                              <div class="p-2 flex items-center justify-between">
                                <span class="text-white text-xs font-bold">Shorts</span>
                              </div>
                              <div class="absolute right-2 bottom-14 flex flex-col items-center gap-3">
                                <div class="text-center"><div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-xs">👍</div><span class="text-white text-[10px]">15K</span></div>
                                <div class="text-center"><div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-xs">💬</div><span class="text-white text-[10px]">456</span></div>
                              </div>
                              <div class="absolute bottom-2 left-2 right-10">
                                <div class="flex items-center gap-1.5 mb-1">
                                  <div class="w-5 h-5 bg-red-500 rounded-full"></div>
                                  <span class="text-white text-[10px]">@VideoKur</span>
                                </div>
                                <p class="text-white/90 text-[10px] line-clamp-2" x-text="selectedVideo?.title"></p>
                              </div>
                            </div>
                            
                            <!-- Instagram UI -->
                            <div x-show="previewPlatform === 'instagram'" class="absolute inset-0">
                              <div class="p-2 bg-gradient-to-b from-black/50 to-transparent">
                                <span class="text-white text-xs font-semibold">Reels</span>
                              </div>
                              <div class="absolute right-2 bottom-14 flex flex-col items-center gap-3">
                                <div class="w-7 h-7 flex items-center justify-center text-sm">❤️</div>
                                <div class="w-7 h-7 flex items-center justify-center text-sm">💬</div>
                                <div class="w-7 h-7 flex items-center justify-center text-sm">↗️</div>
                              </div>
                              <div class="absolute bottom-2 left-2 right-10">
                                <p class="text-white font-semibold text-xs">videokur</p>
                                <p class="text-white/90 text-[10px] mt-0.5 line-clamp-2" x-text="selectedVideo?.title"></p>
                              </div>
                            </div>
                            
                            <!-- Facebook UI -->
                            <div x-show="previewPlatform === 'facebook'" class="absolute inset-0">
                              <div class="p-2 flex items-center gap-2 bg-gradient-to-b from-black/50 to-transparent">
                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-[8px] font-bold">VK</div>
                                <span class="text-white text-[10px] font-medium">Video Kur</span>
                              </div>
                              <div class="absolute bottom-2 left-2 right-2">
                                <p class="text-white/90 text-[10px] line-clamp-2" x-text="selectedVideo?.title"></p>
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

    <?php include __DIR__ . '/components/_footer.php'; ?>
  </div>

  <!-- Create/Edit Queue Modal -->
  <template x-if="createModal || editModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="closeModals()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-white" x-text="createModal ? '📋 Yeni Kuyruk Oluştur' : '✏️ Kuyruğu Düzenle'"></h3>
            <button @click="closeModals()" class="text-white/80 hover:text-white">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
        
        <div class="p-6">
          <!-- Name -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Kuyruk İsmi</label>
            <input 
              type="text" 
              x-model="form.name"
              placeholder="Örn: Komedi Videoları"
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            >
          </div>
          
          <!-- Platforms -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Platformlar</label>
            <div class="grid grid-cols-2 gap-3">
              <template x-for="platform in platformOptions" :key="platform.id">
                <label 
                  class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition"
                  :class="form.platforms.includes(platform.id) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                >
                  <input 
                    type="checkbox" 
                    :checked="form.platforms.includes(platform.id)"
                    @change="togglePlatform(platform.id)"
                    class="w-5 h-5 text-indigo-600 rounded"
                  >
                  <div>
                    <div class="font-medium" x-text="platform.icon + ' ' + platform.name"></div>
                  </div>
                </label>
              </template>
            </div>
          </div>
          
          <!-- Schedule Type -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Paylaşım Zamanlaması</label>
            <div class="space-y-2">
              <template x-for="option in scheduleOptions" :key="option.id">
                <label 
                  class="flex items-start gap-3 p-3 border-2 rounded-xl cursor-pointer transition"
                  :class="form.scheduleType === option.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                >
                  <input 
                    type="radio" 
                    name="scheduleType"
                    :value="option.id"
                    x-model="form.scheduleType"
                    class="w-5 h-5 text-indigo-600 mt-0.5"
                  >
                  <div>
                    <div class="font-medium" x-text="option.name"></div>
                    <div class="text-sm text-gray-500" x-text="option.desc"></div>
                  </div>
                </label>
              </template>
            </div>
          </div>
          
          <!-- Interval Hours -->
          <template x-if="form.scheduleType === 'interval'">
            <div class="mb-5">
              <label class="block text-sm font-medium text-gray-700 mb-2">Kaç Saatte Bir?</label>
              <select 
                x-model="form.intervalHours"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              >
                <option value="1">Her 1 saatte bir</option>
                <option value="2">Her 2 saatte bir</option>
                <option value="3">Her 3 saatte bir</option>
                <option value="4">Her 4 saatte bir</option>
                <option value="6">Her 6 saatte bir</option>
                <option value="8">Her 8 saatte bir</option>
                <option value="12">Her 12 saatte bir</option>
                <option value="24">Günde bir</option>
              </select>
            </div>
          </template>
          
          <!-- Specific Times -->
          <template x-if="form.scheduleType === 'specific'">
            <div class="mb-5">
              <label class="block text-sm font-medium text-gray-700 mb-2">Paylaşım Saatleri</label>
              <div class="flex flex-wrap gap-2">
                <template x-for="(time, idx) in form.specificTimes" :key="idx">
                  <div class="flex items-center gap-1 bg-gray-100 rounded-lg px-3 py-1.5">
                    <input 
                      type="time" 
                      x-model="form.specificTimes[idx]"
                      class="bg-transparent border-none text-sm font-medium focus:outline-none"
                    >
                    <button 
                      @click="form.specificTimes.splice(idx, 1)"
                      class="text-gray-400 hover:text-red-500"
                      x-show="form.specificTimes.length > 1"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                </template>
                <button 
                  @click="form.specificTimes.push('12:00')"
                  class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                >
                  + Saat Ekle
                </button>
              </div>
            </div>
          </template>
          
          <!-- Actions -->
          <div class="flex justify-end gap-3 mt-6">
            <button 
              @click="closeModals()" 
              class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold transition"
            >
              İptal
            </button>
            <button 
              @click="createModal ? createQueue() : updateQueue()" 
              :disabled="submitting"
              class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg font-semibold transition disabled:opacity-50"
            >
              <span x-show="!submitting" x-text="createModal ? '✓ Oluştur' : '✓ Kaydet'"></span>
              <span x-show="submitting">⏳ İşleniyor...</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <!-- Queue Detail Modal -->
  <template x-if="detailModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="closeModals()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-white">
              📋 <span x-text="selectedQueue?.name || 'Kuyruk Detayı'"></span>
            </h3>
            <button @click="closeModals()" class="text-white/80 hover:text-white">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
        
        <div class="p-6">
          <template x-if="!selectedQueue">
            <div class="text-center py-8">
              <svg class="w-8 h-8 mx-auto text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <p class="mt-3 text-gray-500">Yükleniyor...</p>
            </div>
          </template>
          
          <template x-if="selectedQueue">
            <div>
              <!-- Queue Info -->
              <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <div class="flex flex-wrap gap-2 mb-3">
                  <template x-for="platform in selectedQueue.platforms" :key="platform">
                    <span 
                      class="px-2.5 py-1 rounded-full text-xs font-medium"
                      :class="{
                        'bg-red-100 text-red-700': platform === 'youtube',
                        'bg-cyan-100 text-cyan-700': platform === 'tiktok',
                        'bg-pink-100 text-pink-700': platform === 'instagram',
                        'bg-blue-100 text-blue-700': platform === 'facebook'
                      }"
                    >
                      <span x-text="platform === 'youtube' ? '📺 YouTube' : platform === 'tiktok' ? '🎵 TikTok' : platform === 'instagram' ? '📸 Instagram' : '📘 Facebook'"></span>
                    </span>
                  </template>
                </div>
                <p class="text-sm text-gray-600" x-text="getScheduleText(selectedQueue)"></p>
              </div>
              
              <!-- Videos List -->
              <h4 class="font-semibold text-gray-800 mb-3">Kuyruktaki Videolar (<span x-text="selectedQueue.videos?.length || 0"></span>)</h4>
              
              <template x-if="!selectedQueue.videos || selectedQueue.videos.length === 0">
                <div class="text-center py-8 bg-gray-50 rounded-xl">
                  <p class="text-gray-500">Bu kuyrukta henüz video yok.</p>
                  <a href="dashboard.php" class="inline-block mt-3 text-indigo-600 hover:underline">Videolar sayfasından ekleyin →</a>
                </div>
              </template>
              
              <template x-if="selectedQueue.videos && selectedQueue.videos.length > 0">
                <div class="space-y-3">
                  <template x-for="(video, idx) in selectedQueue.videos" :key="video.job_id">
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                      <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm" x-text="idx + 1"></div>
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 truncate" x-text="video.title || 'Video'"></p>
                        <div class="flex flex-wrap gap-1.5 mt-1">
                          <template x-for="(status, platform) in video.platform_status" :key="platform">
                            <span 
                              class="px-1.5 py-0.5 rounded text-xs font-medium"
                              :class="{
                                'bg-green-100 text-green-700': status === 'published',
                                'bg-yellow-100 text-yellow-700': status === 'pending',
                                'bg-red-100 text-red-700': status === 'failed'
                              }"
                            >
                              <span x-text="platform === 'youtube' ? '📺' : platform === 'tiktok' ? '🎵' : platform === 'instagram' ? '📸' : '📘'"></span>
                              <span x-text="status === 'published' ? '✓' : status === 'failed' ? '✗' : '⏳'"></span>
                            </span>
                          </template>
                        </div>
                      </div>
                      <button 
                        @click="removeFromQueue(video.job_id)"
                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Kuyruktan Çıkar"
                      >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                      </button>
                    </div>
                  </template>
                </div>
              </template>
            </div>
          </template>
        </div>
      </div>
    </div>
  </template>

  <!-- Move Video Modal -->
  <template x-if="moveModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="moveModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-white">📦 Videoyu Taşı</h3>
            <button @click="moveModal = false" class="text-white/80 hover:text-white">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-4">
            "<span class="font-medium text-gray-800" x-text="selectedVideo?.title"></span>" videosunu taşımak için hedef kuyruğu seçin:
          </p>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <template x-for="queue in queues.filter(q => q.id !== selectedQueue?.id)" :key="queue.id">
              <button 
                @click="moveVideoToQueue(queue.id)"
                class="w-full p-4 text-left rounded-xl border-2 border-gray-200 hover:border-indigo-500 hover:bg-indigo-50 transition"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium text-gray-800" x-text="queue.name"></p>
                    <div class="flex items-center gap-1 mt-1">
                      <template x-for="p in queue.platforms" :key="p">
                        <span class="text-sm" x-text="p === 'youtube' ? '📺' : p === 'tiktok' ? '🎵' : p === 'instagram' ? '📸' : '📘'"></span>
                      </template>
                    </div>
                  </div>
                  <span class="text-xs text-gray-400" x-text="(queue.videos?.length || 0) + ' video'"></span>
                </div>
              </button>
            </template>
          </div>
          <button 
            @click="moveModal = false" 
            class="w-full mt-4 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition"
          >
            İptal
          </button>
        </div>
      </div>
    </div>
  </template>
</body>
</html>
