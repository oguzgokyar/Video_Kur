<?php $page_title = 'YouTube Yönetimi - YouTube Shorts Otomasyon'; $active_page = 'youtube'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <script>
  function youtubeApp() {
    return {
      channels: [],
      loading: true,
      authenticating: false,
      
      async loadChannels() {
        try {
          const r = await fetch('/api/youtube.php?action=channels');
          const d = await r.json();
          this.channels = d.channels || [];
        } catch(e) {
          console.error('Kanal yükleme hatası:', e);
        }
        this.loading = false;
      },
      
      async authenticateChannel() {
        if (this.authenticating) return;
        
        if (!confirm('YouTube hesabınızı bağlamak için tarayıcınızda yeni bir pencere açılacak. Devam etmek istiyor musunuz?')) {
          return;
        }
        
        this.authenticating = true;
        
        try {
          const r = await fetch('/api/youtube.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'auth'})
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.channels = d.channels || [];
          } else {
            alert('❌ ' + (d.error || 'Kimlik doğrulama başarısız'));
            console.error(d.output);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
        
        this.authenticating = false;
      },
      
      async disconnectChannel(channelId) {
        if (!confirm('Bu kanalın bağlantısını kesmek istediğinizden emin misiniz?')) return;
        
        try {
          const r = await fetch('/api/youtube.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'disconnect', channel_id: channelId})
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.loadChannels();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      async setDefaultChannel(channelId) {
        try {
          const r = await fetch('/api/youtube.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'set_default', channel_id: channelId})
          });
          
          const d = await r.json();
          
          if (d.success) {
            this.loadChannels();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      formatNumber(num) {
        if (num >= 1000000) return (num/1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num/1000).toFixed(1) + 'K';
        return num.toString();
      },
      
      init() {
        this.loadChannels();
      }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="youtubeApp()" x-init="init()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-5xl mx-auto">
          
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">📺 YouTube Yönetimi</h1>
              <p class="text-gray-600 mt-1">YouTube hesaplarınızı bağlayın ve yönetin</p>
            </div>
          </div>

          <!-- Loading -->
          <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Yükleniyor...</p>
          </div>

          <!-- No Channels -->
          <template x-if="!loading && channels.length === 0">
            <div class="bg-white rounded-lg shadow p-8 text-center">
              <div class="text-6xl mb-4">🔗</div>
              <h3 class="text-xl font-semibold text-gray-900 mb-2">Bağlı Hesap Yok</h3>
              <p class="text-gray-600 mb-6">YouTube hesabınızı bağlayarak video yüklemeye başlayın</p>
              
              <button 
                @click="authenticateChannel()"
                :disabled="authenticating"
                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
              >
                <span x-show="!authenticating">➕ YouTube Hesabı Bağla</span>
                <span x-show="authenticating">⏳ Bağlanıyor...</span>
              </button>
              
              <div class="mt-6 p-4 bg-yellow-50 rounded-lg text-left">
                <h4 class="font-semibold text-yellow-900 mb-2">⚠️ Önemli Notlar:</h4>
                <ul class="text-sm text-yellow-800 space-y-1">
                  <li>• Google Cloud Console'dan <code>client_secrets.json</code> dosyasını <code>data/youtube_credentials/</code> klasörüne koymanız gerekir</li>
                  <li>• YouTube Data API v3'ü aktif etmeniz gerekir</li>
                  <li>• OAuth 2.0 credentials oluşturmanız gerekir</li>
                  <li>• İlk bağlantıda tarayıcınızda yeni pencere açılacaktır</li>
                </ul>
              </div>
            </div>
          </template>

          <!-- Channels List -->
          <div x-show="!loading && channels.length > 0" class="space-y-4">
            
            <!-- Add New Channel Button -->
            <div class="flex justify-end">
              <button 
                @click="authenticateChannel()"
                :disabled="authenticating"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm inline-flex items-center"
              >
                <span x-show="!authenticating">➕ Yeni Hesap Bağla</span>
                <span x-show="authenticating">⏳ Bağlanıyor...</span>
              </button>
            </div>

            <!-- Channel Cards -->
            <template x-for="channel in channels" :key="channel.channel_id">
              <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                  <div class="flex items-center space-x-4">
                    <img 
                      :src="channel.thumbnail" 
                      :alt="channel.channel_title"
                      class="w-16 h-16 rounded-full"
                    />
                    <div>
                      <h3 class="text-lg font-semibold text-gray-900" x-text="channel.channel_title"></h3>
                      <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                        <span>📊 <span x-text="formatNumber(channel.subscriber_count)"></span> abone</span>
                        <span>🎬 <span x-text="channel.video_count"></span> video</span>
                      </div>
                      <div class="mt-2">
                        <span 
                          x-show="channel.is_default" 
                          class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"
                        >
                          ✅ Varsayılan Kanal
                        </span>
                        <span 
                          x-show="channel.is_active && !channel.is_default" 
                          class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800"
                        >
                          ✓ Aktif
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <button 
                      x-show="!channel.is_default"
                      @click="setDefaultChannel(channel.channel_id)"
                      class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded"
                      title="Varsayılan yap"
                    >
                      ⭐ Varsayılan Yap
                    </button>
                    <button 
                      @click="disconnectChannel(channel.channel_id)"
                      class="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 text-red-700 rounded"
                      title="Bağlantıyı kes"
                    >
                      ❌ Bağlantıyı Kes
                    </button>
                  </div>
                </div>
              </div>
            </template>
            
            <!-- Default Upload Settings (Future Enhancement) -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">⚙️ Varsayılan Yükleme Ayarları</h3>
              <p class="text-sm text-gray-600 mb-4">Bu ayarlar yeni video yüklemelerinde varsayılan olarak kullanılacaktır.</p>
              
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Gizlilik</label>
                  <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="public" selected>Public (Herkese açık)</option>
                    <option value="unlisted">Unlisted (Link bilen izleyebilir)</option>
                    <option value="private">Private (Sadece siz)</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                  <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="28" selected>Science & Technology</option>
                    <option value="22">People & Blogs</option>
                    <option value="25">News & Politics</option>
                    <option value="24">Entertainment</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Varsayılan Tags</label>
                  <input 
                    type="text" 
                    value="#Shorts, #Haber, #Teknoloji, #Türkçe"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="virgülle ayırın"
                  />
                </div>
                
                <div class="flex items-center">
                  <input type="checkbox" id="notify" class="mr-2" checked />
                  <label for="notify" class="text-sm text-gray-700">Abonelere bildirim gönder</label>
                </div>
                
                <div class="flex justify-end">
                  <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                    💾 Kaydet
                  </button>
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
