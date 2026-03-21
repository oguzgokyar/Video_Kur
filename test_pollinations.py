import sys
import os
sys.path.insert(0, r"c:\Users\user\Documents\GitHub\Antigravity\Video_Kur\python")

from image_gen import (
    generate_image_fal, 
    generate_image_pollinations, 
    generate_text_pollinations,
    estimate_fal_cost,
    FAL_AVAILABLE
)

print("=" * 60)
print("GÖRSEL ÜRETİM API TEST")
print("=" * 60)

# FAL_KEY kontrolü
fal_key = os.environ.get('FAL_KEY')
print(f"\n[Durum] FAL_KEY: {'✓ Ayarlı' if fal_key else '✗ Ayarlanmamış'}")
print(f"[Durum] fal-client: {'✓ Yüklü' if FAL_AVAILABLE else '✗ Yüklü değil'}")

output_dir = r"c:\Users\user\Documents\GitHub\Antigravity\Video_Kur\output"

# Test 1: Fal.ai (eğer API key varsa)
if fal_key and FAL_AVAILABLE:
    print("\n" + "-" * 60)
    print("[TEST 1] Fal.ai FLUX.1 Schnell")
    print("-" * 60)
    
    # Maliyet tahmini
    estimate_fal_cost(768, 768, 1)
    
    prompt = "A beautiful red sunset over calm blue ocean, photorealistic"
    output = os.path.join(output_dir, "test_fal.jpg")
    
    result = generate_image_fal(prompt, output, optimize_cost=True)
    if result and os.path.exists(output):
        print(f"✓ Fal.ai başarılı: {os.path.getsize(output)} bytes")
    else:
        print("✗ Fal.ai başarısız")
else:
    print("\n[ATLANDI] Fal.ai testi - FAL_KEY ayarlanmamış")
    print("API key almak için: https://fal.ai/dashboard/keys")
    print("Ayarlamak için: set FAL_KEY=your-api-key")

# Test 2: Pollinations (ücretsiz)
print("\n" + "-" * 60)
print("[TEST 2] Pollinations.ai (Ücretsiz)")
print("-" * 60)

prompt = "A mountain landscape at sunrise"
output = os.path.join(output_dir, "test_pollinations.jpg")

result = generate_image_pollinations(prompt, output)
if result and os.path.exists(output):
    print(f"✓ Pollinations başarılı: {os.path.getsize(output)} bytes")
else:
    print("✗ Pollinations başarısız (sunucular yoğun olabilir)")

# Test 3: Metin üretimi
print("\n" + "-" * 60)
print("[TEST 3] Pollinations Metin Üretimi")
print("-" * 60)

text_result = generate_text_pollinations(
    prompt="Yapay zeka nedir? Tek cümleyle açıkla.",
    model="openai-fast"
)
if text_result:
    print(f"✓ Metin üretildi: {text_result[:150]}...")
else:
    print("✗ Metin üretilemedi")

print("\n" + "=" * 60)
print("TEST TAMAMLANDI")
print("=" * 60)

# Maliyet özeti
if fal_key:
    print("\n[MALİYET BİLGİSİ]")
    print("Fal.ai FLUX.1 Schnell:")
    print("  - 512x512:  ~$0.001/görsel")
    print("  - 768x768:  ~$0.002/görsel (önerilen)")
    print("  - 1024x1024: ~$0.003/görsel")
    print("  - 100 görsel (768x768): ~$0.18")
