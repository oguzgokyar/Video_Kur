<?php $page_title = 'Zamanlama - YouTube Shorts Otomasyon'; $active_page = 'scheduler'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <script>
  function schedulerApp() {
    return {
      // App State
      sidebarOpen: false,
      darkMode: localStorage.getItem('darkMode') === '1',
      
      // Scheduler State
      activeTab: 'queue',
      queue: [],
      history: [],
      loading: true,
      autoRefresh: null,
      
      toggleDark() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode ? '1' : '0');
        document.documentElement.classList.toggle('dark', this.darkMode);
      },
      
      async loadQueue() {
        try {
          const r = await fetch('/api/scheduler.php?queue=1');
          const d = await r.json();
          this.queue = d.queue || [];
        } catch(e) {
          console.error('Queue yükleme hatası:', e);
        }
      },
      
      async loadHistory() {
        try {
          const r = await fetch('/api/scheduler.php?history=1&limit=50');
          const d = await r.json();
          this.history = d.history || [];
        } catch(e) {
          console.error('History yükleme hatası:', e);
        }
      },
      
      async cancelSchedule(queueId) {
        if (!confirm('Bu zamanlamayı iptal etmek istediğinizden emin misiniz?')) return;
        
        try {
          const r = await fetch('/api/scheduler.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'cancel', queue_id: queueId})
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.loadQueue();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleString('tr-TR', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      },
      
      formatDateShort(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleString('tr-TR', {
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      },
      
      getStatusBadge(status) {
        const badges = {
          pending: { color: 'gray', label: 'Bekliyor', icon: '⏳' },
          processing: { color: 'blue', label: 'Yükleniyor', icon: '⏫' },
          success: { color: 'green', label: 'Başarılı', icon: '✅' },
          failed: { color: 'red', label: 'Başarısız', icon: '❌' }
        };
        return badges[status] || badges.pending;
      },
      
      isScheduled(scheduledTime) {
        const scheduled = new Date(scheduledTime);
        const now = new Date();
        return scheduled > now;
      },
      
      async init() {
        await this.loadQueue();
        await this.loadHistory();
        this.loading = false;
        
        // Auto-refresh every 10 seconds
        this.autoRefresh = setInterval(() => {
          if (this.activeTab === 'queue') {
            this.loadQueue();
          }
        }, 10000);
      },
      
      destroy() {
        clearInterval(this.autoRefresh);
      }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="schedulerApp()" x-init="init()" @destroy.window="destroy()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-6xl mx-auto">
          
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">📅 Yükleme Zamanlaması</h1>
              <p class="text-gray-600 mt-1">YouTube video yüklemelerini zamanlayın ve takip edin</p>
            </div>
          </div>

          <!-- Loading -->
          <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Yükleniyor...</p>
          </div>

          <!-- Tabs -->
          <div x-show="!loading">
            <div class="border-b border-gray-200 mb-6">
              <nav class="-mb-px flex space-x-8">
                <button 
                  @click="activeTab = 'queue'; loadQueue()"
                  :class="activeTab === 'queue' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                  class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                  📋 Zamanlama Kuyruğu
                  <span x-show="queue.length > 0" class="ml-2 bg-blue-100 text-blue-800 py-0.5 px-2 rounded-full text-xs" x-text="queue.length"></span>
                </button>
                
                <button 
                  @click="activeTab = 'history'; loadHistory()"
                  :class="activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                  class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                  📜 Yükleme Geçmişi
                </button>
                
                <button 
                  @click="activeTab = 'settings'"
                  :class="activeTab === 'settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                  class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                  ⚙️ Otomatik Zamanlama
                </button>
              </nav>
            </div>

            <!-- Queue Tab -->
            <div x-show="activeTab === 'queue'" class="space-y-4">
              
              <!-- Empty State -->
              <template x-if="queue.length === 0">
                <div class="bg-white rounded-lg shadow p-8 text-center">
                  <div class="text-6xl mb-4">📭</div>
                  <h3 class="text-xl font-semibold text-gray-900 mb-2">Bekleyen Yükleme Yok</h3>
                  <p class="text-gray-600">Dashboard'dan video oluşturup zamanlayabilirsiniz</p>
                </div>
              </template>

              <!-- Queue Items -->
              <template x-for="item in queue" :key="item.queue_id">
                <div class="bg-white rounded-lg shadow p-6">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="item.metadata.title"></h3>
                      
                      <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                        <div>
                          <span class="font-medium">📅 Zamanlanan Tarih:</span>
                          <span x-text="formatDate(item.scheduled_time)"></span>
                        </div>
                        <div>
                          <span class="font-medium">🆔 Job ID:</span>
                          <span x-text="item.job_id"></span>
                        </div>
                      </div>
                      
                      <div class="flex items-center space-x-2">
                        <template x-data="{ badge: getStatusBadge(item.status) }">
                          <span 
                            :class="`bg-${badge.color}-100 text-${badge.color}-800`"
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                          >
                            <span x-text="badge.icon"></span>
                            <span class="ml-1" x-text="badge.label"></span>
                          </span>
                        </template>
                        
                        <span 
                          x-show="item.retry_count > 0"
                          class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800"
                        >
                          🔄 Deneme <span x-text="item.retry_count"></span>
                        </span>
                      </div>
                      
                      <div x-show="item.last_error" class="mt-3 p-3 bg-red-50 rounded text-sm text-red-800">
                        <strong>Hata:</strong> <span x-text="item.last_error"></span>
                      </div>
                    </div>
                    
                    <div class="ml-4">
                      <button 
                        @click="cancelSchedule(item.queue_id)"
                        class="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 text-red-700 rounded"
                      >
                        ❌ İptal
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- History Tab -->
            <div x-show="activeTab === 'history'" class="space-y-4">
              
              <!-- Empty State -->
              <template x-if="history.length === 0">
                <div class="bg-white rounded-lg shadow p-8 text-center">
                  <div class="text-6xl mb-4">📜</div>
                  <h3 class="text-xl font-semibold text-gray-900 mb-2">Yükleme Geçmişi Yok</h3>
                  <p class="text-gray-600">Henüz video yüklemesi yapılmadı</p>
                </div>
              </template>

              <!-- History Items -->
              <template x-for="item in history" :key="item.queue_id">
                <div class="bg-white rounded-lg shadow p-6">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-2 mb-2">
                        <template x-data="{ badge: getStatusBadge(item.status) }">
                          <span 
                            :class="`bg-${badge.color}-100 text-${badge.color}-800`"
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                          >
                            <span x-text="badge.icon"></span>
                            <span class="ml-1" x-text="badge.label"></span>
                          </span>
                        </template>
                        <span class="text-sm text-gray-500" x-text="formatDateShort(item.uploaded_at || item.created_at)"></span>
                      </div>
                      
                      <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="item.metadata.title"></h3>
                      
                      <div x-show="item.status === 'success'" class="mt-2">
                        <a 
                          :href="item.video_url" 
                          target="_blank"
                          class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm"
                        >
                          🔗 YouTube'da Aç →
                        </a>
                      </div>
                      
                      <div x-show="item.status === 'failed'" class="mt-2 p-3 bg-red-50 rounded text-sm text-red-800">
                        <strong>Hata:</strong> <span x-text="item.error || item.last_error"></span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Settings Tab -->
            <div x-show="activeTab === 'settings'">
              <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🤖 Otomatik Zamanlama Ayarları</h3>
                <p class="text-sm text-gray-600 mb-6">Yeni videolar üretildikçe otomatik olarak zamanlanacaktır.</p>
                
                <div class="space-y-6">
                  <div class="flex items-center">
                    <input type="checkbox" id="auto-schedule" class="mr-3 h-5 w-5" />
                    <label for="auto-schedule" class="text-sm font-medium text-gray-900">Otomatik zamanlama aktif</label>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Günlük yükleme sayısı</label>
                    <input 
                      type="number" 
                      min="1" 
                      max="10" 
                      value="2"
                      class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Tercih edilen saatler</label>
                    <div class="grid grid-cols-6 gap-2">
                      <template x-for="hour in [9, 12, 14, 17, 19, 20, 21, 22]" :key="hour">
                        <label class="flex items-center space-x-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                          <input type="checkbox" :value="hour" checked class="form-checkbox" />
                          <span class="text-sm" x-text="hour + ':00'"></span>
                        </label>
                      </template>
                    </div>
                  </div>
                  
                  <div class="flex items-center">
                    <input type="checkbox" id="weekend" class="mr-3 h-5 w-5" checked />
                    <label for="weekend" class="text-sm font-medium text-gray-900">Hafta sonu yükle</label>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Zamanlama stratejisi</label>
                    <div class="space-y-2">
                      <label class="flex items-center space-x-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="strategy" value="smart" checked class="form-radio" />
                        <div>
                          <div class="font-medium">🎯 Akıllı</div>
                          <div class="text-sm text-gray-600">En yüksek trafik saatlerinde yükle</div>
                        </div>
                      </label>
                      
                      <label class="flex items-center space-x-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="strategy" value="fixed" class="form-radio" />
                        <div>
                          <div class="font-medium">⏰ Sabit</div>
                          <div class="text-sm text-gray-600">Belirli saatlerde yükle</div>
                        </div>
                      </label>
                      
                      <label class="flex items-center space-x-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="strategy" value="random" class="form-radio" />
                        <div>
                          <div class="font-medium">🎲 Rastgele</div>
                          <div class="text-sm text-gray-600">Gün içinde rastgele dağıt</div>
                        </div>
                      </label>
                    </div>
                  </div>
                  
                  <div class="flex justify-end pt-4 border-t">
                    <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                      💾 Ayarları Kaydet
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>
</body>
</html>
