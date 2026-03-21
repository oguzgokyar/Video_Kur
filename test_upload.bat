@echo off
REM YouTube Upload - İlk Test
REM Bu script ilk test yüklemesini yapar

echo ========================================
echo   YouTube İlk Test Yüklemesi
echo ========================================
echo.

echo [1/2] Video kontrolü...
if not exist "output\job_69b9e56b244608.61484112\final_video.mp4" (
    echo HATA: Video dosyası bulunamadı!
    pause
    exit /b 1
)
echo OK Video dosyasi mevcut

echo.
echo [2/2] Test yuklemesi baslatiyor...
echo.
echo DIKKAT: Bu video UNLISTED olarak yuklenecek (test amacli)
echo.
pause

cd python
python -m youtube.uploader "..\output\job_69b9e56b244608.61484112\final_video.mp4" "Yapay Zeka Is Yukunu Azaltmiyor mu? #Shorts" "Yapay zeka is yukunuzu azaltacagini mi saniyordunuz? Amazon calisanlari yanildigimizi soyluyor!\n\n#Shorts #YapayZeka #Teknoloji #AI"

echo.
echo ========================================
echo   Test Tamamlandi!
echo ========================================
echo.
echo YouTube Studio'dan kontrol edin:
echo https://studio.youtube.com/
echo.
pause
