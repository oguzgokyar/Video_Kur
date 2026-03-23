<?php $page_title = 'Videolar - Video Otomasyon'; $active_page = 'videos'; ?>
<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === '1' }" :class="{ 'dark': darkMode }">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <style>
    @keyframes pulse-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(1.4); } }
    @keyframes spin-slow { to { transform: rotate(360deg); } }
    @keyframes progress-bar { 0% { background-position: 0 0; } 100% { background-position: 40px 0; } }
    @keyframes fade-in-up { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .anim-pulse-dot { animation: pulse-dot 1.2s ease-in-out infinite; }
    .anim-spin { animation: spin-slow 1.5s linear infinite; }
    .anim-fade-in { animation: fade-in-up .4s ease-out both; }
    .progress-stripe {
      background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent);
      background-size: 40px 40px;
      animation: progress-bar 1s linear infinite;
    }
  </style>
  <script>
  function dashApp() {
    const statusMeta = {
      pending:    { label:'Bekliyor',       icon:'clock',    color:'gray',   step:0 },
      scraping:   { label:'Haber Çekiliyor', icon:'globe',   color:'blue',   step:1 },
      scripting:  { label:'Script Yazılıyor',icon:'pencil',  color:'indigo', step:2 },
      imaging:    { label:'Görseller',       icon:'photo',   color:'purple', step:3 },
      tts:        { label:'Seslendirme',     icon:'mic',     color:'pink',   step:4 },
      subtitling: { label:'Altyazı',         icon:'caption', color:'orange', step:5 },
      composing:  { label:'Video Birleştirme',icon:'film',   color:'amber',  step:6 },
      done:       { label:'Tamamlandı',      icon:'check',   color:'green',  step:7 },
      failed:     { label:'Hata',            icon:'x',       color:'red',    step:-1 },
      paused:     { label:'Duraklatıldı',    icon:'pause',   color:'yellow', step:-2 }
    };
    const totalSteps = 7;

    return {
      sidebarOpen: false, 
      sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
      darkMode: false, 
      jobs: [], 
      loading: true, 
      autoRefresh: null,
      videoPopup: null,
      queues: [],
      queueModal: null,
      selectedQueueId: '',
      addingToQueue: false,
      productionFilter: 'waiting',
      queueFilter: 'all',
      statusMeta,
      getMeta(s) { return statusMeta[s] || statusMeta.pending; },
      isActive(s) { return !['done','failed','pending','paused'].includes(s); },
      isPaused(s) { return s === 'paused'; },
      isProductionDone(job) {
        const s = (job?.status || '').toLowerCase();
        return s === 'done' || s === 'completed';
      },
      isProductionWaiting(job) {
        return !this.isProductionDone(job);
      },
      get filteredJobs() {
        let items = [...this.jobs];

        if (this.productionFilter === 'waiting') {
          items = items.filter(job => this.isProductionWaiting(job));
        } else if (this.productionFilter === 'done') {
          items = items.filter(job => this.isProductionDone(job));
        }

        if (this.queueFilter === 'in_queue') {
          items = items.filter(job => !!job.queue_status?.queue_id);
        } else if (this.queueFilter === 'no_queue') {
          items = items.filter(job => !job.queue_status?.queue_id);
        } else if (this.queueFilter !== 'all') {
          items = items.filter(job => (job.queue_status?.queue_id || '') === this.queueFilter);
        }

        return items;
      },
      progressPercent(s) {
        const m = this.getMeta(s);
        if (s === 'done') return 100;
        if (s === 'failed' || s === 'pending' || s === 'paused') return 0;
        return Math.round((m.step / totalSteps) * 100);
      },
      async pauseJob(jobId) {
        try {
          await fetch('/api/jobs.php', { method:'PATCH', headers:{'Content-Type':'application/json'}, body:JSON.stringify({jobId, action:'pause'}) });
          this.loadJobs();
        } catch(e) {}
      },
      async resumeJob(jobId) {
        try {
          await fetch('/api/jobs.php', { method:'PATCH', headers:{'Content-Type':'application/json'}, body:JSON.stringify({jobId, action:'resume'}) });
          this.loadJobs();
        } catch(e) {}
      },
      async deleteJob(jobId) {
        if (!confirm('Bu işi silmek istediğinizden emin misiniz? Tüm içerik kalıcı olarak silinecek.')) return;
        try {
          await fetch('/api/jobs.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({jobId}) });
          this.loadJobs();
        } catch(e) { alert('Silme hatası: ' + e.message); }
      },
      
      // Kuyruğa Ekleme
      async openQueueModal(job) {
        this.queueModal = job;
        this.selectedQueueId = '';
        await this.loadQueues();
      },
      
      closeQueueModal() {
        this.queueModal = null;
        this.selectedQueueId = '';
      },
      
      async loadQueues() {
        try {
          const r = await fetch('/api/queues.php?action=list');
          const d = await r.json();
          this.queues = d.queues || [];
        } catch(e) {
          console.error('Kuyruk listesi yüklenemedi:', e);
        }
      },
      
      async addToQueue() {
        if (!this.selectedQueueId) {
          alert('Lütfen bir kuyruk seçin!');
          return;
        }
        
        this.addingToQueue = true;
        
        try {
          const response = await fetch('/api/queues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'add_video',
              queue_id: this.selectedQueueId,
              job_id: this.queueModal.id
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            const queue = this.queues.find(q => q.id === this.selectedQueueId);
            alert('✅ Video "' + (queue?.name || 'Kuyruk') + '" kuyruğuna eklendi!');
            this.closeQueueModal();
            this.loadJobs();
          } else {
            alert('❌ Hata: ' + (result.error || 'Bilinmeyen hata'));
          }
        } catch (error) {
          alert('❌ Hata: ' + error.message);
        } finally {
          this.addingToQueue = false;
        }
      },
      
      toggleDark() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode ? '1' : '0');
        document.documentElement.classList.toggle('dark', this.darkMode);
      },
      async loadJobs() {
        try {
          const r = await fetch('/api/jobs.php?list=1');
          const d = await r.json();
          this.jobs = d.jobs || [];
        } catch(e) {}
        this.loading = false;
      },
      init() {
        this.darkMode = localStorage.getItem('darkMode') === '1';
        this.loadJobs();
        this.autoRefresh = setInterval(() => this.loadJobs(), 3000);
      },
      destroy() { clearInterval(this.autoRefresh); }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="dashApp()" x-init="init()" @destroy.window="destroy()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-5xl mx-auto">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Videolar</h1>
            <span class="text-sm text-gray-500">
              <span x-text="filteredJobs.length"></span> / <span x-text="jobs.length"></span> gösteriliyor
            </span>
          </div>

          <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Üretim Durumu</label>
              <select x-model="productionFilter" class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm">
                <option value="waiting">Üretimi Bekleyen (Varsayılan)</option>
                <option value="done">Üretimi Tamamlanan</option>
                <option value="all">Tümü</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Kuyruk</label>
              <select x-model="queueFilter" class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm">
                <option value="all">Tüm Kuyruklar</option>
                <option value="in_queue">Kuyruğa Dahil</option>
                <option value="no_queue">Kuyruğa Dahil Değil</option>
                <template x-for="queue in queues" :key="'filter-' + queue.id">
                  <option :value="queue.id" x-text="queue.name"></option>
                </template>
              </select>
            </div>
          </div>

          <template x-if="loading">
            <div class="flex items-center justify-center py-16">
              <svg class="w-8 h-8 text-blue-500 anim-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              <span class="ml-3 text-gray-500 font-medium">Yükleniyor...</span>
            </div>
          </template>

          <template x-if="!loading && filteredJobs.length === 0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
              <p class="text-gray-400 text-lg mb-2">Seçili filtreye uygun video yok.</p>
              <a href="create.php" class="inline-flex items-center gap-2 mt-3 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                İlk Videoyu Oluştur
              </a>
            </div>
          </template>

          <template x-if="!loading && filteredJobs.length > 0">
            <div class="space-y-4">
              <template x-for="(job, ji) in filteredJobs" :key="job.id">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden anim-fade-in"
                     :class="{'ring-2 ring-blue-400 ring-offset-2': isActive(job.status), 'ring-2 ring-yellow-400 ring-offset-2': isPaused(job.status)}">

                  <div class="flex items-center justify-between px-5 py-4">
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                           :class="{
                             'bg-gray-100 text-gray-400': job.status==='pending',
                             'bg-blue-100 text-blue-600': ['scraping','scripting'].includes(job.status),
                             'bg-purple-100 text-purple-600': job.status==='imaging',
                             'bg-pink-100 text-pink-600': job.status==='tts',
                             'bg-orange-100 text-orange-600': job.status==='subtitling',
                             'bg-amber-100 text-amber-600': job.status==='composing',
                             'bg-green-100 text-green-600': job.status==='done',
                             'bg-red-100 text-red-600': job.status==='failed',
                             'bg-yellow-100 text-yellow-600': job.status==='paused'
                           }">
                        <template x-if="isActive(job.status)">
                          <svg class="w-5 h-5 anim-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        <template x-if="job.status==='done'">
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="job.status==='failed'">
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </template>
                        <template x-if="job.status==='pending'">
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <template x-if="job.status==='paused'">
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6"/></svg>
                        </template>
                      </div>

                      <div class="min-w-0">
                        <div class="flex items-center gap-2">
                          <span class="font-semibold text-sm text-gray-800 truncate" x-text="getMeta(job.status).label"></span>
                          <template x-if="isActive(job.status)">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-blue-500 text-white">
                              <span class="w-1.5 h-1.5 bg-white rounded-full anim-pulse-dot"></span> Aktif
                            </span>
                          </template>
                          <template x-if="isPaused(job.status)">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-yellow-500 text-white">
                              Duraklatıldı
                            </span>
                          </template>
                        </div>
                        <p class="text-sm font-medium text-gray-700 truncate max-w-md" x-text="job.title || 'Video'"></p>
                        <p class="text-xs text-gray-400 truncate max-w-xs" x-text="job.url"></p>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                      <span class="text-xs text-gray-400 hidden sm:block" x-text="job.created_at"></span>
                      <template x-if="isActive(job.status)">
                        <button @click="pauseJob(job.id)" class="inline-flex items-center gap-1.5 px-3 py-2 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded-lg text-sm font-semibold transition" title="Duraklat">
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6"/></svg>
                          Duraklat
                        </button>
                      </template>
                      <template x-if="isPaused(job.status)">
                        <button @click="resumeJob(job.id)" class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm font-semibold transition" title="Devam Et">
                          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                          Devam Et
                        </button>
                      </template>
                      <a :href="'project.php?id=' + job.id" class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Detaylar
                      </a>
                      <template x-if="job.status==='done' && job.previewUrl">
                        <button @click="videoPopup = job.previewUrl" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">
                          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                          İzle
                        </button>
                      </template>
                      
                      <!-- Kuyruğa Ekle Butonu -->
                      <template x-if="job.status === 'done' && job.previewUrl">
                        <button 
                          @click="openQueueModal(job)" 
                          class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg text-sm font-semibold transition shadow-sm"
                          title="Kuyruğa Ekle"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                          Kuyruğa Ekle
                        </button>
                      </template>
                      
                      <!-- Kuyruk Durumu -->
                      <template x-if="job.queue_status">
                        <span 
                          class="px-3 py-1.5 rounded-lg text-xs font-semibold"
                          :class="{
                            'bg-blue-100 text-blue-700': job.queue_status.status === 'queued',
                            'bg-green-100 text-green-700': job.queue_status.status === 'published',
                            'bg-yellow-100 text-yellow-700': job.queue_status.status === 'publishing'
                          }"
                        >
                          📋 <span x-text="job.queue_status.queue_name"></span>
                        </span>
                      </template>
                      
                      <button @click="deleteJob(job.id)" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-semibold transition" title="Sil">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </div>

                  <template x-if="isActive(job.status)">
                    <div class="px-5 pb-4">
                      <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold text-gray-500">İlerleme</span>
                        <span class="text-[11px] font-bold text-blue-600" x-text="progressPercent(job.status) + '%'"></span>
                      </div>
                      <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 progress-stripe transition-all duration-700 ease-out"
                             :style="'width:' + progressPercent(job.status) + '%'"></div>
                      </div>
                      <div class="flex justify-between mt-2">
                        <template x-for="(st, si) in ['scraping','scripting','imaging','tts','subtitling','composing','done']" :key="si">
                          <div class="flex flex-col items-center gap-0.5">
                            <div class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                                 :class="{
                                   'bg-blue-500 shadow-md shadow-blue-200': getMeta(job.status).step >= getMeta(st).step && getMeta(job.status).step > 0,
                                   'bg-blue-500 anim-pulse-dot': job.status === st,
                                   'bg-gray-200': getMeta(job.status).step < getMeta(st).step
                                 }"></div>
                            <span class="text-[9px] text-gray-400 hidden lg:block" x-text="getMeta(st).label.split(' ')[0]"></span>
                          </div>
                        </template>
                      </div>
                    </div>
                  </template>

                  <template x-if="job.status==='failed' && job.error">
                    <div class="mx-5 mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                      <p class="text-xs text-red-600 font-medium truncate" x-text="job.error"></p>
                    </div>
                  </template>

                </div>
              </template>
            </div>
          </template>
        </div>
      </main>
    </div>

    <?php include __DIR__ . '/components/_footer.php'; ?>
  </div>

  <template x-if="videoPopup">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80" @click.self="videoPopup = null">
      <div class="relative bg-black rounded-2xl shadow-2xl flex flex-col items-center max-w-sm w-full mx-4" style="max-height:90vh">
        <button @click="videoPopup = null" class="absolute -top-4 -right-4 z-10 w-9 h-9 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-gray-100 transition">
          <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <video :src="videoPopup" controls autoplay class="rounded-2xl w-full" style="max-height:80vh;aspect-ratio:9/16;background:#000"></video>
      </div>
    </div>
  </template>

  <!-- Kuyruğa Ekle Modal -->
  <template x-if="queueModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="closeQueueModal()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-white">📋 Kuyruğa Ekle</h3>
            <button @click="closeQueueModal()" class="text-white/80 hover:text-white">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <p class="text-white/80 text-sm mt-1 truncate" x-text="queueModal.title || 'Video'"></p>
        </div>
        
        <div class="p-6">
          <template x-if="queues.length === 0">
            <div class="text-center py-8">
              <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              <p class="text-gray-500 mb-4">Henüz kuyruk oluşturulmamış.</p>
              <a href="queues.php" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Kuyruk Oluştur
              </a>
            </div>
          </template>
          
          <template x-if="queues.length > 0">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Kuyruk Seçin</label>
              <select 
                x-model="selectedQueueId"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              >
                <option value="">-- Kuyruk seçin --</option>
                <template x-for="queue in queues" :key="queue.id">
                  <option :value="queue.id">
                    <span x-text="queue.name"></span> 
                    (<span x-text="queue.platforms.join(', ')"></span>)
                  </option>
                </template>
              </select>
              
              <!-- Seçili Kuyruk Bilgisi -->
              <template x-if="selectedQueueId">
                <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                  <template x-for="queue in queues.filter(q => q.id === selectedQueueId)" :key="queue.id">
                    <div>
                      <div class="flex items-center gap-2 mb-2">
                        <span class="font-semibold text-gray-800" x-text="queue.name"></span>
                      </div>
                      <div class="flex flex-wrap gap-2 mb-2">
                        <template x-for="platform in queue.platforms" :key="platform">
                          <span class="px-2 py-1 rounded-full text-xs font-medium"
                                :class="{
                                  'bg-red-100 text-red-700': platform === 'youtube',
                                  'bg-cyan-100 text-cyan-700': platform === 'tiktok',
                                  'bg-pink-100 text-pink-700': platform === 'instagram',
                                  'bg-blue-100 text-blue-700': platform === 'facebook'
                                }">
                            <span x-text="platform === 'youtube' ? '📺 YouTube' : platform === 'tiktok' ? '🎵 TikTok' : platform === 'instagram' ? '📸 Instagram' : '📘 Facebook'"></span>
                          </span>
                        </template>
                      </div>
                      <p class="text-xs text-gray-500">
                        <span x-text="queue.schedule?.type === 'now' ? '⚡ Hemen paylaş' : queue.schedule?.type === 'interval' ? '⏰ Her ' + queue.schedule.interval_hours + ' saatte bir' : '📅 Belirli saatlerde'"></span>
                        • <span x-text="(queue.videos?.length || 0) + ' video kuyrukta'"></span>
                      </p>
                    </div>
                  </template>
                </div>
              </template>
              
              <div class="flex justify-end gap-3 mt-6">
                <button @click="closeQueueModal()" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold transition">
                  İptal
                </button>
                <button 
                  @click="addToQueue()" 
                  :disabled="addingToQueue || !selectedQueueId"
                  class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg font-semibold transition disabled:opacity-50"
                >
                  <span x-show="!addingToQueue">📋 Kuyruğa Ekle</span>
                  <span x-show="addingToQueue">⏳ Ekleniyor...</span>
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </template>
</body>
</html>
