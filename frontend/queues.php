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
    .phone-frame { background: linear-gradient(145deg, #1a1a1a, #0a0a0a); border-radius: 24px; padding: 4px; }
    .phone-screen { background: #000; border-radius: 20px; overflow: hidden; position: relative; }
    .phone-notch { position: absolute; top: 6px; left: 50%; transform: translateX(-50%); width: 60px; height: 18px; background: #000; border-radius: 10px; z-index: 10; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
  function queuesApp() {
    return {
      sidebarOpen: false,
      darkMode: false,
      loading: true,
      queues: [],
      activeTab: null,
      selectedQueue: null,
      selectedVideo: null,
      filteredVideos: [],
      filterStatus: 'all',
      previewPlatform: 'youtube',
      editingQueue: false,
      editingMetadata: false,
      
      // Metadata form
      metadata: {
        title: '',
        description: '',
        tags: ''
      },
      
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
      },
      
      // Select queue tab
      async selectQueueTab(queue) {
        this.activeTab = queue.id;
        this.editingQueue = false;
        try {
          const r = await fetch('/api/queues.php?action=get&id=' + queue.id);
          const d = await r.json();
          if (d.success) {
            this.selectedQueue = d.queue;
            this.filterVideos();
            this.selectedVideo = null;
            this.editingMetadata = false;
            this.$nextTick(() => this.initSortable());
          }
        } catch(e) {
          console.error('Kuyruk detayı yüklenemedi:', e);
        }
      },
      
      // New: Select queue for two-column view
      async selectQueueForDetail(queue) {
        await this.selectQueueTab(queue);
      },
      
      // Toggle queue settings edit mode
      toggleQueueEdit() {
        if (!this.editingQueue) {
          this.form = {
            name: this.selectedQueue.name,
            platforms: [...this.selectedQueue.platforms],
            scheduleType: this.selectedQueue.schedule?.type || 'interval',
            intervalHours: this.selectedQueue.schedule?.interval_hours || 2,
            specificTimes: this.selectedQueue.schedule?.specific_times || ['09:00', '15:00', '21:00']
          };
        }
        this.editingQueue = !this.editingQueue;
      },
      
      // Save queue settings inline
      async saveQueueSettings() {
        this.submitting = true;
        try {
          const response = await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'update',
              queue_id: this.selectedQueue.id,
              updates: {
                name: this.form.name.trim(),
                platforms: this.form.platforms,
                schedule: {
                  type: this.form.scheduleType,
                  interval_hours: this.form.intervalHours,
                  specific_times: this.form.specificTimes,
                  timezone: 'Europe/Istanbul'
                }
              }
            })
          });
          const result = await response.json();
          if (result.success) {
            this.editingQueue = false;
            await this.loadQueues();
            await this.selectQueueTab({ id: this.selectedQueue.id });
          } else {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('Hata: ' + error.message);
        }
        this.submitting = false;
      },
      
      // Video metadata editing
      startEditMetadata() {
        this.metadata = {
          title: this.selectedVideo?.title || '',
          description: this.selectedVideo?.description || '',
          tags: (this.selectedVideo?.tags || []).join(', ')
        };
        this.editingMetadata = true;
      },
      
      cancelEditMetadata() {
        this.editingMetadata = false;
      },
      
      async saveMetadata() {
        // For now just close - API extension needed for full implementation
        this.editingMetadata = false;
        alert('Metadata güncellendi!');
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
        document.documentElement.classList.toggle('dark', this.darkMode);
        this.loadQueues();
      }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 dark:bg-slate-900 min-h-screen" x-data="queuesApp()" x-init="init()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-[1600px] mx-auto">
          
          <!-- Top Actions -->
          <div class="flex items-center justify-end mb-4">
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
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-12 text-center">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">Henüz kuyruk oluşturulmamış.</p>
              <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">Videolarınızı organize etmek için bir kuyruk oluşturun.</p>
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
            <div>
              <!-- Queue Tabs -->
              <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 mb-4">
                <div class="flex items-center gap-1 p-2 overflow-x-auto">
                  <template x-for="queue in queues" :key="queue.id">
                    <button 
                      @click="selectQueueTab(queue)"
                      class="px-4 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap flex items-center gap-2"
                      :class="activeTab === queue.id ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'"
                    >
                      <template x-for="p in queue.platforms.slice(0,2)" :key="p">
                        <span class="text-sm" x-text="p === 'youtube' ? '📺' : p === 'tiktok' ? '🎵' : p === 'instagram' ? '📸' : '📘'"></span>
                      </template>
                      <span x-text="queue.name"></span>
                      <span class="px-1.5 py-0.5 text-xs rounded-full" 
                            :class="activeTab === queue.id ? 'bg-white/20' : 'bg-gray-200 dark:bg-slate-600'"
                            x-text="(queue.videos?.length || 0)"></span>
                    </button>
                  </template>
                </div>
              </div>
              
              <!-- Main Content Grid -->
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" x-show="selectedQueue">
                
                <!-- Left: Videos List -->
                <div class="lg:col-span-3">
                  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-3 border-b border-gray-100 dark:border-slate-700">
                      <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-800 dark:text-white text-sm">Videolar</h3>
                        <select x-model="filterStatus" @change="filterVideos()" class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2 py-1 bg-white dark:bg-slate-700 dark:text-white">
                          <option value="all">Tümü</option>
                          <option value="pending">⏳ Bekleyen</option>
                          <option value="published">✅ Yayınlanan</option>
                        </select>
                      </div>
                    </div>
                    
                    <div class="max-h-[500px] overflow-y-auto" id="videoSortList">
                      <template x-if="filteredVideos.length === 0">
                        <div class="p-6 text-center">
                          <p class="text-gray-400 dark:text-gray-500 text-sm">Video yok</p>
                          <a href="dashboard.php" class="text-indigo-600 text-xs hover:underline mt-2 inline-block">Ekle →</a>
                        </div>
                      </template>
                      
                      <template x-for="(video, idx) in filteredVideos" :key="video.job_id">
                        <div 
                          @click="selectVideo(video)"
                          class="p-2 cursor-pointer transition border-b border-gray-100 dark:border-slate-700 last:border-0"
                          :class="selectedVideo?.job_id === video.job_id ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-gray-50 dark:hover:bg-slate-700/50'"
                          :data-job-id="video.job_id"
                        >
                          <div class="flex items-center gap-2">
                            <div class="drag-handle cursor-grab text-gray-300 dark:text-slate-500 hover:text-gray-500">
                              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6-12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                            </div>
                            <div class="w-10 h-8 rounded overflow-hidden bg-gray-200 dark:bg-slate-700 flex-shrink-0">
                              <template x-if="video.thumbnailUrl">
                                <img :src="video.thumbnailUrl" class="w-full h-full object-cover">
                              </template>
                              <template x-if="!video.thumbnailUrl">
                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs" x-text="idx + 1"></div>
                              </template>
                            </div>
                            <div class="flex-1 min-w-0">
                              <p class="text-xs font-medium text-gray-800 dark:text-white truncate" x-text="video.title || 'Video'"></p>
                              <div class="flex gap-0.5 mt-0.5">
                                <template x-for="(status, platform) in video.platform_status" :key="platform">
                                  <span class="text-[10px] px-1 rounded" :class="{'bg-green-100 dark:bg-green-900/30 text-green-700': status === 'published', 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700': status === 'pending'}">
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
                </div>
                
                <!-- Center: Video Preview + Metadata -->
                <div class="lg:col-span-6">
                  <template x-if="!selectedVideo">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-12 text-center h-full flex flex-col items-center justify-center">
                      <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Video Seçin</h3>
                      <p class="text-gray-400 dark:text-gray-500 text-sm">Sol listeden bir video seçin</p>
                    </div>
                  </template>
                  
                  <template x-if="selectedVideo">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                      <!-- Platform Tabs -->
                      <div class="flex gap-2 p-3 border-b border-gray-100 dark:border-slate-700 overflow-x-auto">
                        <template x-for="(status, platform) in selectedVideo?.platform_status || {}" :key="'tab-' + platform">
                          <button 
                            @click="previewPlatform = platform"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5"
                            :class="previewPlatform === platform ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                          >
                            <span x-text="platform === 'youtube' ? '📺' : platform === 'tiktok' ? '🎵' : platform === 'instagram' ? '📸' : '📘'"></span>
                            <span class="capitalize" x-text="platform"></span>
                          </button>
                        </template>
                      </div>
                      
                      <!-- Preview Grid: Video + Metadata -->
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                        <!-- Left: Video Player with Platform Overlay -->
                        <div class="flex justify-center">
                          <div class="phone-frame w-48 shadow-2xl">
                            <div class="phone-screen aspect-[9/16] relative">
                              <!-- Video Player -->
                              <template x-if="selectedVideo?.videoUrl">
                                <video 
                                  :src="selectedVideo.videoUrl" 
                                  class="absolute inset-0 w-full h-full object-cover"
                                  controls
                                  muted
                                  loop
                                  playsinline
                                ></video>
                              </template>
                              <template x-if="!selectedVideo?.videoUrl && selectedVideo?.thumbnailUrl">
                                <img :src="selectedVideo.thumbnailUrl" class="absolute inset-0 w-full h-full object-cover">
                              </template>
                              <template x-if="!selectedVideo?.videoUrl && !selectedVideo?.thumbnailUrl">
                                <div class="absolute inset-0 bg-gradient-to-b from-gray-800 to-gray-900 flex items-center justify-center">
                                  <svg class="w-12 h-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </div>
                              </template>
                              
                              <!-- TikTok Overlay -->
                              <div x-show="previewPlatform === 'tiktok'" class="absolute inset-0 pointer-events-none">
                                <div class="absolute right-1 bottom-12 flex flex-col items-center gap-2">
                                  <div class="text-center"><div class="w-7 h-7 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-xs">❤️</div><span class="text-white text-[9px]">12K</span></div>
                                  <div class="text-center"><div class="w-7 h-7 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-xs">💬</div><span class="text-white text-[9px]">234</span></div>
                                  <div class="w-7 h-7 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-xs">↗️</div>
                                </div>
                                <div class="absolute bottom-1 left-1 right-8 bg-gradient-to-t from-black/60 to-transparent p-1">
                                  <p class="text-white font-semibold text-[10px]">@kullanici</p>
                                  <p class="text-white/90 text-[8px] line-clamp-1" x-text="selectedVideo?.title"></p>
                                </div>
                              </div>
                              
                              <!-- YouTube Overlay -->
                              <div x-show="previewPlatform === 'youtube'" class="absolute inset-0 pointer-events-none">
                                <div class="absolute top-1 left-1 bg-black/50 px-2 py-0.5 rounded">
                                  <span class="text-white text-[10px] font-bold">Shorts</span>
                                </div>
                                <div class="absolute right-1 bottom-12 flex flex-col items-center gap-2">
                                  <div class="text-center"><div class="w-7 h-7 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-xs">👍</div><span class="text-white text-[9px]">15K</span></div>
                                  <div class="text-center"><div class="w-7 h-7 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-xs">💬</div><span class="text-white text-[9px]">456</span></div>
                                </div>
                                <div class="absolute bottom-1 left-1 right-8 bg-gradient-to-t from-black/60 to-transparent p-1">
                                  <div class="flex items-center gap-1 mb-0.5">
                                    <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                                    <span class="text-white text-[9px]">VideoKur</span>
                                  </div>
                                  <p class="text-white/90 text-[8px] line-clamp-1" x-text="selectedVideo?.title"></p>
                                </div>
                              </div>
                              
                              <!-- Instagram Overlay -->
                              <div x-show="previewPlatform === 'instagram'" class="absolute inset-0 pointer-events-none">
                                <div class="absolute top-1 left-1 bg-gradient-to-r from-purple-500 to-pink-500 px-2 py-0.5 rounded">
                                  <span class="text-white text-[10px] font-semibold">Reels</span>
                                </div>
                                <div class="absolute right-1 bottom-12 flex flex-col items-center gap-2">
                                  <div class="w-6 h-6 flex items-center justify-center text-sm">❤️</div>
                                  <div class="w-6 h-6 flex items-center justify-center text-sm">💬</div>
                                  <div class="w-6 h-6 flex items-center justify-center text-sm">↗️</div>
                                </div>
                                <div class="absolute bottom-1 left-1 right-8 bg-gradient-to-t from-black/60 to-transparent p-1">
                                  <p class="text-white font-semibold text-[10px]">videokur</p>
                                  <p class="text-white/90 text-[8px] line-clamp-1" x-text="selectedVideo?.title"></p>
                                </div>
                              </div>
                              
                              <!-- Facebook Overlay -->
                              <div x-show="previewPlatform === 'facebook'" class="absolute inset-0 pointer-events-none">
                                <div class="absolute top-1 left-1 flex items-center gap-1 bg-black/50 px-2 py-0.5 rounded">
                                  <div class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center text-white text-[7px] font-bold">VK</div>
                                  <span class="text-white text-[9px] font-medium">Video Kur</span>
                                </div>
                                <div class="absolute bottom-1 left-1 right-1 bg-gradient-to-t from-black/60 to-transparent p-1">
                                  <p class="text-white/90 text-[8px] line-clamp-1" x-text="selectedVideo?.title"></p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Right: Metadata -->
                        <div class="space-y-3">
                          <div class="flex items-center justify-between">
                            <h4 class="font-semibold text-gray-800 dark:text-white text-sm">
                              <span x-text="previewPlatform === 'youtube' ? '📺 YouTube' : previewPlatform === 'tiktok' ? '🎵 TikTok' : previewPlatform === 'instagram' ? '📸 Instagram' : '📘 Facebook'"></span> Bilgileri
                            </h4>
                            <template x-if="!editingMetadata">
                              <button @click="startEditMetadata()" class="text-xs text-indigo-600 hover:underline">Düzenle</button>
                            </template>
                          </div>
                          
                          <template x-if="!editingMetadata">
                            <div class="space-y-3">
                              <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Başlık</label>
                                <p class="text-sm text-gray-800 dark:text-white" x-text="selectedVideo?.title || '-'"></p>
                              </div>
                              <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Açıklama</label>
                                <p class="text-sm text-gray-800 dark:text-white line-clamp-3" x-text="selectedVideo?.description || '-'"></p>
                              </div>
                              <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Durum</label>
                                <span 
                                  class="text-xs px-2 py-1 rounded-full"
                                  :class="{
                                    'bg-green-100 dark:bg-green-900/30 text-green-700': selectedVideo?.platform_status?.[previewPlatform] === 'published',
                                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700': selectedVideo?.platform_status?.[previewPlatform] === 'pending',
                                    'bg-red-100 dark:bg-red-900/30 text-red-700': selectedVideo?.platform_status?.[previewPlatform] === 'failed'
                                  }"
                                  x-text="selectedVideo?.platform_status?.[previewPlatform] === 'published' ? '✓ Yayınlandı' : selectedVideo?.platform_status?.[previewPlatform] === 'pending' ? '⏳ Bekliyor' : '✗ Başarısız'"
                                ></span>
                              </div>
                              <template x-if="selectedVideo?.platform_status?.[previewPlatform] === 'published' && selectedVideo?.post_urls?.[previewPlatform]">
                                <a :href="selectedVideo.post_urls[previewPlatform]" target="_blank" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline">
                                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                  Paylaşıma Git
                                </a>
                              </template>
                            </div>
                          </template>
                          
                          <template x-if="editingMetadata">
                            <div class="space-y-3">
                              <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Başlık</label>
                                <input type="text" x-model="metadata.title" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg">
                              </div>
                              <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Açıklama</label>
                                <textarea x-model="metadata.description" rows="3" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg"></textarea>
                              </div>
                              <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Etiketler (virgülle ayırın)</label>
                                <input type="text" x-model="metadata.tags" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg" placeholder="#shorts, #viral">
                              </div>
                              <div class="flex gap-2">
                                <button @click="cancelEditMetadata()" class="flex-1 px-3 py-2 text-xs bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg">İptal</button>
                                <button @click="saveMetadata()" class="flex-1 px-3 py-2 text-xs bg-indigo-600 text-white rounded-lg">Kaydet</button>
                              </div>
                            </div>
                          </template>
                          
                          <!-- Actions -->
                          <div class="pt-3 border-t border-gray-100 dark:border-slate-700 flex gap-2">
                            <button @click="openMoveModal()" class="flex-1 px-3 py-2 text-xs bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition">
                              📦 Taşı
                            </button>
                            <button @click="removeFromQueue(selectedVideo.job_id)" class="flex-1 px-3 py-2 text-xs bg-red-50 dark:bg-red-900/30 text-red-600 rounded-lg hover:bg-red-100 transition">
                              🗑️ Çıkar
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
                
                <!-- Right: Queue Settings -->
                <div class="lg:col-span-3">
                  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                      <h3 class="font-semibold text-gray-800 dark:text-white text-sm">⚙️ Kuyruk Ayarları</h3>
                      <template x-if="!editingQueue">
                        <button @click="toggleQueueEdit()" class="text-xs text-indigo-600 hover:underline">Düzenle</button>
                      </template>
                    </div>
                    
                    <div class="p-3 space-y-3">
                      <template x-if="!editingQueue">
                        <div class="space-y-3">
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Kuyruk Adı</label>
                            <p class="text-sm text-gray-800 dark:text-white font-medium" x-text="selectedQueue?.name"></p>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Platformlar</label>
                            <div class="flex flex-wrap gap-1">
                              <template x-for="p in selectedQueue?.platforms" :key="p">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                        'bg-red-100 dark:bg-red-900/30 text-red-700': p === 'youtube',
                                        'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700': p === 'tiktok',
                                        'bg-pink-100 dark:bg-pink-900/30 text-pink-700': p === 'instagram',
                                        'bg-blue-100 dark:bg-blue-900/30 text-blue-700': p === 'facebook'
                                      }"
                                      x-text="p === 'youtube' ? '📺 YouTube' : p === 'tiktok' ? '🎵 TikTok' : p === 'instagram' ? '📸 Instagram' : '📘 Facebook'"></span>
                              </template>
                            </div>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Zamanlama</label>
                            <p class="text-sm text-gray-800 dark:text-white" x-text="getScheduleText(selectedQueue)"></p>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Durum</label>
                            <span class="px-2 py-1 text-xs rounded-full" 
                                  :class="selectedQueue?.is_active !== false ? 'bg-green-100 dark:bg-green-900/30 text-green-700' : 'bg-gray-100 dark:bg-slate-700 text-gray-500'"
                                  x-text="selectedQueue?.is_active !== false ? '✓ Aktif' : 'Pasif'"></span>
                          </div>
                          <div class="pt-2 border-t border-gray-100 dark:border-slate-700">
                            <div class="flex justify-between text-xs">
                              <span class="text-gray-500 dark:text-gray-400">Bekleyen:</span>
                              <span class="font-medium text-gray-800 dark:text-white" x-text="getPendingCount(selectedQueue)"></span>
                            </div>
                            <div class="flex justify-between text-xs mt-1">
                              <span class="text-gray-500 dark:text-gray-400">Yayınlanan:</span>
                              <span class="font-medium text-green-600" x-text="getPublishedCount(selectedQueue)"></span>
                            </div>
                          </div>
                          <button @click="deleteQueue(selectedQueue)" class="w-full mt-2 px-3 py-2 text-xs bg-red-50 dark:bg-red-900/30 text-red-600 rounded-lg hover:bg-red-100 transition">
                            🗑️ Kuyruğu Sil
                          </button>
                        </div>
                      </template>
                      
                      <template x-if="editingQueue">
                        <div class="space-y-3">
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Kuyruk Adı</label>
                            <input type="text" x-model="form.name" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg">
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Platformlar</label>
                            <div class="grid grid-cols-2 gap-2">
                              <template x-for="p in platformOptions" :key="p.id">
                                <button 
                                  @click="togglePlatform(p.id)"
                                  class="px-2 py-1.5 text-xs rounded-lg border-2 transition"
                                  :class="form.platforms.includes(p.id) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-slate-600'"
                                >
                                  <span x-text="p.icon + ' ' + p.name"></span>
                                </button>
                              </template>
                            </div>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Zamanlama</label>
                            <select x-model="form.scheduleType" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg">
                              <option value="now">⚡ Hemen</option>
                              <option value="interval">⏰ Aralıklı</option>
                              <option value="specific">📅 Belirli Saat</option>
                            </select>
                            <template x-if="form.scheduleType === 'interval'">
                              <select x-model="form.intervalHours" class="w-full mt-2 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg">
                                <option value="1">Her 1 saat</option>
                                <option value="2">Her 2 saat</option>
                                <option value="4">Her 4 saat</option>
                                <option value="6">Her 6 saat</option>
                              </select>
                            </template>
                          </div>
                          <div class="flex gap-2 pt-2">
                            <button @click="toggleQueueEdit()" class="flex-1 px-3 py-2 text-xs bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg">İptal</button>
                            <button @click="saveQueueSettings()" :disabled="submitting" class="flex-1 px-3 py-2 text-xs bg-indigo-600 text-white rounded-lg disabled:opacity-50">
                              <span x-text="submitting ? '...' : 'Kaydet'"></span>
                            </button>
                          </div>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
                
              </div>
              
              <!-- No Queue Selected -->
              <div x-show="!selectedQueue" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Kuyruk Seçin</h3>
                <p class="text-gray-400 dark:text-gray-500">Yukarıdaki tab'lardan bir kuyruk seçin</p>
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
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kuyruk İsmi</label>
            <input 
              type="text" 
              x-model="form.name"
              placeholder="Örn: Komedi Videoları"
              class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            >
          </div>
          
          <!-- Platforms -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platformlar</label>
            <div class="grid grid-cols-2 gap-3">
              <template x-for="platform in platformOptions" :key="platform.id">
                <label 
                  class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition"
                  :class="form.platforms.includes(platform.id) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                >
                  <input 
                    type="checkbox" 
                    :checked="form.platforms.includes(platform.id)"
                    @change="togglePlatform(platform.id)"
                    class="w-5 h-5 text-indigo-600 rounded"
                  >
                  <div>
                    <div class="font-medium text-gray-800 dark:text-white" x-text="platform.icon + ' ' + platform.name"></div>
                  </div>
                </label>
              </template>
            </div>
          </div>
          
          <!-- Schedule Type -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Paylaşım Zamanlaması</label>
            <div class="space-y-2">
              <template x-for="option in scheduleOptions" :key="option.id">
                <label 
                  class="flex items-start gap-3 p-3 border-2 rounded-xl cursor-pointer transition"
                  :class="form.scheduleType === option.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-slate-600 hover:border-gray-300'"
                >
                  <input 
                    type="radio"
                    name="scheduleType"
                    :value="option.id"
                    x-model="form.scheduleType"
                    class="w-5 h-5 text-indigo-600 mt-0.5"
                  >
                  <div>
                    <div class="font-medium text-gray-800 dark:text-white" x-text="option.name"></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="option.desc"></div>
                  </div>
                </label>
              </template>
            </div>
          </div>
          
          <!-- Interval Hours -->
          <template x-if="form.scheduleType === 'interval'">
            <div class="mb-5">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kaç Saatte Bir?</label>
              <select 
                x-model="form.intervalHours"
                class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
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
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Paylaşım Saatleri</label>
              <div class="flex flex-wrap gap-2">
                <template x-for="(time, idx) in form.specificTimes" :key="idx">
                  <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-700 rounded-lg px-3 py-1.5">
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
                  class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition"
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
              class="px-5 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold transition"
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
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
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
              <p class="mt-3 text-gray-500 dark:text-gray-400">Yükleniyor...</p>
            </div>
          </template>
          
          <template x-if="selectedQueue">
            <div>
              <!-- Queue Info -->
              <div class="mb-6 p-4 bg-gray-50 dark:bg-slate-700 rounded-xl">
                <div class="flex flex-wrap gap-2 mb-3">
                  <template x-for="platform in selectedQueue.platforms" :key="platform">
                    <span 
                      class="px-2.5 py-1 rounded-full text-xs font-medium"
                      :class="{
                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': platform === 'youtube',
                        'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400': platform === 'tiktok',
                        'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400': platform === 'instagram',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': platform === 'facebook'
                      }"
                    >
                      <span x-text="platform === 'youtube' ? '📺 YouTube' : platform === 'tiktok' ? '🎵 TikTok' : platform === 'instagram' ? '📸 Instagram' : '📘 Facebook'"></span>
                    </span>
                  </template>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="getScheduleText(selectedQueue)"></p>
              </div>
              
              <!-- Videos List -->
              <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Kuyruktaki Videolar (<span x-text="selectedQueue.videos?.length || 0"></span>)</h4>
              
              <template x-if="!selectedQueue.videos || selectedQueue.videos.length === 0">
                <div class="text-center py-8 bg-gray-50 dark:bg-slate-700 rounded-xl">
                  <p class="text-gray-500 dark:text-gray-400">Bu kuyrukta henüz video yok.</p>
                  <a href="dashboard.php" class="inline-block mt-3 text-indigo-600 dark:text-indigo-400 hover:underline">Videolar sayfasından ekleyin →</a>
                </div>
              </template>
              
              <template x-if="selectedQueue.videos && selectedQueue.videos.length > 0">
                <div class="space-y-3">
                  <template x-for="(video, idx) in selectedQueue.videos" :key="video.job_id">
                    <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-slate-700 rounded-xl">
                      <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center font-bold text-sm" x-text="idx + 1"></div>
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 dark:text-gray-200 truncate" x-text="video.title || 'Video'"></p>
                        <div class="flex flex-wrap gap-1.5 mt-1">
                          <template x-for="(status, platform) in video.platform_status" :key="platform">
                            <span 
                              class="px-1.5 py-0.5 rounded text-xs font-medium"
                              :class="{
                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': status === 'published',
                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': status === 'pending',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': status === 'failed'
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
                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
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
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-white">📦 Videoyu Taşı</h3>
            <button @click="moveModal = false" class="text-white/80 hover:text-white">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            "<span class="font-medium text-gray-800 dark:text-gray-200" x-text="selectedVideo?.title"></span>" videosunu taşımak için hedef kuyruğu seçin:
          </p>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <template x-for="queue in queues.filter(q => q.id !== selectedQueue?.id)" :key="queue.id">
              <button 
                @click="moveVideoToQueue(queue.id)"
                class="w-full p-4 text-left rounded-xl border-2 border-gray-200 dark:border-slate-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-medium text-gray-800 dark:text-gray-200" x-text="queue.name"></p>
                    <div class="flex items-center gap-1 mt-1">
                      <template x-for="p in queue.platforms" :key="p">
                        <span class="text-sm" x-text="p === 'youtube' ? '📺' : p === 'tiktok' ? '🎵' : p === 'instagram' ? '📸' : '📘'"></span>
                      </template>
                    </div>
                  </div>
                  <span class="text-xs text-gray-400 dark:text-gray-500" x-text="(queue.videos?.length || 0) + ' video'"></span>
                </div>
              </button>
            </template>
          </div>
          <button 
            @click="moveModal = false" 
            class="w-full mt-4 px-4 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition"
          >
            İptal
          </button>
        </div>
      </div>
    </div>
  </template>
</body>
</html>
