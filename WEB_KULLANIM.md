# 🎯 Web Arayüzü Kullanım Kılavuzu

## ✅ Artık Sadece Tarayıcıdan Yönetebilirsiniz!

Terminal kullanmanıza gerek yok. Her şeyi web arayüzünden yapabilirsiniz.

---

## 📱 1. Dashboard - Video Yükleme

### Adım 1: Dashboard'u Açın
```
http://localhost/dashboard.php
```

### Adım 2: Tamamlanmış Videoları Görün
- Yeşil "✅ Tamamlandı" işareti olan videolar hazır
- Her videonun altında **"📺 YouTube Yükleme"** bölümü var

### Adım 3: Video Yükleyin

**İki Seçenek:**

#### A) ⚡ Hemen Yükle (Şimdi)
1. **"⚡ Hemen Yükle"** butonuna tıklayın
2. Onay dialogu çıkar → **"OK"** tıklayın
3. Video otomatik yüklenir (2-5 dakika)
4. **"✅ YouTube'a yüklendi"** mesajı görünür
5. **"🔗 YouTube'da Aç"** butonuna tıklayın

#### B) 📅 Zamanla (İleri bir tarih)
1. **"📅 Zamanla"** butonuna tıklayın
2. Zamanlama sayfası açılır
3. Tarih ve saat seçin
4. **"Zamanla"** butonuna tıklayın
5. Video belirlenen saatte otomatik yüklenecek

---

## 📅 2. Zamanlama Sayfası

### Açış:
```
http://localhost/scheduler.php
```

### Özellikler:

**📋 Zamanlama Kuyruğu Sekmesi**
- Bekleyen yüklemeleri görün
- İptal etmek için **"❌ İptal"** butonuna tıklayın

**📜 Yükleme Geçmişi Sekmesi**
- Yüklenmiş videoları görün
- YouTube linklerine tıklayarak izleyin
- Başarısız yüklemeleri tekrar deneyin

**⚙️ Otomatik Zamanlama Sekmesi**
- Otomatik zamanlama açın
- Günlük video sayısı belirleyin
- Tercih edilen saatleri seçin
- Strateji seçin (Akıllı/Sabit/Rastgele)

---

## 📺 3. YouTube Yönetimi

### Açış:
```
http://localhost/youtube.php
```

### Özellikler:

**🔗 Hesap Bağlama**
- **"+ YouTube Hesabı Bağla"** butonuna tıklayın
- Tarayıcıda OAuth sayfası açılır
- Google hesabınızla giriş yapın
- İzinleri onaylayın
- Otomatik bağlanır!

**📊 Kanal Yönetimi**
- Bağlı kanalları görün
- Varsayılan kanal seçin
- İstenmeyen kanalların bağlantısını kesin

**⚙️ Varsayılan Ayarlar**
- Gizlilik (Public/Unlisted/Private)
- Kategori
- Varsayılan tags
- Abone bildirimi

---

## 🚀 Hızlı Başlangıç (3 Adım)

### 1️⃣ Dashboard'u Açın
```
http://localhost/dashboard.php
```

### 2️⃣ Tamamlanmış Video Bulun
- Yeşil "✅ Tamamlandı" yazısını arayın
- **"📺 YouTube Yükleme"** bölümüne gidin

### 3️⃣ Hemen Yükle
- **"⚡ Hemen Yükle"** butonuna tıklayın
- **"OK"** deyin
- 2-5 dakika bekleyin
- **"🔗 YouTube'da Aç"** linki belirecek
- Tıklayın ve YouTube'da izleyin!

---

## ✅ Artık Terminal YOK!

**Tüm işlemler web arayüzünden:**
- ✅ Video yükleme
- ✅ Zamanlama
- ✅ YouTube hesap yönetimi
- ✅ Geçmiş ve kuyruk takibi
- ✅ Otomatik zamanlama ayarları

**Sadece tıklayın ve izleyin!** 🎉

---

## 💡 İpuçları

### Toplu Yükleme
1. Dashboard → Her video için "Zamanla"
2. Farklı saatler seçin (örn: 2, 4, 6, 8 saat sonra)
3. Scheduler otomatik yükler

### Otomatik Zamanlama
1. Scheduler.php → "Otomatik Zamanlama"
2. Aktif et
3. Yeni videolar otomatik zamanlanır

### Hızlı Test
1. Dashboard → İlk video
2. "Hemen Yükle"
3. 2 dakika bekle
4. "YouTube'da Aç" → İzle

**Başarılar!** 🚀
