<?php $page_title = 'Hesaplar - YouTube Shorts Otomasyon'; $active_page = 'accounts'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <style>
    .platform-tab {
      transition: all 0.2s ease;
    }
    .platform-tab.active {
      border-bottom: 3px solid currentColor;
    }
    .platform-icon {
      width: 24px;
      height: 24px;
    }
  </style>
  <script>
  function accountsApp() {
    return {
      // App State
      sidebarOpen: false,
      darkMode: localStorage.getItem('darkMode') === '1',
      activeTab: 'youtube',
      
      // YouTube State
      youtubeChannels: [],
      youtubeLoading: true,
      youtubeAuthenticating: false,
      
      // Instagram State
      instagramAccounts: [],
      instagramLoading: true,
      instagramAuthenticating: false,
      
      // TikTok State
      tiktokAccounts: [],
      tiktokLoading: true,
      tiktokAuthenticating: false,
      
      // ==================== YOUTUBE ====================
      async loadYoutubeChannels() {
        this.youtubeLoading = true;
        try {
          const r = await fetch('/api/youtube.php?action=channels');
          const d = await r.json();
          this.youtubeChannels = d.channels || [];
        } catch(e) {
          console.error('YouTube kanal yükleme hatası:', e);
        }
        this.youtubeLoading = false;
      },
      
      async authenticateYoutube() {
        if (this.youtubeAuthenticating) return;
        
        if (!confirm('YouTube hesabınızı bağlamak için tarayıcınızda yeni bir pencere açılacak. Devam etmek istiyor musunuz?')) {
          return;
        }
        
        this.youtubeAuthenticating = true;
        
        try {
          const r = await fetch('/api/youtube.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'auth'})
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.youtubeChannels = d.channels || [];
          } else {
            alert('❌ ' + (d.error || 'Kimlik doğrulama başarısız'));
            console.error(d.output);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
        
        this.youtubeAuthenticating = false;
      },
      
      async disconnectYoutube(channelId) {
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
            this.loadYoutubeChannels();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      async setDefaultYoutubeChannel(channelId) {
        try {
          const r = await fetch('/api/youtube.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'set_default', channel_id: channelId})
          });
          
          const d = await r.json();
          
          if (d.success) {
            this.loadYoutubeChannels();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      // ==================== INSTAGRAM ====================
      async loadInstagramAccounts() {
        this.instagramLoading = true;
        try {
          const r = await fetch('/api/accounts.php?action=list&platform=instagram');
          const d = await r.json();
          this.instagramAccounts = d.accounts || [];
        } catch(e) {
          console.error('Instagram hesap yükleme hatası:', e);
        }
        this.instagramLoading = false;
      },
      
      async authenticateInstagram() {
        if (this.instagramAuthenticating) return;
        
        // Instagram OAuth için kullanıcıdan bilgi al
        const username = prompt('Instagram kullanıcı adınızı girin:');
        if (!username) return;
        
        this.instagramAuthenticating = true;
        
        try {
          const r = await fetch('/api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              action: 'connect',
              platform: 'instagram',
              username: username
            })
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.loadInstagramAccounts();
          } else {
            alert('❌ ' + (d.error || 'Bağlantı başarısız'));
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
        
        this.instagramAuthenticating = false;
      },
      
      async disconnectInstagram(accountId) {
        if (!confirm('Bu Instagram hesabının bağlantısını kesmek istediğinizden emin misiniz?')) return;
        
        try {
          const r = await fetch('/api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              action: 'disconnect',
              platform: 'instagram',
              account_id: accountId
            })
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.loadInstagramAccounts();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      async setDefaultInstagram(accountId) {
        try {
          const r = await fetch('/api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              action: 'set_default',
              platform: 'instagram',
              account_id: accountId
            })
          });
          
          const d = await r.json();
          
          if (d.success) {
            this.loadInstagramAccounts();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      // ==================== TIKTOK ====================
      async loadTiktokAccounts() {
        this.tiktokLoading = true;
        try {
          const r = await fetch('/api/accounts.php?action=list&platform=tiktok');
          const d = await r.json();
          this.tiktokAccounts = d.accounts || [];
        } catch(e) {
          console.error('TikTok hesap yükleme hatası:', e);
        }
        this.tiktokLoading = false;
      },
      
      async authenticateTiktok() {
        if (this.tiktokAuthenticating) return;
        
        // TikTok OAuth için kullanıcıdan bilgi al
        const username = prompt('TikTok kullanıcı adınızı girin (@username):');
        if (!username) return;
        
        this.tiktokAuthenticating = true;
        
        try {
          const r = await fetch('/api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              action: 'connect',
              platform: 'tiktok',
              username: username.replace('@', '')
            })
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.loadTiktokAccounts();
          } else {
            alert('❌ ' + (d.error || 'Bağlantı başarısız'));
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
        
        this.tiktokAuthenticating = false;
      },
      
      async disconnectTiktok(accountId) {
        if (!confirm('Bu TikTok hesabının bağlantısını kesmek istediğinizden emin misiniz?')) return;
        
        try {
          const r = await fetch('/api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              action: 'disconnect',
              platform: 'tiktok',
              account_id: accountId
            })
          });
          
          const d = await r.json();
          
          if (d.success) {
            alert('✅ ' + d.message);
            this.loadTiktokAccounts();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      async setDefaultTiktok(accountId) {
        try {
          const r = await fetch('/api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              action: 'set_default',
              platform: 'tiktok',
              account_id: accountId
            })
          });
          
          const d = await r.json();
          
          if (d.success) {
            this.loadTiktokAccounts();
          } else {
            alert('❌ ' + d.error);
          }
        } catch(e) {
          alert('❌ Hata: ' + e.message);
        }
      },
      
      // ==================== UTILITIES ====================
      formatNumber(num) {
        if (!num) return '0';
        if (num >= 1000000) return (num/1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num/1000).toFixed(1) + 'K';
        return num.toString();
      },
      
      switchTab(tab) {
        this.activeTab = tab;
        if (tab === 'youtube' && this.youtubeChannels.length === 0) {
          this.loadYoutubeChannels();
        } else if (tab === 'instagram' && this.instagramAccounts.length === 0) {
          this.loadInstagramAccounts();
        } else if (tab === 'tiktok' && this.tiktokAccounts.length === 0) {
          this.loadTiktokAccounts();
        }
      },
      
      toggleDark() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode ? '1' : '0');
        document.documentElement.classList.toggle('dark', this.darkMode);
      },
      
      init() {
        this.loadYoutubeChannels();
        this.loadInstagramAccounts();
        this.loadTiktokAccounts();
      }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="accountsApp()" x-init="init()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-5xl mx-auto">
          
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">🔗 Hesaplar</h1>
              <p class="text-gray-600 mt-1">Sosyal medya hesaplarınızı bağlayın ve yönetin</p>
            </div>
          </div>

          <!-- Platform Tabs -->
          <div class="bg-white rounded-t-lg shadow-sm border-b">
            <div class="flex">
              <!-- YouTube Tab -->
              <button 
                @click="switchTab('youtube')"
                :class="activeTab === 'youtube' ? 'border-red-500 text-red-600 bg-red-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="platform-tab flex items-center gap-2 px-6 py-4 border-b-2 font-medium transition-colors"
              >
                <svg class="platform-icon" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                <span>YouTube</span>
                <span 
                  x-show="youtubeChannels.length > 0" 
                  class="ml-1 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700"
                  x-text="youtubeChannels.length"
                ></span>
              </button>
              
              <!-- Instagram Tab -->
              <button 
                @click="switchTab('instagram')"
                :class="activeTab === 'instagram' ? 'border-pink-500 text-pink-600 bg-pink-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="platform-tab flex items-center gap-2 px-6 py-4 border-b-2 font-medium transition-colors"
              >
                <svg class="platform-icon" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
                <span>Instagram</span>
                <span 
                  x-show="instagramAccounts.length > 0" 
                  class="ml-1 px-2 py-0.5 text-xs rounded-full bg-pink-100 text-pink-700"
                  x-text="instagramAccounts.length"
                ></span>
              </button>
              
              <!-- TikTok Tab -->
              <button 
                @click="switchTab('tiktok')"
                :class="activeTab === 'tiktok' ? 'border-gray-900 text-gray-900 bg-gray-100' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="platform-tab flex items-center gap-2 px-6 py-4 border-b-2 font-medium transition-colors"
              >
                <svg class="platform-icon" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                </svg>
                <span>TikTok</span>
                <span 
                  x-show="tiktokAccounts.length > 0" 
                  class="ml-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700"
                  x-text="tiktokAccounts.length"
                ></span>
              </button>
            </div>
          </div>

          <!-- Tab Content Container -->
          <div class="bg-white rounded-b-lg shadow p-6">
            
            <!-- ==================== YOUTUBE TAB ==================== -->
            <div x-show="activeTab === 'youtube'" x-cloak>
              
              <!-- YouTube Loading -->
              <div x-show="youtubeLoading" class="text-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Yükleniyor...</p>
              </div>

              <!-- YouTube No Channels -->
              <template x-if="!youtubeLoading && youtubeChannels.length === 0">
                <div class="text-center py-8">
                  <div class="text-6xl mb-4">📺</div>
                  <h3 class="text-xl font-semibold text-gray-900 mb-2">Bağlı YouTube Hesabı Yok</h3>
                  <p class="text-gray-600 mb-6">YouTube hesabınızı bağlayarak video yüklemeye başlayın</p>
                  
                  <button 
                    @click="authenticateYoutube()"
                    :disabled="youtubeAuthenticating"
                    class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
                  >
                    <span x-show="!youtubeAuthenticating">➕ YouTube Hesabı Bağla</span>
                    <span x-show="youtubeAuthenticating">⏳ Bağlanıyor...</span>
                  </button>
                  
                  <div class="mt-6 p-4 bg-yellow-50 rounded-lg text-left max-w-lg mx-auto">
                    <h4 class="font-semibold text-yellow-900 mb-2">⚠️ Gereksinimler:</h4>
                    <ul class="text-sm text-yellow-800 space-y-1">
                      <li>• Google Cloud Console'dan <code class="bg-yellow-100 px-1 rounded">client_secrets.json</code> dosyası</li>
                      <li>• YouTube Data API v3 aktif</li>
                      <li>• OAuth 2.0 credentials</li>
                    </ul>
                  </div>
                </div>
              </template>

              <!-- YouTube Channels List -->
              <div x-show="!youtubeLoading && youtubeChannels.length > 0" class="space-y-4">
                
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold text-gray-900">Bağlı YouTube Kanalları</h3>
                  <button 
                    @click="authenticateYoutube()"
                    :disabled="youtubeAuthenticating"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50 text-sm inline-flex items-center"
                  >
                    <span x-show="!youtubeAuthenticating">➕ Yeni Hesap Bağla</span>
                    <span x-show="youtubeAuthenticating">⏳ Bağlanıyor...</span>
                  </button>
                </div>

                <template x-for="channel in youtubeChannels" :key="channel.channel_id">
                  <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                      <div class="flex items-center space-x-4">
                        <img 
                          :src="channel.thumbnail" 
                          :alt="channel.channel_title"
                          class="w-14 h-14 rounded-full"
                        />
                        <div>
                          <h4 class="font-semibold text-gray-900" x-text="channel.channel_title"></h4>
                          <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                            <span>📊 <span x-text="formatNumber(channel.subscriber_count)"></span> abone</span>
                            <span>🎬 <span x-text="channel.video_count"></span> video</span>
                          </div>
                          <div class="mt-2">
                            <span 
                              x-show="channel.is_default" 
                              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"
                            >
                              ✅ Varsayılan
                            </span>
                          </div>
                        </div>
                      </div>
                      
                      <div class="flex items-center space-x-2">
                        <button 
                          x-show="!channel.is_default"
                          @click="setDefaultYoutubeChannel(channel.channel_id)"
                          class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded"
                        >
                          ⭐ Varsayılan Yap
                        </button>
                        <button 
                          @click="disconnectYoutube(channel.channel_id)"
                          class="px-3 py-1.5 text-sm bg-red-100 hover:bg-red-200 text-red-700 rounded"
                        >
                          ❌ Bağlantıyı Kes
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
              
              <!-- YouTube Setup Guide -->
              <div class="mt-8 border-t pt-6" x-data="{ guideOpen: false }">
                <button 
                  @click="guideOpen = !guideOpen" 
                  class="flex items-center justify-between w-full text-left"
                >
                  <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    📖 YouTube Hesap Bağlama Rehberi
                  </h3>
                  <svg 
                    class="w-5 h-5 text-gray-500 transition-transform" 
                    :class="guideOpen ? 'rotate-180' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>
                
                <div x-show="guideOpen" x-collapse class="mt-4 space-y-6">
                  
                  <!-- Step 1 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm">1</span>
                      Google Cloud Console'da Proje Oluşturma
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><a href="https://console.cloud.google.com/" target="_blank" class="text-blue-600 hover:underline">Google Cloud Console</a>'a gidin</li>
                      <li>Yeni bir proje oluşturun veya mevcut projenizi seçin</li>
                      <li>Projenizin adını ve ID'sini not edin</li>
                    </ol>
                  </div>
                  
                  <!-- Step 2 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm">2</span>
                      YouTube Data API v3'ü Etkinleştirme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>Sol menüden <strong>"APIs & Services"</strong> → <strong>"Library"</strong> seçin</li>
                      <li>Arama kutusuna <code class="bg-gray-200 px-1 rounded">YouTube Data API v3</code> yazın</li>
                      <li>API'yi bulun ve <strong>"Enable"</strong> butonuna tıklayın</li>
                    </ol>
                  </div>
                  
                  <!-- Step 3 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm">3</span>
                      OAuth 2.0 Credentials Oluşturma
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><strong>"APIs & Services"</strong> → <strong>"Credentials"</strong> bölümüne gidin</li>
                      <li><strong>"+ CREATE CREDENTIALS"</strong> → <strong>"OAuth client ID"</strong> seçin</li>
                      <li>İlk kez yapıyorsanız <strong>"Configure Consent Screen"</strong> tıklayın:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li>User Type: <strong>External</strong> seçin</li>
                          <li>App name, User support email ve Developer email doldurun</li>
                          <li>Scopes bölümünde <code class="bg-gray-200 px-1 rounded">youtube.upload</code> ve <code class="bg-gray-200 px-1 rounded">youtube.readonly</code> ekleyin</li>
                          <li>Test users bölümüne kendi Gmail adresinizi ekleyin</li>
                        </ul>
                      </li>
                      <li>Application type: <strong>"Desktop app"</strong> seçin</li>
                      <li><strong>"Create"</strong> tıklayın</li>
                    </ol>
                  </div>
                  
                  <!-- Step 4 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm">4</span>
                      JSON Dosyasını İndirme ve Yerleştirme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>Oluşturduğunuz credential'ın yanındaki <strong>indirme ikonuna</strong> tıklayın</li>
                      <li>İndirilen dosyanın adını <code class="bg-gray-200 px-1 rounded">client_secrets.json</code> olarak değiştirin</li>
                      <li>Dosyayı şu klasöre kopyalayın: <code class="bg-gray-200 px-1 rounded">data/youtube_credentials/</code></li>
                    </ol>
                    <div class="mt-3 p-3 bg-yellow-100 rounded text-sm text-yellow-800">
                      ⚠️ <strong>Önemli:</strong> Bu dosyayı asla paylaşmayın veya Git'e commit etmeyin!
                    </div>
                  </div>
                  
                  <!-- Step 5 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm">5</span>
                      Hesabı Bağlama
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>Yukarıdaki <strong>"YouTube Hesabı Bağla"</strong> butonuna tıklayın</li>
                      <li>Açılan pencerede Google hesabınızla giriş yapın</li>
                      <li>Gerekli izinleri onaylayın</li>
                      <li>İşlem tamamlandığında kanal bilgileriniz burada görünecek</li>
                    </ol>
                  </div>
                  
                  <!-- Troubleshooting -->
                  <div class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-3">🔧 Sorun Giderme</h4>
                    <ul class="text-sm text-red-800 space-y-2">
                      <li><strong>"Access blocked" hatası:</strong> OAuth consent screen'de uygulamanızı yayınlayın veya test users'a email ekleyin</li>
                      <li><strong>"File not found" hatası:</strong> <code class="bg-red-100 px-1 rounded">client_secrets.json</code> dosyasının doğru konumda olduğundan emin olun</li>
                      <li><strong>"Quota exceeded" hatası:</strong> Günlük API kotanızı aştınız, yarın tekrar deneyin</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div x-show="activeTab === 'instagram'" x-cloak>
              
              <!-- Instagram Loading -->
              <div x-show="instagramLoading" class="text-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-pink-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Yükleniyor...</p>
              </div>

              <!-- Instagram No Accounts -->
              <template x-if="!instagramLoading && instagramAccounts.length === 0">
                <div class="text-center py-8">
                  <div class="text-6xl mb-4">📸</div>
                  <h3 class="text-xl font-semibold text-gray-900 mb-2">Bağlı Instagram Hesabı Yok</h3>
                  <p class="text-gray-600 mb-6">Instagram hesabınızı bağlayarak Reels paylaşmaya başlayın</p>
                  
                  <button 
                    @click="authenticateInstagram()"
                    :disabled="instagramAuthenticating"
                    class="bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white px-6 py-3 rounded-lg hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
                  >
                    <span x-show="!instagramAuthenticating">➕ Instagram Hesabı Bağla</span>
                    <span x-show="instagramAuthenticating">⏳ Bağlanıyor...</span>
                  </button>
                  
                  <div class="mt-6 p-4 bg-pink-50 rounded-lg text-left max-w-lg mx-auto">
                    <h4 class="font-semibold text-pink-900 mb-2">ℹ️ Not:</h4>
                    <ul class="text-sm text-pink-800 space-y-1">
                      <li>• Instagram Basic Display API veya Graph API gereklidir</li>
                      <li>• Meta Developer Console'dan uygulama oluşturmanız gerekir</li>
                      <li>• İşletme hesabı ile daha fazla özellik kullanabilirsiniz</li>
                    </ul>
                  </div>
                </div>
              </template>

              <!-- Instagram Accounts List -->
              <div x-show="!instagramLoading && instagramAccounts.length > 0" class="space-y-4">
                
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold text-gray-900">Bağlı Instagram Hesapları</h3>
                  <button 
                    @click="authenticateInstagram()"
                    :disabled="instagramAuthenticating"
                    class="bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90 disabled:opacity-50 text-sm inline-flex items-center"
                  >
                    <span x-show="!instagramAuthenticating">➕ Yeni Hesap Bağla</span>
                    <span x-show="instagramAuthenticating">⏳ Bağlanıyor...</span>
                  </button>
                </div>

                <template x-for="account in instagramAccounts" :key="account.account_id">
                  <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                      <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 flex items-center justify-center text-white text-xl font-bold">
                          <span x-text="account.username.charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                          <h4 class="font-semibold text-gray-900">@<span x-text="account.username"></span></h4>
                          <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                            <span x-show="account.followers_count">👥 <span x-text="formatNumber(account.followers_count)"></span> takipçi</span>
                            <span x-show="account.media_count">📷 <span x-text="account.media_count"></span> gönderi</span>
                          </div>
                          <div class="mt-2">
                            <span 
                              x-show="account.is_default" 
                              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"
                            >
                              ✅ Varsayılan
                            </span>
                            <span 
                              x-show="account.account_type" 
                              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-pink-100 text-pink-800 ml-1"
                              x-text="account.account_type === 'business' ? '💼 İşletme' : '👤 Kişisel'"
                            ></span>
                          </div>
                        </div>
                      </div>
                      
                      <div class="flex items-center space-x-2">
                        <button 
                          x-show="!account.is_default"
                          @click="setDefaultInstagram(account.account_id)"
                          class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded"
                        >
                          ⭐ Varsayılan Yap
                        </button>
                        <button 
                          @click="disconnectInstagram(account.account_id)"
                          class="px-3 py-1.5 text-sm bg-red-100 hover:bg-red-200 text-red-700 rounded"
                        >
                          ❌ Bağlantıyı Kes
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
              
              <!-- Instagram Setup Guide -->
              <div class="mt-8 border-t pt-6" x-data="{ guideOpen: false }">
                <button 
                  @click="guideOpen = !guideOpen" 
                  class="flex items-center justify-between w-full text-left"
                >
                  <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    📖 Instagram Hesap Bağlama Rehberi
                  </h3>
                  <svg 
                    class="w-5 h-5 text-gray-500 transition-transform" 
                    :class="guideOpen ? 'rotate-180' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>
                
                <div x-show="guideOpen" x-collapse class="mt-4 space-y-6">
                  
                  <!-- Overview -->
                  <div class="bg-pink-50 rounded-lg p-4">
                    <h4 class="font-semibold text-pink-900 mb-2">📋 Genel Bakış</h4>
                    <p class="text-sm text-pink-800">
                      Instagram API entegrasyonu için <strong>Meta Developer</strong> platformunu kullanmanız gerekmektedir. 
                      İki farklı API seçeneği bulunmaktadır:
                    </p>
                    <ul class="text-sm text-pink-800 mt-2 space-y-1">
                      <li>• <strong>Instagram Basic Display API:</strong> Kişisel hesaplar için (okuma)</li>
                      <li>• <strong>Instagram Graph API:</strong> İşletme/Creator hesapları için (okuma + yazma)</li>
                    </ul>
                  </div>
                  
                  <!-- Step 1 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center text-sm">1</span>
                      Meta Developer Hesabı Oluşturma
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><a href="https://developers.facebook.com/" target="_blank" class="text-blue-600 hover:underline">Meta for Developers</a> sitesine gidin</li>
                      <li>Facebook hesabınızla giriş yapın</li>
                      <li><strong>"My Apps"</strong> → <strong>"Create App"</strong> tıklayın</li>
                      <li>Use case olarak <strong>"Other"</strong> → <strong>"Consumer"</strong> seçin</li>
                      <li>Uygulama adını ve iletişim email'inizi girin</li>
                    </ol>
                  </div>
                  
                  <!-- Step 2 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center text-sm">2</span>
                      Instagram Basic Display API Ekleme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>App Dashboard'da <strong>"Add Products"</strong> bölümüne gidin</li>
                      <li><strong>"Instagram Basic Display"</strong> yanındaki <strong>"Set Up"</strong> tıklayın</li>
                      <li>Settings'de şunları doldurun:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><strong>Valid OAuth Redirect URIs:</strong> <code class="bg-gray-200 px-1 rounded">https://yourdomain.com/callback</code></li>
                          <li><strong>Deauthorize Callback URL:</strong> <code class="bg-gray-200 px-1 rounded">https://yourdomain.com/deauth</code></li>
                          <li><strong>Data Deletion Request URL:</strong> <code class="bg-gray-200 px-1 rounded">https://yourdomain.com/delete</code></li>
                        </ul>
                      </li>
                    </ol>
                  </div>
                  
                  <!-- Step 3 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center text-sm">3</span>
                      Instagram Test Kullanıcısı Ekleme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><strong>"Roles"</strong> → <strong>"Instagram Testers"</strong> bölümüne gidin</li>
                      <li><strong>"Add Instagram Testers"</strong> tıklayın</li>
                      <li>Instagram kullanıcı adınızı ekleyin</li>
                      <li>Instagram uygulamasında: <strong>Ayarlar</strong> → <strong>Web Sitesi İzinleri</strong> → <strong>Tester Davetleri</strong> → daveti kabul edin</li>
                    </ol>
                  </div>
                  
                  <!-- Step 4 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center text-sm">4</span>
                      Credentials'ları Kaydetme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>App Dashboard'da <strong>"Settings"</strong> → <strong>"Basic"</strong> gidin</li>
                      <li>Şu bilgileri not edin:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><strong>App ID</strong> (Instagram App ID)</li>
                          <li><strong>App Secret</strong> (Show butonuna tıklayın)</li>
                        </ul>
                      </li>
                      <li>Bu bilgileri <code class="bg-gray-200 px-1 rounded">data/social_credentials/instagram_config.json</code> dosyasına kaydedin</li>
                    </ol>
                  </div>
                  
                  <!-- Step 5 - Business Account -->
                  <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-semibold text-purple-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-sm">⭐</span>
                      İşletme Hesabı (İçerik Paylaşımı İçin Gerekli)
                    </h4>
                    <p class="text-sm text-purple-800 mb-2">
                      Instagram'a içerik yükleyebilmek için <strong>İşletme veya Creator hesabı</strong> gereklidir:
                    </p>
                    <ol class="text-sm text-purple-700 space-y-2 ml-4 list-decimal">
                      <li>Instagram'da <strong>Ayarlar</strong> → <strong>Hesap</strong> → <strong>Profesyonel hesaba geç</strong></li>
                      <li><strong>İşletme</strong> veya <strong>İçerik Üretici</strong> seçin</li>
                      <li>Bir Facebook Sayfası bağlayın (veya yeni oluşturun)</li>
                      <li>Meta Developer'da <strong>Instagram Graph API</strong> ürününü ekleyin</li>
                      <li>Facebook Page'i uygulamanıza bağlayın</li>
                    </ol>
                  </div>
                  
                  <!-- Troubleshooting -->
                  <div class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-3">🔧 Sorun Giderme</h4>
                    <ul class="text-sm text-red-800 space-y-2">
                      <li><strong>"Invalid OAuth access token":</strong> Token süresi dolmuş, yeniden yetkilendirin</li>
                      <li><strong>"This user is not a test user":</strong> Instagram Test Kullanıcısı eklemeyi ve daveti kabul etmeyi unutmayın</li>
                      <li><strong>Reels paylaşılamıyor:</strong> İşletme hesabına geçin ve Instagram Graph API kullanın</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- ==================== TIKTOK TAB ==================== -->
            <div x-show="activeTab === 'tiktok'" x-cloak>
              
              <!-- TikTok Loading -->
              <div x-show="tiktokLoading" class="text-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 mx-auto"></div>
                <p class="mt-4 text-gray-600">Yükleniyor...</p>
              </div>

              <!-- TikTok No Accounts -->
              <template x-if="!tiktokLoading && tiktokAccounts.length === 0">
                <div class="text-center py-8">
                  <div class="text-6xl mb-4">🎵</div>
                  <h3 class="text-xl font-semibold text-gray-900 mb-2">Bağlı TikTok Hesabı Yok</h3>
                  <p class="text-gray-600 mb-6">TikTok hesabınızı bağlayarak video paylaşmaya başlayın</p>
                  
                  <button 
                    @click="authenticateTiktok()"
                    :disabled="tiktokAuthenticating"
                    class="bg-gray-900 text-white px-6 py-3 rounded-lg hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
                  >
                    <span x-show="!tiktokAuthenticating">➕ TikTok Hesabı Bağla</span>
                    <span x-show="tiktokAuthenticating">⏳ Bağlanıyor...</span>
                  </button>
                  
                  <div class="mt-6 p-4 bg-gray-100 rounded-lg text-left max-w-lg mx-auto">
                    <h4 class="font-semibold text-gray-900 mb-2">ℹ️ Not:</h4>
                    <ul class="text-sm text-gray-700 space-y-1">
                      <li>• TikTok for Developers'dan uygulama oluşturmanız gerekir</li>
                      <li>• Video Upload API erişimi için başvuru yapmanız gerekebilir</li>
                      <li>• Creator veya Business hesabı önerilir</li>
                    </ul>
                  </div>
                </div>
              </template>

              <!-- TikTok Accounts List -->
              <div x-show="!tiktokLoading && tiktokAccounts.length > 0" class="space-y-4">
                
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold text-gray-900">Bağlı TikTok Hesapları</h3>
                  <button 
                    @click="authenticateTiktok()"
                    :disabled="tiktokAuthenticating"
                    class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 disabled:opacity-50 text-sm inline-flex items-center"
                  >
                    <span x-show="!tiktokAuthenticating">➕ Yeni Hesap Bağla</span>
                    <span x-show="tiktokAuthenticating">⏳ Bağlanıyor...</span>
                  </button>
                </div>

                <template x-for="account in tiktokAccounts" :key="account.account_id">
                  <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                      <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 rounded-full bg-gray-900 flex items-center justify-center text-white text-xl font-bold">
                          <span x-text="account.username.charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                          <h4 class="font-semibold text-gray-900">@<span x-text="account.username"></span></h4>
                          <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                            <span x-show="account.followers_count">👥 <span x-text="formatNumber(account.followers_count)"></span> takipçi</span>
                            <span x-show="account.likes_count">❤️ <span x-text="formatNumber(account.likes_count)"></span> beğeni</span>
                          </div>
                          <div class="mt-2">
                            <span 
                              x-show="account.is_default" 
                              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"
                            >
                              ✅ Varsayılan
                            </span>
                            <span 
                              x-show="account.is_verified" 
                              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 ml-1"
                            >
                              ✓ Doğrulanmış
                            </span>
                          </div>
                        </div>
                      </div>
                      
                      <div class="flex items-center space-x-2">
                        <button 
                          x-show="!account.is_default"
                          @click="setDefaultTiktok(account.account_id)"
                          class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded"
                        >
                          ⭐ Varsayılan Yap
                        </button>
                        <button 
                          @click="disconnectTiktok(account.account_id)"
                          class="px-3 py-1.5 text-sm bg-red-100 hover:bg-red-200 text-red-700 rounded"
                        >
                          ❌ Bağlantıyı Kes
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
              
              <!-- TikTok Setup Guide -->
              <div class="mt-8 border-t pt-6" x-data="{ guideOpen: false }">
                <button 
                  @click="guideOpen = !guideOpen" 
                  class="flex items-center justify-between w-full text-left"
                >
                  <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    📖 TikTok Hesap Bağlama Rehberi
                  </h3>
                  <svg 
                    class="w-5 h-5 text-gray-500 transition-transform" 
                    :class="guideOpen ? 'rotate-180' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>
                
                <div x-show="guideOpen" x-collapse class="mt-4 space-y-6">
                  
                  <!-- Overview -->
                  <div class="bg-gray-100 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">📋 Genel Bakış</h4>
                    <p class="text-sm text-gray-700">
                      TikTok API entegrasyonu için <strong>TikTok for Developers</strong> platformunu kullanmanız gerekmektedir.
                      Video yükleme için <strong>Content Posting API</strong> erişimine başvurmanız gerekir.
                    </p>
                    <div class="mt-2 p-2 bg-yellow-100 rounded text-sm text-yellow-800">
                      ⚠️ <strong>Önemli:</strong> TikTok API erişimi için uygulama incelemesi gerekebilir ve bu süreç birkaç gün sürebilir.
                    </div>
                  </div>
                  
                  <!-- Step 1 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm">1</span>
                      TikTok Developer Hesabı Oluşturma
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><a href="https://developers.tiktok.com/" target="_blank" class="text-blue-600 hover:underline">TikTok for Developers</a> sitesine gidin</li>
                      <li>TikTok hesabınızla giriş yapın veya yeni hesap oluşturun</li>
                      <li>Developer Agreement'ı kabul edin</li>
                      <li>Profil bilgilerinizi tamamlayın</li>
                    </ol>
                  </div>
                  
                  <!-- Step 2 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm">2</span>
                      Uygulama Oluşturma
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><strong>"Manage Apps"</strong> → <strong>"Create App"</strong> tıklayın</li>
                      <li>Uygulama tipini seçin:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><strong>Live Display:</strong> Sadece okuma (veri çekme)</li>
                          <li><strong>Content Posting:</strong> Okuma + Yazma (video yükleme) - <strong>Bunu seçin</strong></li>
                        </ul>
                      </li>
                      <li>Uygulama adı, açıklama ve kategori girin</li>
                      <li>Uygulama ikonunu yükleyin</li>
                    </ol>
                  </div>
                  
                  <!-- Step 3 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm">3</span>
                      API Ürünlerini Ekleme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>Uygulama detaylarında <strong>"Add products"</strong> bölümüne gidin</li>
                      <li>Şu ürünleri ekleyin:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><strong>Login Kit:</strong> Kullanıcı girişi için</li>
                          <li><strong>Content Posting API:</strong> Video yükleme için</li>
                          <li><strong>User Info (isteğe bağlı):</strong> Profil bilgisi için</li>
                        </ul>
                      </li>
                      <li>Her ürün için gerekli scope'ları seçin</li>
                    </ol>
                  </div>
                  
                  <!-- Step 4 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm">4</span>
                      OAuth Ayarları
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li><strong>"Configuration"</strong> bölümünde OAuth ayarlarını yapın:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><strong>Redirect URI:</strong> <code class="bg-gray-200 px-1 rounded">https://yourdomain.com/tiktok/callback</code></li>
                          <li><strong>Redirect URI (Web):</strong> Aynı URL'yi ekleyin</li>
                        </ul>
                      </li>
                      <li>Scopes seçin:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><code class="bg-gray-200 px-1 rounded">user.info.basic</code></li>
                          <li><code class="bg-gray-200 px-1 rounded">video.upload</code></li>
                          <li><code class="bg-gray-200 px-1 rounded">video.publish</code></li>
                        </ul>
                      </li>
                    </ol>
                  </div>
                  
                  <!-- Step 5 -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm">5</span>
                      Credentials'ları Kaydetme
                    </h4>
                    <ol class="text-sm text-gray-700 space-y-2 ml-8 list-decimal">
                      <li>Uygulama detaylarından şu bilgileri not edin:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                          <li><strong>Client Key</strong></li>
                          <li><strong>Client Secret</strong></li>
                        </ul>
                      </li>
                      <li>Bu bilgileri <code class="bg-gray-200 px-1 rounded">data/social_credentials/tiktok_config.json</code> dosyasına kaydedin</li>
                    </ol>
                    <div class="mt-3 p-3 bg-yellow-100 rounded text-sm text-yellow-800">
                      ⚠️ <strong>Önemli:</strong> Client Secret'ı asla paylaşmayın!
                    </div>
                  </div>
                  
                  <!-- Step 6 - Submit for Review -->
                  <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-3 flex items-center gap-2">
                      <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm">6</span>
                      Uygulama İncelemesine Gönderme
                    </h4>
                    <p class="text-sm text-blue-800 mb-2">
                      Content Posting API kullanmak için uygulamanızı incelemeye göndermeniz gerekir:
                    </p>
                    <ol class="text-sm text-blue-700 space-y-2 ml-4 list-decimal">
                      <li><strong>"Submit for review"</strong> butonuna tıklayın</li>
                      <li>Uygulamanızın ne yaptığını açıklayan detaylı bir açıklama yazın</li>
                      <li>Ekran görüntüleri veya demo video ekleyin</li>
                      <li>Privacy Policy ve Terms of Service URL'lerini ekleyin</li>
                      <li>İnceleme genellikle 1-5 iş günü sürer</li>
                    </ol>
                  </div>
                  
                  <!-- Sandbox Mode -->
                  <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-semibold text-green-900 mb-3 flex items-center gap-2">
                      🧪 Sandbox Modu (Test)
                    </h4>
                    <p class="text-sm text-green-800">
                      Uygulama onaylanmadan önce <strong>Sandbox modunda</strong> test yapabilirsiniz:
                    </p>
                    <ul class="text-sm text-green-700 mt-2 space-y-1">
                      <li>• Sadece developer hesabınızla çalışır</li>
                      <li>• Günlük API çağrı limiti vardır</li>
                      <li>• Yüklenen videolar sadece size görünür</li>
                    </ul>
                  </div>
                  
                  <!-- Troubleshooting -->
                  <div class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-3">🔧 Sorun Giderme</h4>
                    <ul class="text-sm text-red-800 space-y-2">
                      <li><strong>"Access token expired":</strong> Token 24 saat geçerlidir, refresh token kullanarak yenileyin</li>
                      <li><strong>"Scope not authorized":</strong> Uygulamanız için gerekli scope'lar onaylanmamış olabilir</li>
                      <li><strong>"Video upload failed":</strong> Video formatı TikTok gereksinimlerini karşılamıyor olabilir (MP4, max 4GB, 3-60 saniye)</li>
                      <li><strong>"Rate limit exceeded":</strong> API çağrı limitini aştınız, bir süre bekleyin</li>
                    </ul>
                  </div>
                  
                  <!-- Video Requirements -->
                  <div class="bg-gray-100 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">📹 TikTok Video Gereksinimleri</h4>
                    <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-700">
                      <div>
                        <strong>Format:</strong>
                        <ul class="mt-1 space-y-1">
                          <li>• Dosya tipi: MP4, WebM</li>
                          <li>• Codec: H.264</li>
                          <li>• Max boyut: 4GB</li>
                        </ul>
                      </div>
                      <div>
                        <strong>Boyutlar:</strong>
                        <ul class="mt-1 space-y-1">
                          <li>• En boy oranı: 9:16 (dikey)</li>
                          <li>• Çözünürlük: 1080x1920 önerilir</li>
                          <li>• Süre: 3-180 saniye</li>
                        </ul>
                      </div>
                    </div>
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
