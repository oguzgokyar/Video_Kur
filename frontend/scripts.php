<?php $page_title = 'Script Yönetimi - YouTube Shorts Otomasyon'; $active_page = 'scripts'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include __DIR__ . '/components/_head.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="scriptManager()">
  <div class="flex flex-col h-screen">
    <?php include __DIR__ . '/components/_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
      <?php include __DIR__ . '/components/_sidebar.php'; ?>

      <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-6xl mx-auto">
          <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Script Yönetimi</h1>
            <button @click="openNewScriptModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Yeni Script
            </button>
          </div>

          <!-- Scripts List -->
          <div class="grid gap-4 md:grid-cols-2">
            <template x-for="script in scripts" :key="script.id">
              <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                  <div class="flex-1">
                    <h3 class="font-bold text-gray-800 text-lg" x-text="script.name"></h3>
                    <p class="text-sm text-gray-500 mt-1" x-text="script.description"></p>
                  </div>
                  <template x-if="script.isDefault">
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Varsayılan</span>
                  </template>
                </div>
                
                <div class="flex gap-2 text-xs text-gray-600 mb-3">
                  <span class="px-2 py-1 bg-gray-100 rounded">📁 <span x-text="script.contentType"></span></span>
                  <span class="px-2 py-1 bg-gray-100 rounded">⏱️ <span x-text="script.maxDuration"></span>s</span>
                </div>
                
                <div class="flex gap-2">
                  <button @click="editScript(script)" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-600 px-3 py-2 rounded-lg text-sm font-semibold transition">
                    ✏️ Düzenle
                  </button>
                  <button @click="deleteScript(script.id)" class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-lg text-sm font-semibold transition">
                    🗑️ Sil
                  </button>
                </div>
              </div>
            </template>

            <template x-if="scripts.length === 0">
              <div class="col-span-2 bg-white rounded-xl border border-gray-200 p-12 text-center">
                <p class="text-gray-500">Henüz script oluşturulmamış. Yukarıdaki butona tıklayarak yeni script ekleyin.</p>
              </div>
            </template>
          </div>
        </div>
      </main>
    </div>

    <?php include __DIR__ . '/components/_footer.php'; ?>
  </div>

  <!-- Modal: Yeni/Düzenle Script -->
  <div x-show="modalOpen" @click.self="closeModal()" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" x-transition>
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b p-4 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800" x-text="editMode ? 'Script Düzenle' : 'Yeni Script'"></h2>
        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form @submit.prevent="saveScript()" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Script Adı *</label>
          <input type="text" x-model="form.name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Örn: Haber Scripti">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Açıklama</label>
          <input type="text" x-model="form.description" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Script hakkında kısa açıklama">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">İçerik Tipi</label>
            <input type="text" x-model="form.contentType" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Örn: haber, eğlence">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Maksimum Süre (sn)</label>
            <input type="number" x-model.number="form.maxDuration" min="10" max="300" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
          </div>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" x-model="form.isDefault" class="w-4 h-4 accent-blue-600">
          <label class="text-sm font-medium text-gray-700">Bu içerik tipi için varsayılan script yap</label>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Prompt *</label>
          <textarea x-model="form.prompt" required rows="16" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono text-sm" placeholder="AI'ya gönderilecek prompt. {{TITLE}}, {{TEXT}}, {{MAX_DURATION}} değişkenlerini kullanabilirsiniz."></textarea>
          <p class="text-xs text-gray-500 mt-1">💡 Kullanılabilir değişkenler: {{TITLE}} (başlık), {{TEXT}} (metin), {{MAX_DURATION}} (süre)</p>
        </div>

        <div class="flex gap-3 pt-4 border-t">
          <button type="button" @click="closeModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg font-semibold transition">
            İptal
          </button>
          <button type="submit" :disabled="saving" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-semibold transition disabled:opacity-50">
            <span x-show="!saving" x-text="editMode ? 'Güncelle' : 'Oluştur'"></span>
            <span x-show="saving">Kaydediliyor...</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function scriptManager() {
    return {
      // App State
      sidebarOpen: false,
      darkMode: localStorage.getItem('darkMode') === '1',
      
      // Scripts State
      scripts: [],
      modalOpen: false,
      editMode: false,
      saving: false,
      form: {
        id: '',
        name: '',
        description: '',
        contentType: 'haber',
        maxDuration: 55,
        isDefault: false,
        prompt: ''
      },

      init() {
        this.loadScripts();
      },
      
      toggleDark() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode ? '1' : '0');
        document.documentElement.classList.toggle('dark', this.darkMode);
      },

      async loadScripts() {
        const res = await fetch('/api/scripts.php');
        const data = await res.json();
        this.scripts = data.scripts || [];
      },

      openNewScriptModal() {
        this.editMode = false;
        this.form = {
          id: '',
          name: '',
          description: '',
          contentType: 'haber',
          maxDuration: 55,
          isDefault: false,
          prompt: `Sen bir profesyonel YouTube Shorts video scripti yazarısın.
Aşağıdaki haber içeriğinden maksimum {{MAX_DURATION}} saniyelik, dikkat çekici bir Türkçe video scripti yaz.

Kurallar:
- Kısa, vurucu cümleler kullan
- Her sahne 5-8 saniye olsun
- Her sahne için: sahne numarası, metin (seslendirme), görsel açıklaması (İngilizce, AI görsel üretimi için) ver
- Hook (giriş) ve Outro (kapanış) için de görsel açıklaması ver
- Thumbnail (video kapak görseli) için de özel görsel açıklaması ver - bu görsel videoyu özetlemeli ve tıklanabilir olmalı
- JSON formatında döndür
- image_prompt: Sahne metninde geçen ürün adı, oyun adı, marka adı, kişi adı, yer adı gibi spesifik bilgileri MUTLAKA İngilizce görsel açıklamasına ekle. Örnek: sahne metni "iPhone 16 tanıtıldı" ise image_prompt "Apple iPhone 16 smartphone product launch promotional photo" olmalı. Genel ifadelerden kaçın, sahneye özel ve somut açıklama yaz.
- camera_effect: Her sahne için hikayeye uygun kamera efekti seç. Seçenekler:
  * "zoom_in": Detaya odaklanma, önemli anlar, dramatik vurgular için (örn: ürün tanıtımı, şok edici detay)
  * "zoom_out": Genel görüntü, bağlam gösterme, finalde büyük resmi gösterme için (örn: manzara, toplam etki)
  * "pan_right": Hareket hissi, ilerleme, gelecek odaklı sahneler için (örn: yol gösterme, pozitif gelişme)
  * "pan_left": Geçmişe bakış, nostaljik anlar, geriye dönüş sahneleri için (örn: hatırlama, önceki durumlar)
- hook_image_prompt: Hook için dikkat çekici, viral içerik tarzında, haberin ana konusunu yansıtan İngilizce görsel açıklaması
- outro_image_prompt: Kapanış için call-to-action, subscribe/like ikonları içeren modern İngilizce görsel açıklaması
- thumbnail_image_prompt: YouTube kapak görseli için dikkat çekici, high quality, professional thumbnail görsel açıklaması (haberin ana konusunu yansıtan, bold text space bırakacak şekilde)

Haber Başlığı: {{TITLE}}
Haber Metni: {{TEXT}}

Yanıtı şu JSON formatında ver (sadece JSON, başka açıklama yazma):
{
  "hook": "Dikkat çekici açılış cümlesi",
  "hook_image_prompt": "Eye-catching viral intro visual with specific news topic elements",
  "hook_camera_effect": "zoom_in",
  "scenes": [
    {
      "scene": 1,
      "text": "Seslendirme metni",
      "image_prompt": "Specific English image description mentioning exact product/brand/game/person from the scene text",
      "camera_effect": "zoom_in",
      "duration": 6
    }
  ],
  "outro": "Kapanış cümlesi",
  "outro_image_prompt": "Video outro with subscribe button, like icon, comment reminder, modern social media style",
  "outro_camera_effect": "zoom_out",
  "thumbnail_image_prompt": "Professional YouTube thumbnail with dramatic lighting, bold colors, eye-catching composition for news topic, space for text overlay"
}`
        };
        this.modalOpen = true;
      },

      editScript(script) {
        this.editMode = true;
        this.form = { ...script };
        this.modalOpen = true;
      },

      closeModal() {
        this.modalOpen = false;
      },

      async saveScript() {
        this.saving = true;
        const method = this.editMode ? 'PUT' : 'POST';
        
        try {
          const res = await fetch('/api/scripts.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          
          if (res.ok) {
            await this.loadScripts();
            this.closeModal();
          } else {
            alert('Hata: Script kaydedilemedi');
          }
        } catch (e) {
          alert('Bağlantı hatası');
        } finally {
          this.saving = false;
        }
      },

      async deleteScript(id) {
        if (!confirm('Bu scripti silmek istediğinize emin misiniz?')) return;
        
        const res = await fetch('/api/scripts.php', {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        
        if (res.ok) {
          await this.loadScripts();
        } else {
          alert('Script silinemedi');
        }
      }
    }
  }
  </script>
</body>
</html>
