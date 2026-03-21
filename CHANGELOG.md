# 📜 Video_Kur - Değişiklik Günlüğü (CHANGELOG)

Bu dosya, projedeki tüm önemli değişiklikleri kronolojik sırayla takip eder.

---

## [2.0.0] - 2026-03-19 - Custom Script Sistemi

### ✨ Eklenenler
- **10 yeni video efekti:**
  - `ken_burns_zoom_in` - Yavaş zoom in + pan
  - `ken_burns_zoom_out` - Yavaş zoom out + pan
  - `zoom_in_fast` - Hızlı zoom in (heyecan, aciliyet)
  - `zoom_out_fast` - Hızlı zoom out (dramatik açılış)
  - `pulse` - Hafif nabız efekti (vurgular)
  - `pulse_strong` - Güçlü nabız (CTA, outro)
  - `pan_left` - Sola kaydırma (geçmiş, nostaljik)
  - `pan_right` - Sağa kaydırma (ilerleme, gelecek)
  - `static` - Hareketsiz (metin, istatistik)
  - `glitch_transition` - Glitch geçiş (teknoloji, modern)

- **7 ses profili:**
  - `neutral` - Nötr, standart haber tonu
  - `excited` - Heyecanlı, enerjik
  - `urgent` - Acil, hızlı
  - `serious` - Ciddi, otoriter
  - `calm` - Sakin, yatıştırıcı
  - `dramatic` - Dramatik, duygusal
  - `cheerful` - Neşeli, pozitif

- AI'nın sahne bazında efekt ve ses profili seçmesi
- ElevenLabs ses profili parametreleri (stability, similarity_boost, style)
- Edge-TTS rate ve pitch ayarları ile profil simülasyonu

### 🔧 Değişiklikler
- `script_gen.py` - AI prompt'a efekt ve profil seçenekleri eklendi
- `video_composer.py` - 10 efekt kodlandı
- `tts_engine.py` - Ses profili sistemi eklendi
- `pipeline.py` - Parametre aktarımı güncellendi
- `frontend/scripts.php` - Varsayılan template güncellendi

---

## [1.9.0] - 2026-03-19 - Altyazı Stili Yönetimi

### ✨ Eklenenler
- Ayarlar sayfasına "Altyazı" tabı
- Görsel altyazı stil editörü
- Canlı önizleme desteği
- 4 hazır stil şablonu (Classic, Bold, Yellow, TikTok)
- Job bazlı özel stil desteği
- Hex renk desteği (#FFFFFF formatı)
- ASS formatına otomatik dönüşüm

### 🔧 Değişiklikler
- `frontend/settings.php` - Altyazı sekmesi eklendi
- `data/config.json` - subtitleStyle objesi eklendi
- `python/video_composer.py` - Stil parametreleri entegre edildi
- `python/pipeline.py` - Config okuma güncellendi

---

## [1.8.0] - 2026-03-18 - Çoklu Platform Desteği

### ✨ Eklenenler
- TikTok Content Posting API entegrasyonu (başvuru gerekli)
- Instagram Reels desteği (Meta Graph API)
- Facebook Reels desteği (Meta Graph API)
- Platform-spesifik metadata optimizasyonu
- Çoklu platform zamanlama kuyruğu
- `start_social_scheduler.bat` - Sosyal medya scheduler
- `api/social.php` - Çoklu platform API endpoint
- `python/social/` - Platform uploader modülleri

### 📁 Yeni Dosyalar
- `SOCIAL_MEDIA_README.md` - Sosyal medya kurulum rehberi
- `python/social/base.py` - Base uploader class
- `python/social/platform_optimizer.py` - Metadata optimizer
- `python/social/tiktok/` - TikTok modülleri
- `python/social/instagram/` - Instagram modülleri
- `python/social/facebook/` - Facebook modülleri
- `python/scheduler/social_scheduler.py`
- `python/scheduler/social_queue_manager.py`

---

## [1.7.0] - 2026-03-18 - YouTube Otomatik Yükleme

### ✨ Eklenenler
- YouTube Data API v3 entegrasyonu
- OAuth 2.0 kimlik doğrulama
- Otomatik video yükleme
- Akıllı zamanlama sistemi
- AI-powered metadata optimizasyonu (Gemini)
- Yükleme kuyruğu ve geçmişi
- Retry mekanizması (3 deneme)

### 📁 Yeni Dosyalar
- `YOUTUBE_README.md` - YouTube kurulum rehberi
- `VIDEO_UPLOAD_PLAN.md` - Yükleme planı
- `QUICKSTART.md` - Hızlı başlangıç
- `python/youtube/auth.py`
- `python/youtube/uploader.py`
- `python/youtube/metadata_optimizer.py`
- `python/scheduler/scheduler.py`
- `python/scheduler/queue_manager.py`
- `python/scheduler/timing_optimizer.py`
- `api/youtube.php`
- `api/scheduler.php`
- `frontend/youtube.php`
- `frontend/scheduler.php`
- `start_scheduler.bat`
- `test_upload.bat`

---

## [1.6.0] - 2026-03-17 - Kuyruk Yönetimi

### ✨ Eklenenler
- Çoklu yayın kuyruğu sistemi
- Video sıralama ve yeniden sıralama
- Platform bazlı durum takibi
- Zamanlama tipleri (Hemen, Aralıklı, Belirli saatler)
- Kuyruk CRUD operasyonları

### 📁 Yeni Dosyalar
- `api/queues.php`
- `frontend/queues.php`

---

## [1.5.0] - 2026-03-16 - Görsel Versiyon Sistemi

### ✨ Eklenenler
- Görsel versiyon takibi
- Önceki versiyonlara geri dönüş
- Aktif versiyon seçimi
- `image_versions.json` metadata dosyası

### 📁 Yeni Dosyalar
- `api/image_versions.php`

---

## [1.4.0] - 2026-03-15 - Seçici Yeniden Üretim

### ✨ Eklenenler
- Pipeline bölümlerini seçici yeniden üretme
- Desteklenen bölümler: news, script, images, image_single, tts, subtitles, video
- Prompt güncelleme desteği
- Hook/outro/thumbnail prompt düzenleme

### 📁 Yeni Dosyalar
- `api/regenerate.php`
- `python/regenerate.py`

---

## [1.3.0] - 2026-03-14 - Script Şablon Sistemi

### ✨ Eklenenler
- Yeniden kullanılabilir script şablonları
- İçerik tipi kategorileri (genel, haber, eğlence)
- Varsayılan şablon seçimi
- Script CRUD operasyonları

### 📁 Yeni Dosyalar
- `api/scripts.php`
- `frontend/scripts.php`
- `data/scripts.json`

---

## [1.2.0] - 2026-03-13 - Çoklu Görsel Servisi

### ✨ Eklenenler
- Fal.ai FLUX.1 Schnell entegrasyonu
- Pollinations.ai desteği
- HuggingFace entegrasyonu
- Pexels stock foto desteği
- Otomatik fallback mekanizması
- Maliyet optimizasyonu

### 🔧 Değişiklikler
- `python/image_gen.py` - Çoklu servis desteği

---

## [1.1.0] - 2026-03-12 - TTS Geliştirmeleri

### ✨ Eklenenler
- ElevenLabs premium TTS
- Edge-TTS ücretsiz alternatif
- Otomatik fallback
- Async TTS desteği

### 🔧 Değişiklikler
- `python/tts_engine.py` - Dual TTS sistemi

---

## [1.0.0] - 2026-03-10 - İlk Sürüm

### ✨ Eklenenler
- Haber URL'sinden video üretimi
- Gemini AI script üretimi
- Temel görsel üretimi
- Video birleştirme (MoviePy)
- Altyazı oluşturma
- Web dashboard
- İş durumu takibi
- Pause/Resume desteği

### 📁 Temel Dosyalar
- `python/scraper.py`
- `python/script_gen.py`
- `python/image_gen.py`
- `python/tts_engine.py`
- `python/subtitle_gen.py`
- `python/video_composer.py`
- `python/pipeline.py`
- `api/jobs.php`
- `api/config.php`
- `api/check.php`
- `frontend/dashboard.php`
- `frontend/create.php`
- `frontend/project.php`
- `frontend/settings.php`

---

## Versiyon Numaralandırma

- **Major (X.0.0):** Büyük özellik eklemeleri, breaking changes
- **Minor (0.X.0):** Yeni özellikler, geriye uyumlu
- **Patch (0.0.X):** Bug fix, küçük iyileştirmeler

---

## Planlanan Özellikler

### v2.1.0
- [ ] Toplu video üretimi
- [ ] A/B test desteği

### v2.2.0
- [ ] Analytics dashboard
- [ ] Performans metrikleri

### v3.0.0
- [ ] Çoklu dil desteği
- [ ] Özel ses klonlama
- [ ] Otomatik trend analizi

---

*Bu dosya, proje değişikliklerini takip etmek için oluşturulmuştur.*
