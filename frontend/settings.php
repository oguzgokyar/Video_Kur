<?php
$page_title = 'Ayarlar - YouTube Shorts Otomasyon';
$active_page = 'settings';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <style>
    html.dark { color-scheme: dark; }
    html.dark body { background-color: #0f172a !important; }
    html.dark .bg-white { background-color: #1e293b !important; }
    html.dark .bg-gray-50 { background-color: #0f172a !important; }
    html.dark .bg-gray-100 { background-color: #1e293b !important; }
    html.dark .border-gray-100, html.dark .border-gray-200, html.dark .border-gray-300 { border-color: #334155 !important; }
    html.dark .border-b, html.dark .border-r, html.dark .border-t { border-color: #334155 !important; }
    html.dark .text-gray-800, html.dark .text-gray-700 { color: #f1f5f9 !important; }
    html.dark .text-gray-600, html.dark .text-gray-500 { color: #94a3b8 !important; }
    html.dark header { background-color: #1e293b !important; }
    html.dark aside { background-color: #1e293b !important; border-color: #334155 !important; }
    html.dark footer { background-color: #1e293b !important; }
    html.dark input[type=password], html.dark input[type=text], html.dark select { background-color: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important; }
    html.dark .shadow-sm { box-shadow: 0 1px 6px 0 rgba(0,0,0,.5) !important; }
    html.dark .hover\:bg-gray-100:hover { background-color: #334155 !important; }
    html.dark .hover\:bg-gray-200:hover { background-color: #475569 !important; }
    html.dark .bg-purple-50 { background-color: #2d1b4e !important; }
    html.dark .border-purple-200 { border-color: #6b21a8 !important; }
    html.dark .text-purple-800, html.dark .text-purple-700, html.dark .text-purple-600 { color: #d8b4fe !important; }
    html.dark .bg-green-50 { background-color: #14532d !important; }
    html.dark .bg-red-50 { background-color: #450a0a !important; }
    html.dark .bg-blue-50 { background-color: #1e3a5f !important; }
    .tab-active { border-bottom: 2px solid #3b82f6; color: #3b82f6; font-weight: 600; }
    .toggle-switch { position: relative; width: 44px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; border-radius: 24px; transition: .3s; }
    .toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s; }
    .toggle-switch input:checked + .toggle-slider { background-color: #3b82f6; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
  </style>

  <script>
    if (localStorage.getItem('darkMode') === '1') document.documentElement.classList.add('dark');
  </script>
  <script>
  function settingsApp() {
    return {
      sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1', darkMode: false, activeTab: 'genel',
      geminiKey: '', elevenKey: '', hfKey: '', pexelsKey: '', falKey: '', pollinationsKey: '',
      ttsProvider: 'elevenlabs', geminiModel: 'gemini-2.0-flash',
      imageService: 'pollinations', pollinationsModel: 'flux',
      pollinationsTextModel: 'openai-fast', scriptProvider: 'gemini',
      falWidth: 768, falHeight: 768, falSteps: 4,
      subtitleStyle: {
        FontName: 'Arial',
        FontSize: 24,
        PrimaryColour: '#FFFFFF',
        OutlineColour: '#000000',
        BackColour: '#80000000',
        BorderStyle: 3,
        Outline: 3,
        Shadow: 1,
        MarginV: 100,
        Alignment: 2,
        Bold: 1
      },
      toolsEnabled: { scriptGen: true, imageGen: true, ttsGen: true, videoCompose: true },
      servicesEnabled: {
        fal_image: true, pollinations_image: true, huggingface_image: true, pexels_image: true,
        gemini_script: true, pollinations_text: true,
        elevenlabs_tts: true, edge_tts: true
      },
      saveMsg: '', saveError: false,
      checks: {
        gemini: {loading:false,result:null}, elevenlabs: {loading:false,result:null},
        huggingface: {loading:false,result:null}, pexels: {loading:false,result:null},
        fal: {loading:false,result:null}, pollinations: {loading:false,result:null},
        pollinations_image: {loading:false,result:null}, pollinations_text: {loading:false,result:null},
        edge_tts: {loading:false,result:null}, ffmpeg: {loading:false,result:null}, python: {loading:false,result:null}
      },
      tabs: [
        { id:'genel', label:'Genel', icon:'⚙️' },
        { id:'scheduler', label:'Zamanlayıcı', icon:'⏰' },
        { id:'script', label:'Script', icon:'📝' },
        { id:'gorsel', label:'Görsel', icon:'🖼️' },
        { id:'ses', label:'Ses', icon:'🔊' },
        { id:'altyazi', label:'Altyazı', icon:'💬' },
        { id:'video', label:'Video', icon:'🎬' }
      ],
      
      // Scheduler state
      schedulerStatus: {
        production: { running: false, pid: null, started_at: null },
        social: { running: false, pid: null, started_at: null }
      },
      schedulerLogs: [],
      schedulerLoading: false,
      
      // Scheduler methods
      async loadSchedulerStatus() {
        try {
          const r = await fetch('/api/scheduler_control.php?action=status');
          const d = await r.json();
          if (d.success) {
            this.schedulerStatus = d.status;
          }
        } catch(e) {
          console.error('Scheduler durumu yüklenemedi:', e);
        }
      },
      
      async loadSchedulerLogs() {
        try {
          const r = await fetch('/api/scheduler_control.php?action=logs&lines=50');
          
          // Check if response is ok and has content
          if (!r.ok) {
            console.warn('Logs API failed:', r.status);
            this.schedulerLogs = [];
            return;
          }
          
          const text = await r.text();
          if (!text.trim()) {
            console.warn('Empty response from logs API');
            this.schedulerLogs = [];
            return;
          }
          
          const d = JSON.parse(text);
          if (d.success) {
            this.schedulerLogs = d.logs || [];
          } else {
            console.warn('Logs API returned error:', d.error);
            this.schedulerLogs = [];
          }
        } catch(e) {
          console.warn('Log yükleme hatası (normal):', e.message);
          this.schedulerLogs = [];
        }
      },
      
      async toggleScheduler(type) {
        this.schedulerLoading = true;
        const isRunning = this.schedulerStatus[type]?.running;
        const action = isRunning ? 'stop' : 'start';
        
        try {
          const r = await fetch('/api/scheduler_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, type })
          });
          const d = await r.json();
          
          if (d.success) {
            await this.loadSchedulerStatus();
            await this.loadSchedulerLogs();
          } else {
            alert('Hata: ' + (d.error || 'Bilinmeyen hata'));
          }
        } catch(e) {
          alert('Hata: ' + e.message);
        }
        this.schedulerLoading = false;
      },
      
      async clearSchedulerLogs() {
        try {
          await fetch('/api/scheduler_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear_logs' })
          });
          this.schedulerLogs = [];
        } catch(e) {
          console.error('Log temizleme hatası:', e);
        }
      },
      toggleDark() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode ? '1' : '0');
        document.documentElement.classList.toggle('dark', this.darkMode);
      },
      loadConfig() {
        this.darkMode = localStorage.getItem('darkMode') === '1';
        fetch('/api/config.php').then(r => r.json()).then(d => {
          this.geminiKey = d.geminiKey || '';
          this.elevenKey = d.elevenKey || '';
          this.hfKey = d.hfKey || '';
          this.pexelsKey = d.pexelsKey || '';
          this.falKey = d.falKey || '';
          this.pollinationsKey = d.pollinationsKey || '';
          this.ttsProvider = d.ttsProvider || 'elevenlabs';
          this.geminiModel = d.geminiModel || 'gemini-2.0-flash';
          this.imageService = d.imageService || 'pollinations';
          this.pollinationsModel = d.pollinationsModel || 'flux';
          this.pollinationsTextModel = d.pollinationsTextModel || 'openai-fast';
          this.scriptProvider = d.scriptProvider || 'gemini';
          this.falWidth = d.falWidth || 768;
          this.falHeight = d.falHeight || 768;
          this.falSteps = d.falSteps || 4;
          if (d.subtitleStyle) {
            this.subtitleStyle = Object.assign({}, this.subtitleStyle, d.subtitleStyle);
          }
          if (d.toolsEnabled) {
            this.toolsEnabled = {
              scriptGen: d.toolsEnabled.scriptGen !== false,
              imageGen: d.toolsEnabled.imageGen !== false,
              ttsGen: d.toolsEnabled.ttsGen !== false,
              videoCompose: d.toolsEnabled.videoCompose !== false
            };
          }
          if (d.servicesEnabled) {
            this.servicesEnabled = {
              fal_image: d.servicesEnabled.fal_image !== false,
              pollinations_image: d.servicesEnabled.pollinations_image !== false,
              huggingface_image: d.servicesEnabled.huggingface_image !== false,
              pexels_image: d.servicesEnabled.pexels_image !== false,
              gemini_script: d.servicesEnabled.gemini_script !== false,
              pollinations_text: d.servicesEnabled.pollinations_text !== false,
              elevenlabs_tts: d.servicesEnabled.elevenlabs_tts !== false,
              edge_tts: d.servicesEnabled.edge_tts !== false
            };
          }
        }).catch(() => {});
        
        // Scheduler durumu yükle
        this.loadSchedulerStatus();
        this.loadSchedulerLogs();
      },
      saveConfig() {
        this.saveMsg = ''; this.saveError = false;
        fetch('/api/config.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            geminiKey: this.geminiKey, elevenKey: this.elevenKey, hfKey: this.hfKey, pexelsKey: this.pexelsKey,
            falKey: this.falKey, pollinationsKey: this.pollinationsKey,
            ttsProvider: this.ttsProvider, geminiModel: this.geminiModel,
            imageService: this.imageService, pollinationsModel: this.pollinationsModel,
            pollinationsTextModel: this.pollinationsTextModel, scriptProvider: this.scriptProvider,
            falWidth: this.falWidth, falHeight: this.falHeight, falSteps: this.falSteps,
            subtitleStyle: this.subtitleStyle,
            toolsEnabled: this.toolsEnabled,
            servicesEnabled: this.servicesEnabled
          })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) { this.saveMsg = 'Ayarlar kaydedildi!'; this.saveError = false; }
          else { this.saveMsg = d.error || 'Kaydetme hatası!'; this.saveError = true; }
        })
        .catch(() => { this.saveMsg = 'Sunucuya bağlanılamadı!'; this.saveError = true; });
      },
      async testKey(provider, key, extra) {
        const keyless = ['pollinations_image','pollinations_text','edge_tts','ffmpeg','python'];
        if (!keyless.includes(provider) && !key) {
          this.checks[provider] = {loading:false, result:{valid:false, message:'API anahtarı boş'}};
          return;
        }
        this.checks[provider] = {loading:true, result:null};
        try {
          const body = {provider, key: key || ''};
          if (extra) Object.assign(body, extra);
          const r = await fetch('/api/check.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
          this.checks[provider].result = await r.json();
        } catch(e) { this.checks[provider].result = {valid:false, message:'Sunucuya bağlanılamadı'}; }
        this.checks[provider].loading = false;
      },
      testAllInTab() {
        if (this.activeTab === 'genel') {
          this.testKey('python'); this.testKey('ffmpeg');
          this.testKey('fal', this.falKey);
        } else if (this.activeTab === 'script') {
          this.testKey('gemini', this.geminiKey);
          this.testKey('pollinations_text', '', {model: this.pollinationsTextModel});
        } else if (this.activeTab === 'gorsel') {
          this.testKey('fal', this.falKey);
          this.testKey('pollinations_image', '', {model: this.pollinationsModel});
          this.testKey('huggingface', this.hfKey);
          this.testKey('pexels', this.pexelsKey);
        } else if (this.activeTab === 'ses') {
          this.testKey('elevenlabs', this.elevenKey);
          this.testKey('edge_tts');
        } else if (this.activeTab === 'video') {
          this.testKey('ffmpeg');
          this.testKey('python');
        }
      }
    };
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="settingsApp()" x-init="loadConfig()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>
    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>
      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-3xl mx-auto">
          <h1 class="text-2xl font-bold text-gray-800 mb-4">Ayarlar</h1>

          <!-- Tab Navigation -->
          <div class="flex gap-1 border-b border-gray-200 mb-6 overflow-x-auto">
            <template x-for="tab in tabs" :key="tab.id">
              <button @click="activeTab = tab.id"
                class="flex items-center gap-1.5 px-4 py-2.5 text-sm transition whitespace-nowrap border-b-2"
                :class="activeTab === tab.id ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <span x-text="tab.icon"></span>
                <span x-text="tab.label"></span>
              </button>
            </template>
          </div>

          <form @submit.prevent="saveConfig()" class="space-y-6">

            <!-- ═══════════ TAB: GENEL ═══════════ -->
            <div x-show="activeTab === 'genel'" x-transition>

              <!-- API Keys -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🔑 API Anahtarları</h2>

                <label class="block mb-1 text-sm font-semibold text-gray-700">Gemini API Key</label>
                <div class="flex gap-2 mb-1">
                  <input type="password" x-model="geminiKey" placeholder="AIza..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                  <button type="button" @click="testKey('gemini', geminiKey)" :disabled="checks.gemini.loading" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold border border-gray-300 transition disabled:opacity-50 whitespace-nowrap">
                    <span x-show="!checks.gemini.loading">🔍 Test</span><span x-show="checks.gemini.loading">⏳</span>
                  </button>
                </div>
                <template x-if="checks.gemini.result">
                  <div class="text-xs mb-3 px-1 font-medium" :class="checks.gemini.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.gemini.result.message"></div>
                </template>
                <template x-if="!checks.gemini.result"><div class="mb-3"></div></template>

                <label class="block mb-1 text-sm font-semibold text-gray-700">ElevenLabs API Key</label>
                <div class="flex gap-2 mb-1">
                  <input type="password" x-model="elevenKey" placeholder="sk_..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                  <button type="button" @click="testKey('elevenlabs', elevenKey)" :disabled="checks.elevenlabs.loading" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold border border-gray-300 transition disabled:opacity-50 whitespace-nowrap">
                    <span x-show="!checks.elevenlabs.loading">🔍 Test</span><span x-show="checks.elevenlabs.loading">⏳</span>
                  </button>
                </div>
                <template x-if="checks.elevenlabs.result">
                  <div class="text-xs mb-3 px-1 font-medium" :class="checks.elevenlabs.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.elevenlabs.result.message"></div>
                </template>
                <template x-if="!checks.elevenlabs.result"><div class="mb-3"></div></template>

                <label class="block mb-1 text-sm font-semibold text-gray-700">HuggingFace API Token</label>
                <div class="flex gap-2 mb-1">
                  <input type="password" x-model="hfKey" placeholder="hf_..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                  <button type="button" @click="testKey('huggingface', hfKey)" :disabled="checks.huggingface.loading" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold border border-gray-300 transition disabled:opacity-50 whitespace-nowrap">
                    <span x-show="!checks.huggingface.loading">🔍 Test</span><span x-show="checks.huggingface.loading">⏳</span>
                  </button>
                </div>
                <template x-if="checks.huggingface.result">
                  <div class="text-xs mb-3 px-1 font-medium" :class="checks.huggingface.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.huggingface.result.message"></div>
                </template>
                <template x-if="!checks.huggingface.result"><div class="mb-3"></div></template>

                <label class="block mb-1 text-sm font-semibold text-gray-700">Pexels API Key</label>
                <div class="flex gap-2 mb-1">
                  <input type="password" x-model="pexelsKey" placeholder="..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                  <button type="button" @click="testKey('pexels', pexelsKey)" :disabled="checks.pexels.loading" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold border border-gray-300 transition disabled:opacity-50 whitespace-nowrap">
                    <span x-show="!checks.pexels.loading">🔍 Test</span><span x-show="checks.pexels.loading">⏳</span>
                  </button>
                </div>
                <template x-if="checks.pexels.result">
                  <div class="text-xs mb-3 px-1 font-medium" :class="checks.pexels.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.pexels.result.message"></div>
                </template>
                <template x-if="!checks.pexels.result"><div class="mb-3"></div></template>

                <label class="block mb-1 text-sm font-semibold text-gray-700">🌸 Pollinations API Key <span class="text-xs text-green-600 font-normal">(Önerilen - Hızlı & Güvenilir)</span></label>
                <div class="flex gap-2 mb-1">
                  <input type="password" x-model="pollinationsKey" placeholder="pk_..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                  <button type="button" @click="testKey('pollinations', pollinationsKey)" :disabled="checks.pollinations.loading" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold border border-gray-300 transition disabled:opacity-50 whitespace-nowrap">
                    <span x-show="!checks.pollinations.loading">🔍 Test</span><span x-show="checks.pollinations.loading">⏳</span>
                  </button>
                </div>
                <template x-if="checks.pollinations.result">
                  <div class="text-xs mb-1 px-1 font-medium" :class="checks.pollinations.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.pollinations.result.message"></div>
                </template>
                <p class="text-xs text-gray-500 mb-3">API key: <a href="https://pollinations.ai/pricing" target="_blank" class="text-blue-600 underline">pollinations.ai/pricing</a> • Model: FLUX (hızlı görsel üretim)</p>

                <label class="block mb-1 text-sm font-semibold text-gray-700">⚡ Fal.ai API Key <span class="text-xs text-blue-600 font-normal">(Alternatif - Ucuz)</span></label>
                <div class="flex gap-2 mb-1">
                  <input type="password" x-model="falKey" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:xxxxxxxx" class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                  <button type="button" @click="testKey('fal', falKey)" :disabled="checks.fal.loading" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold border border-gray-300 transition disabled:opacity-50 whitespace-nowrap">
                    <span x-show="!checks.fal.loading">🔍 Test</span><span x-show="checks.fal.loading">⏳</span>
                  </button>
                </div>
                <template x-if="checks.fal.result">
                  <div class="text-xs mb-1 px-1 font-medium" :class="checks.fal.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.fal.result.message"></div>
                </template>
                <p class="text-xs text-gray-500 mb-3">API key: <a href="https://fal.ai/dashboard/keys" target="_blank" class="text-blue-600 underline">fal.ai/dashboard/keys</a> • Bakiye: <a href="https://fal.ai/dashboard/billing" target="_blank" class="text-blue-600 underline">fal.ai/dashboard/billing</a></p>
              </div>

              <!-- Sistem Araçları -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🖥️ Sistem Araçları</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">Python</span>
                    <div class="flex items-center gap-2">
                      <template x-if="checks.python.result">
                        <span class="text-xs font-medium" :class="checks.python.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.python.result.valid ? '✓' : '✗'"></span>
                      </template>
                      <button type="button" @click="testKey('python')" :disabled="checks.python.loading" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-xs font-semibold transition disabled:opacity-50">
                        <span x-show="!checks.python.loading">Test</span><span x-show="checks.python.loading">⏳</span>
                      </button>
                    </div>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">FFmpeg</span>
                    <div class="flex items-center gap-2">
                      <template x-if="checks.ffmpeg.result">
                        <span class="text-xs font-medium" :class="checks.ffmpeg.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.ffmpeg.result.valid ? '✓' : '✗'"></span>
                      </template>
                      <button type="button" @click="testKey('ffmpeg')" :disabled="checks.ffmpeg.loading" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-xs font-semibold transition disabled:opacity-50">
                        <span x-show="!checks.ffmpeg.loading">Test</span><span x-show="checks.ffmpeg.loading">⏳</span>
                      </button>
                    </div>
                  </div>
                </div>
                <template x-if="checks.python.result">
                  <div class="text-xs mt-2 px-1 font-medium" :class="checks.python.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.python.result.message"></div>
                </template>
                <template x-if="checks.ffmpeg.result">
                  <div class="text-xs mt-1 px-1 font-medium" :class="checks.ffmpeg.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.ffmpeg.result.message"></div>
                </template>
              </div>
            </div>

            <!-- ═══════════ TAB: SCHEDULER ═══════════ -->
            <div x-show="activeTab === 'scheduler'" x-transition>
              
              <!-- Production Scheduler -->
              <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">🎬 Üretim Zamanlayıcısı</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kuyruktaki videoları sırayla üretir</p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-medium" :class="schedulerStatus.production?.running ? 'text-green-600' : 'text-gray-400'">
                      <template x-if="schedulerStatus.production?.running">
                        <span>🟢 Çalışıyor</span>
                      </template>
                      <template x-if="!schedulerStatus.production?.running">
                        <span>🔴 Durduruldu</span>
                      </template>
                    </span>
                    <button 
                      @click="toggleScheduler('production')"
                      :disabled="schedulerLoading"
                      class="px-4 py-2 rounded-lg font-medium transition"
                      :class="schedulerStatus.production?.running 
                        ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' 
                        : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'"
                    >
                      <span x-text="schedulerStatus.production?.running ? '⏹️ Durdur' : '▶️ Başlat'"></span>
                    </button>
                  </div>
                </div>
                
                <template x-if="schedulerStatus.production?.started_at">
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Başlangıç: <span x-text="new Date(schedulerStatus.production.started_at).toLocaleString('tr-TR')"></span>
                    <template x-if="schedulerStatus.production?.pid">
                      <span class="ml-2">(PID: <span x-text="schedulerStatus.production.pid"></span>)</span>
                    </template>
                  </p>
                </template>
              </div>
              
              <!-- Social Scheduler -->
              <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">📤 Paylaşım Zamanlayıcısı</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Üretilmiş videoları sosyal medyaya paylaşır</p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-medium" :class="schedulerStatus.social?.running ? 'text-green-600' : 'text-gray-400'">
                      <template x-if="schedulerStatus.social?.running">
                        <span>🟢 Çalışıyor</span>
                      </template>
                      <template x-if="!schedulerStatus.social?.running">
                        <span>🔴 Durduruldu</span>
                      </template>
                    </span>
                    <button 
                      @click="toggleScheduler('social')"
                      :disabled="schedulerLoading"
                      class="px-4 py-2 rounded-lg font-medium transition"
                      :class="schedulerStatus.social?.running 
                        ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' 
                        : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'"
                    >
                      <span x-text="schedulerStatus.social?.running ? '⏹️ Durdur' : '▶️ Başlat'"></span>
                    </button>
                  </div>
                </div>
                
                <template x-if="schedulerStatus.social?.started_at">
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Başlangıç: <span x-text="new Date(schedulerStatus.social.started_at).toLocaleString('tr-TR')"></span>
                    <template x-if="schedulerStatus.social?.pid">
                      <span class="ml-2">(PID: <span x-text="schedulerStatus.social.pid"></span>)</span>
                    </template>
                  </p>
                </template>
              </div>
              
              <!-- Logs -->
              <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800 dark:text-white">📋 Scheduler Logları</h2>
                  <div class="flex gap-2">
                    <button 
                      @click="loadSchedulerLogs()"
                      class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition"
                    >🔄 Yenile</button>
                    <button 
                      @click="clearSchedulerLogs()"
                      class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition"
                    >🗑️ Temizle</button>
                  </div>
                </div>
                
                <div class="bg-gray-900 rounded-lg p-4 max-h-80 overflow-y-auto font-mono text-xs text-green-400">
                  <template x-if="schedulerLogs.length === 0">
                    <p class="text-gray-500">Log kaydı yok</p>
                  </template>
                  <template x-for="(log, idx) in schedulerLogs" :key="idx">
                    <div class="whitespace-pre-wrap mb-1" x-text="log"></div>
                  </template>
                </div>
              </div>
              
            </div>

            <!-- ═══════════ TAB: SCRIPT ═══════════ -->
            <div x-show="activeTab === 'script'" x-transition>

              <!-- Enable/Disable -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800">📝 Script Üretimi</h2>
                  <label class="toggle-switch">
                    <input type="checkbox" x-model="toolsEnabled.scriptGen">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <p class="text-xs text-gray-500 mb-4">Haber metninden AI ile video scripti oluşturur.</p>

                <div :class="!toolsEnabled.scriptGen && 'opacity-40 pointer-events-none'">
                  <!-- Sub-tool toggles for Script -->
                  <div class="mb-4 space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Alt Servisler</p>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">🧠 Gemini Script</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.gemini_script"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">🎨 Pollinations Text</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.pollinations_text"><span class="toggle-slider"></span></label>
                    </div>
                  </div>
                  <label class="block mb-1 text-sm font-semibold text-gray-700">Script Sağlayıcı</label>
                  <select x-model="scriptProvider" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 mb-4 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-gray-700">
                    <option value="gemini">🧠 Google Gemini (API Key Gerekli)</option>
                    <option value="pollinations">🎨 Pollinations.ai (Ücretsiz)</option>
                  </select>

                  <template x-if="scriptProvider === 'gemini'">
                    <div>
                      <label class="block mb-1 text-sm font-semibold text-gray-700">Gemini Model</label>
                      <select x-model="geminiModel" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-gray-700">
                        <option value="gemini-2.0-flash">Gemini 2.0 Flash (Hızlı)</option>
                        <option value="gemini-2.0-flash-lite">Gemini 2.0 Flash Lite</option>
                        <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
                        <option value="gemini-2.5-pro">Gemini 2.5 Pro (Kaliteli)</option>
                        <option value="gemini-2.5-flash-lite">Gemini 2.5 Flash Lite</option>
                        <option value="gemini-3-flash-preview">Gemini 3 Flash Preview</option>
                        <option value="gemini-3-pro-preview">Gemini 3 Pro Preview</option>
                        <option value="gemini-3.1-flash-lite-preview">Gemini 3.1 Flash Lite Preview</option>
                        <option value="gemini-3.1-pro-preview">Gemini 3.1 Pro Preview</option>
                      </select>
                    </div>
                  </template>

                  <template x-if="scriptProvider === 'pollinations'">
                    <div>
                      <div class="flex items-start gap-3 bg-purple-50 border border-purple-200 rounded-lg px-4 py-3 mb-4">
                        <span class="text-xl">🎨</span>
                        <div>
                          <p class="text-sm font-semibold text-purple-800">Pollinations.ai Text — Ücretsiz</p>
                          <p class="text-xs text-purple-700 mt-0.5">API anahtarı gerekmez. Birden fazla LLM modeli desteklenir.</p>
                        </div>
                      </div>
                      <label class="block mb-1 text-sm font-semibold text-gray-700">Pollinations Text Model</label>
                      <select x-model="pollinationsTextModel" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-gray-700">
                        <option value="openai">OpenAI (Önerilen)</option>
                        <option value="mistral">Mistral</option>
                        <option value="llama">Llama</option>
                        <option value="deepseek">DeepSeek</option>
                        <option value="claude">Claude</option>
                      </select>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Test -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-3">🧪 Script Araçları Testi</h2>
                <div class="space-y-3">
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">Gemini API</span>
                      <template x-if="checks.gemini.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.gemini.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.gemini.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('gemini', geminiKey)" :disabled="checks.gemini.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.gemini.loading">Test Et</span><span x-show="checks.gemini.loading">⏳</span>
                    </button>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">Pollinations Text</span>
                      <template x-if="checks.pollinations_text.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.pollinations_text.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.pollinations_text.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('pollinations_text', '', {model: pollinationsTextModel})" :disabled="checks.pollinations_text.loading" class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.pollinations_text.loading">Test Et</span><span x-show="checks.pollinations_text.loading">⏳</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- ═══════════ TAB: GÖRSEL ═══════════ -->
            <div x-show="activeTab === 'gorsel'" x-transition>

              <!-- Enable/Disable -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800">🖼️ Görsel Üretimi</h2>
                  <label class="toggle-switch">
                    <input type="checkbox" x-model="toolsEnabled.imageGen">
                    <span class="toggle-slider"></span>
                  </label>
                </div>

                <div :class="!toolsEnabled.imageGen && 'opacity-40 pointer-events-none'">
                  <!-- Sub-tool toggles for Image -->
                  <div class="mb-4 space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Alt Servisler</p>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">⚡ Fal.ai FLUX (Önerilen)</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.fal_image"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">🎨 Pollinations.ai Görsel</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.pollinations_image"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">🤗 HuggingFace Görsel</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.huggingface_image"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">📷 Pexels Stok Görsel</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.pexels_image"><span class="toggle-slider"></span></label>
                    </div>
                  </div>
                  <!-- Fal.ai Maliyet Ayarları (API key değil, sadece ayarlar) -->
                  <template x-if="imageService === 'fal' || imageService === 'auto'">
                    <div class="mb-4">
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Fal.ai Görsel Ayarları</label>
                      <div class="grid grid-cols-3 gap-3">
                        <div>
                          <label class="block mb-1 text-xs font-medium text-gray-600">Genişlik</label>
                          <select x-model="falWidth" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
                            <option value="512">512px (~$0.001)</option>
                            <option value="768">768px (~$0.002)</option>
                            <option value="1024">1024px (~$0.003)</option>
                          </select>
                        </div>
                        <div>
                          <label class="block mb-1 text-xs font-medium text-gray-600">Yükseklik</label>
                          <select x-model="falHeight" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
                            <option value="512">512px</option>
                            <option value="768">768px</option>
                            <option value="1024">1024px</option>
                          </select>
                        </div>
                        <div>
                          <label class="block mb-1 text-xs font-medium text-gray-600">Steps</label>
                          <select x-model="falSteps" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
                            <option value="1">1 (En hızlı)</option>
                            <option value="2">2</option>
                            <option value="4">4 (Önerilen)</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </template>

                  <label class="block mb-1 text-sm font-semibold text-gray-700">Servis Tercihi</label>
                  <select x-model="imageService" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 mb-4 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-gray-700">
                    <option value="pollinations">🌸 Pollinations FLUX (Önerilen)</option>
                    <option value="fal">⚡ Fal.ai FLUX (Hızlı &amp; Ucuz)</option>
                    <option value="auto">🔄 Otomatik (Pollinations → Fal → HuggingFace → Pexels)</option>
                    <option value="huggingface">🤗 HuggingFace (SDXL)</option>
                    <option value="pexels">📷 Pexels (Stok Fotoğraf)</option>
                  </select>

                  <template x-if="imageService === 'pollinations' || imageService === 'auto'">
                    <div>
                      <label class="block mb-1 text-sm font-semibold text-gray-700">Pollinations Model</label>
                      <select x-model="pollinationsModel" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-gray-700">
                        <option value="flux">Flux (Önerilen)</option>
                        <option value="turbo">Turbo (Hızlı)</option>
                        <option value="flux-realism">Flux Realism</option>
                        <option value="flux-anime">Flux Anime</option>
                        <option value="flux-3d">Flux 3D</option>
                      </select>
                      <p class="text-xs text-gray-500 mt-1">Haber videoları için <strong>flux</strong> veya <strong>flux-realism</strong> önerilir.</p>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Test -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-3">🧪 Görsel Araçları Testi</h2>
                <div class="space-y-3">
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">⚡ Fal.ai FLUX</span>
                      <template x-if="checks.fal.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.fal.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.fal.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('fal', falKey)" :disabled="checks.fal.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.fal.loading">Test Et</span><span x-show="checks.fal.loading">⏳</span>
                    </button>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">🎨 Pollinations Görsel</span>
                      <template x-if="checks.pollinations_image.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.pollinations_image.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.pollinations_image.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('pollinations_image', '', {model: pollinationsModel})" :disabled="checks.pollinations_image.loading" class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.pollinations_image.loading">Test Et</span><span x-show="checks.pollinations_image.loading">⏳</span>
                    </button>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">🤗 HuggingFace</span>
                      <template x-if="checks.huggingface.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.huggingface.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.huggingface.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('huggingface', hfKey)" :disabled="checks.huggingface.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.huggingface.loading">Test Et</span><span x-show="checks.huggingface.loading">⏳</span>
                    </button>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">📷 Pexels</span>
                      <template x-if="checks.pexels.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.pexels.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.pexels.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('pexels', pexelsKey)" :disabled="checks.pexels.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.pexels.loading">Test Et</span><span x-show="checks.pexels.loading">⏳</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- ═══════════ TAB: SES ═══════════ -->
            <div x-show="activeTab === 'ses'" x-transition>

              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800">🔊 Seslendirme (TTS)</h2>
                  <label class="toggle-switch">
                    <input type="checkbox" x-model="toolsEnabled.ttsGen">
                    <span class="toggle-slider"></span>
                  </label>
                </div>

                <div :class="!toolsEnabled.ttsGen && 'opacity-40 pointer-events-none'">
                  <!-- Sub-tool toggles for TTS -->
                  <div class="mb-4 space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Alt Servisler</p>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">🎙️ ElevenLabs TTS</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.elevenlabs_tts"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="text-sm text-gray-700">🔊 Edge-TTS (Ücretsiz)</span>
                      <label class="toggle-switch"><input type="checkbox" x-model="servicesEnabled.edge_tts"><span class="toggle-slider"></span></label>
                    </div>
                  </div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700">TTS Sağlayıcı</label>
                  <div class="flex gap-4 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" x-model="ttsProvider" value="elevenlabs" class="text-blue-600">
                      <span class="text-sm text-gray-700">ElevenLabs (Profesyonel)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" x-model="ttsProvider" value="edge-tts" class="text-blue-600">
                      <span class="text-sm text-gray-700">Edge TTS (Ücretsiz &amp; Sınırsız)</span>
                    </label>
                  </div>
                  <p class="text-xs text-gray-500">ElevenLabs başarısız olursa otomatik olarak Edge-TTS'e geçer.</p>
                </div>
              </div>

              <!-- Test -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-3">🧪 Ses Araçları Testi</h2>
                <div class="space-y-3">
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">ElevenLabs API</span>
                      <template x-if="checks.elevenlabs.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.elevenlabs.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.elevenlabs.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('elevenlabs', elevenKey)" :disabled="checks.elevenlabs.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.elevenlabs.loading">Test Et</span><span x-show="checks.elevenlabs.loading">⏳</span>
                    </button>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">Edge-TTS</span>
                      <template x-if="checks.edge_tts.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.edge_tts.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.edge_tts.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('edge_tts')" :disabled="checks.edge_tts.loading" class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.edge_tts.loading">Test Et</span><span x-show="checks.edge_tts.loading">⏳</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- ═══════════ TAB: ALTYAZI ═══════════ -->
            <div x-show="activeTab === 'altyazi'" x-transition>
              
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">💬 Altyazı Stili</h2>
                <p class="text-sm text-gray-600 mb-6">Varsayılan altyazı stilini özelleştirin. Bu ayarlar tüm yeni videolar için kullanılacaktır.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Sol Kolon: Ayarlar -->
                  <div class="space-y-4">
                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Yazı Tipi</label>
                      <select x-model="subtitleStyle.FontName" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="Arial">Arial</option>
                        <option value="Helvetica">Helvetica</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Tahoma">Tahoma</option>
                        <option value="Impact">Impact</option>
                        <option value="Comic Sans MS">Comic Sans MS</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Courier New">Courier New</option>
                      </select>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Yazı Boyutu</label>
                      <input type="number" x-model.number="subtitleStyle.FontSize" min="12" max="48" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                      <p class="text-xs text-gray-500 mt-1">Önerilen: 20-26 arası</p>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Yazı Rengi</label>
                      <div class="flex gap-2 items-center">
                        <input type="color" x-model="subtitleStyle.PrimaryColour" class="h-10 w-16 border border-gray-300 rounded cursor-pointer">
                        <input type="text" x-model="subtitleStyle.PrimaryColour" class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono">
                      </div>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Kenarlık Rengi</label>
                      <div class="flex gap-2 items-center">
                        <input type="color" x-model="subtitleStyle.OutlineColour" class="h-10 w-16 border border-gray-300 rounded cursor-pointer">
                        <input type="text" x-model="subtitleStyle.OutlineColour" class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono">
                      </div>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Kenarlık Kalınlığı</label>
                      <input type="range" x-model.number="subtitleStyle.Outline" min="0" max="5" step="1" class="w-full">
                      <div class="flex justify-between text-xs text-gray-500">
                        <span>0 (Yok)</span>
                        <span x-text="subtitleStyle.Outline"></span>
                        <span>5 (Kalın)</span>
                      </div>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Gölge</label>
                      <input type="range" x-model.number="subtitleStyle.Shadow" min="0" max="5" step="1" class="w-full">
                      <div class="flex justify-between text-xs text-gray-500">
                        <span>0 (Yok)</span>
                        <span x-text="subtitleStyle.Shadow"></span>
                        <span>5 (Koyu)</span>
                      </div>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Alt Boşluk (MarginV)</label>
                      <input type="number" x-model.number="subtitleStyle.MarginV" min="20" max="300" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                      <p class="text-xs text-gray-500 mt-1">Altyazının ekranın altından uzaklığı (piksel)</p>
                    </div>

                    <div>
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="subtitleStyle.Bold" :true-value="1" :false-value="0" class="w-4 h-4 accent-blue-600">
                        <span class="text-sm font-semibold text-gray-700">Kalın Yazı</span>
                      </label>
                    </div>
                  </div>

                  <!-- Sağ Kolon: Önizleme -->
                  <div>
                    <div class="sticky top-4">
                      <label class="block mb-2 text-sm font-semibold text-gray-700">Önizleme</label>
                      <div class="relative bg-gray-900 rounded-lg overflow-hidden" style="aspect-ratio: 9/16;">
                        <img src="data:image/svg+xml,%3Csvg width='400' height='711' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='0%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='400' height='711' fill='url(%23g)' /%3E%3C/svg%3E" alt="Preview" class="w-full h-full object-cover">
                        <div class="absolute inset-0 flex items-end justify-center" :style="{ paddingBottom: subtitleStyle.MarginV + 'px' }">
                          <div class="text-center px-4" :style="{
                            fontFamily: subtitleStyle.FontName,
                            fontSize: subtitleStyle.FontSize + 'px',
                            color: subtitleStyle.PrimaryColour,
                            fontWeight: subtitleStyle.Bold ? 'bold' : 'normal',
                            textShadow: `
                              ${Array(subtitleStyle.Outline).fill().map((_, i) => {
                                const offset = i + 1;
                                return `
                                  ${offset}px ${offset}px 0 ${subtitleStyle.OutlineColour},
                                  ${-offset}px ${-offset}px 0 ${subtitleStyle.OutlineColour},
                                  ${offset}px ${-offset}px 0 ${subtitleStyle.OutlineColour},
                                  ${-offset}px ${offset}px 0 ${subtitleStyle.OutlineColour}
                                `.trim();
                              }).join(',')}
                              ${subtitleStyle.Shadow > 0 ? `,${subtitleStyle.Shadow * 2}px ${subtitleStyle.Shadow * 2}px ${subtitleStyle.Shadow * 4}px rgba(0,0,0,0.8)` : ''}
                            `.trim()
                          }">
                            Örnek Altyazı Metni
                          </div>
                        </div>
                      </div>
                      <p class="text-xs text-gray-500 mt-2 text-center">Ayarlar değiştikçe önizleme güncellenir</p>

                      <!-- Hazır Stiller -->
                      <div class="mt-4">
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Hazır Stiller</label>
                        <div class="grid grid-cols-2 gap-2">
                          <button type="button" @click="subtitleStyle = {FontName:'Arial',FontSize:20,PrimaryColour:'#FFFFFF',OutlineColour:'#000000',BorderStyle:3,Outline:2,Shadow:0,MarginV:80,Alignment:2,Bold:0}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-xs font-semibold text-gray-700 transition">Klasik</button>
                          <button type="button" @click="subtitleStyle = {FontName:'Arial',FontSize:24,PrimaryColour:'#FFFFFF',OutlineColour:'#000000',BorderStyle:3,Outline:3,Shadow:1,MarginV:100,Alignment:2,Bold:1}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-xs font-semibold text-gray-700 transition">Kalın</button>
                          <button type="button" @click="subtitleStyle = {FontName:'Arial',FontSize:22,PrimaryColour:'#FFFF00',OutlineColour:'#000000',BorderStyle:1,Outline:2,Shadow:1,MarginV:80,Alignment:2,Bold:1}" class="px-3 py-2 bg-yellow-100 hover:bg-yellow-200 rounded text-xs font-semibold text-yellow-700 transition">Sarı</button>
                          <button type="button" @click="subtitleStyle = {FontName:'Arial',FontSize:26,PrimaryColour:'#FFFFFF',OutlineColour:'#FF0000',BorderStyle:3,Outline:3,Shadow:0,MarginV:120,Alignment:2,Bold:1}" class="px-3 py-2 bg-red-100 hover:bg-red-200 rounded text-xs font-semibold text-red-700 transition">TikTok</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ═══════════ TAB: VIDEO ═══════════ -->
            <div x-show="activeTab === 'video'" x-transition>

              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800">🎬 Video Birleştirme</h2>
                  <label class="toggle-switch">
                    <input type="checkbox" x-model="toolsEnabled.videoCompose">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <p class="text-xs text-gray-500 mb-4">Görseller, ses ve altyazıları birleştirerek final videoyu oluşturur. FFmpeg ve Python gerektirir.</p>

                <div :class="!toolsEnabled.videoCompose && 'opacity-40 pointer-events-none'">
                  <div class="text-sm text-gray-600 space-y-2">
                    <div class="flex items-center gap-2">
                      <span class="text-green-500">✓</span>
                      <span>Çözünürlük: 1080×1920 (Shorts/Reels)</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-green-500">✓</span>
                      <span>Codec: H.264 + AAC</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-green-500">✓</span>
                      <span>FPS: 30</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-green-500">✓</span>
                      <span>Altyazı yakma (SRT → ASS)</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Test -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-3">🧪 Video Araçları Testi</h2>
                <div class="space-y-3">
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">FFmpeg</span>
                      <template x-if="checks.ffmpeg.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.ffmpeg.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.ffmpeg.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('ffmpeg')" :disabled="checks.ffmpeg.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.ffmpeg.loading">Test Et</span><span x-show="checks.ffmpeg.loading">⏳</span>
                    </button>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <span class="text-sm font-medium text-gray-700">Python</span>
                      <template x-if="checks.python.result">
                        <span class="ml-2 text-xs font-medium" :class="checks.python.result.valid ? 'text-green-600' : 'text-red-600'" x-text="checks.python.result.message"></span>
                      </template>
                    </div>
                    <button type="button" @click="testKey('python')" :disabled="checks.python.loading" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold transition disabled:opacity-50">
                      <span x-show="!checks.python.loading">Test Et</span><span x-show="checks.python.loading">⏳</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Save & Test All (global) -->
            <div class="flex gap-3">
              <button type="button" @click="testAllInTab()" class="flex-1 bg-gray-700 hover:bg-gray-800 text-white py-2.5 rounded-lg font-semibold transition text-sm">🔍 Bu Sekmedeki Araçları Test Et</button>
              <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold transition text-sm">💾 Tüm Ayarları Kaydet</button>
            </div>

            <template x-if="saveMsg">
              <div class="p-3 rounded-lg text-sm font-semibold" :class="saveError ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'" x-text="saveMsg"></div>
            </template>
          </form>
        </div>
      </main>
    </div>

    </div>
    <?php include __DIR__ . '/components/_footer.php'; ?>
  </div>
</body>
</html>
