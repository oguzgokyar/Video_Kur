<?php $page_title = 'Kuyruklar - Video Otomasyon'; $active_page = 'queues'; ?>
<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === '1' }" :class="{ 'dark': darkMode }">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <style>
    @keyframes fade-in-up { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .anim-fade-in { animation: fade-in-up .4s ease-out both; }
  </style>
  <script>
  function queuesApp() {
    return {
      sidebarOpen: false,
      darkMode: false,
      loading: true,
      queues: [],
      selectedQueue: null,
      
      // Modal states
      createModal: false,
      editModal: false,
      detailModal: false,
      
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
        this.selectedQueue = null;
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
            // Reload detail
            this.openDetailModal({id: this.selectedQueue.id});
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
            <div class="grid gap-4">
              <template x-for="queue in queues" :key="queue.id">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden anim-fade-in hover:shadow-md transition">
                  <div class="p-5">
                    <div class="flex items-start justify-between">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                          <h3 class="text-lg font-bold text-gray-800" x-text="queue.name"></h3>
                          <span 
                            class="px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="queue.is_active !== false ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            x-text="queue.is_active !== false ? 'Aktif' : 'Pasif'"
                          ></span>
                        </div>
                        
                        <!-- Platforms -->
                        <div class="flex flex-wrap gap-2 mb-3">
                          <template x-for="platform in queue.platforms" :key="platform">
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
                        
                        <!-- Info -->
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                          <span x-text="getScheduleText(queue)"></span>
                          <span>•</span>
                          <span><strong x-text="getPendingCount(queue)"></strong> bekliyor</span>
                          <span>•</span>
                          <span><strong x-text="getPublishedCount(queue)"></strong> yayınlandı</span>
                        </div>
                      </div>
                      
                      <!-- Actions -->
                      <div class="flex items-center gap-2 ml-4">
                        <button 
                          @click="openDetailModal(queue)"
                          class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                          title="Detaylar"
                        >
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button 
                          @click="openEditModal(queue)"
                          class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                          title="Düzenle"
                        >
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button 
                          @click="deleteQueue(queue)"
                          class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                          title="Sil"
                        >
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
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
</body>
</html>
