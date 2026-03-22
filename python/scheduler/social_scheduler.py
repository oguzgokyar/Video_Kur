"""
Multi-Platform Social Media Scheduler
Background service that processes social media upload queue
"""
import sys
import os
import time
import json
from pathlib import Path
from datetime import datetime, timezone
from typing import Dict, Optional

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from scheduler.social_queue_manager import SocialQueueManager
from social.platform_optimizer import PlatformMetadataOptimizer
from youtube.metadata_optimizer import MetadataOptimizer

# Platform uploaders
from youtube.uploader import YouTubeUploader

# Try to import social uploaders
try:
    from social.tiktok.uploader import TikTokUploader
    TIKTOK_AVAILABLE = True
except ImportError:
    TIKTOK_AVAILABLE = False

try:
    from social.instagram.uploader import InstagramUploader
    INSTAGRAM_AVAILABLE = True
except ImportError:
    INSTAGRAM_AVAILABLE = False

try:
    from social.facebook.uploader import FacebookUploader
    FACEBOOK_AVAILABLE = True
except ImportError:
    FACEBOOK_AVAILABLE = False


class SocialMediaScheduler:
    """Background scheduler for multi-platform social media uploads"""
    
    def __init__(self, base_dir: str, check_interval: int = 60):
        """
        Initialize scheduler
        
        Args:
            base_dir: Base directory of the application
            check_interval: Check interval in seconds (default 1 minute)
        """
        self.base_dir = Path(base_dir)
        self.check_interval = check_interval
        
        # Setup paths
        self.data_dir = self.base_dir / 'data'
        self.jobs_dir = self.data_dir / 'jobs'
        self.creds_dir = self.data_dir / 'social_credentials'
        self.youtube_creds = self.data_dir / 'youtube_credentials'
        
        # Ensure directories exist
        self.creds_dir.mkdir(parents=True, exist_ok=True)
        
        # Initialize queue manager
        self.queue_manager = SocialQueueManager(str(self.data_dir))
        
        # Initialize metadata optimizers
        config = self._load_config()
        gemini_key = config.get('geminiKey')
        gemini_model = config.get('geminiModel', 'gemini-2.0-flash')
        # Sosyal platformlar icin (TikTok, Instagram, Facebook) → caption + hashtags
        self.metadata_optimizer = PlatformMetadataOptimizer(gemini_key, model=gemini_model)
        # YouTube icin → optimize edilmis title + description + tags
        self.yt_metadata_optimizer = MetadataOptimizer(gemini_key=gemini_key, model=gemini_model)
        
        # Initialize uploaders
        self.uploaders = self._init_uploaders()
        
        print(f"[SOCIAL] Scheduler başlatıldı")
        print(f"[SOCIAL] Kontrol aralığı: {check_interval} saniye")
        print(f"[SOCIAL] Base directory: {base_dir}")
        print(f"[SOCIAL] Aktif platformlar: {', '.join(self.uploaders.keys())}")
    
    def _load_config(self) -> Dict:
        """Load application config"""
        config_file = self.data_dir / 'config.json'
        if config_file.exists():
            try:
                with open(config_file, 'r', encoding='utf-8') as f:
                    return json.load(f)
            except:
                pass
        return {}
    
    def _init_uploaders(self) -> Dict:
        """Initialize available platform uploaders"""
        uploaders = {}
        
        # YouTube (always available if configured)
        try:
            uploaders['youtube'] = YouTubeUploader(str(self.youtube_creds))
            print("  [OK] YouTube uploader hazır")
        except Exception as e:
            print(f"  [WARN] YouTube uploader başlatılamadı: {e}")
        
        # TikTok
        if TIKTOK_AVAILABLE:
            try:
                tiktok_creds = self.creds_dir / 'tiktok'
                uploaders['tiktok'] = TikTokUploader(str(tiktok_creds))
                print("  [OK] TikTok uploader hazır")
            except Exception as e:
                print(f"  [WARN] TikTok uploader başlatılamadı: {e}")
        
        # Instagram
        if INSTAGRAM_AVAILABLE:
            try:
                ig_creds = self.creds_dir / 'instagram'
                uploaders['instagram'] = InstagramUploader(str(ig_creds))
                print("  [OK] Instagram uploader hazır")
            except Exception as e:
                print(f"  [WARN] Instagram uploader başlatılamadı: {e}")
        
        # Facebook
        if FACEBOOK_AVAILABLE:
            try:
                fb_creds = self.creds_dir / 'facebook'
                uploaders['facebook'] = FacebookUploader(str(fb_creds))
                print("  [OK] Facebook uploader hazır")
            except Exception as e:
                print(f"  [WARN] Facebook uploader başlatılamadı: {e}")
        
        return uploaders
    
    def run(self):
        """Main scheduler loop"""
        print("\n🚀 Social Media Scheduler çalışıyor...\n")
        
        try:
            while True:
                self.process_queue()
                time.sleep(self.check_interval)
                
        except KeyboardInterrupt:
            print("\n\n[STOP] Scheduler durduruldu")
        except Exception as e:
            print(f"\n❌ Scheduler hatası: {e}")
            raise
    
    def process_queue(self):
        """Process pending items in queue"""
        pending = self.queue_manager.get_pending_items()
        
        if not pending:
            now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            print(f"[{now}] ⏳ Bekleyen yükleme yok", end='\r')
            return
        
        print(f"\n📋 {len(pending)} yükleme işleniyor...")
        
        for item in pending:
            self._process_item(item)
    
    def _get_script_text(self, job_id: str) -> str:
        """output/job_id/script.json dosyasindan seslendirme metnini birlestirerek dondurur.
        Metadata motorunun gercek icerige gore optimizasyon yapabilmesi icin kullanilir."""
        script_file = self.base_dir / 'output' / job_id / 'script.json'
        if not script_file.exists():
            return ''
        try:
            with open(script_file, 'r', encoding='utf-8') as f:
                script = json.load(f)
            parts = []
            if script.get('hook'):
                parts.append(script['hook'])
            for scene in script.get('scenes', []):
                if scene.get('text'):
                    parts.append(scene['text'])
            if script.get('outro'):
                parts.append(script['outro'])
            return ' '.join(parts)
        except Exception:
            return ''

    def _process_item(self, item: Dict):
        """Process single queue item across all pending platforms"""
        queue_id = item['queue_id']
        job_id = item['job_id']
        video_path = item['video_path']
        
        print(f"\n{'='*60}")
        print(f"[PROCESS] Queue item: {queue_id}")
        print(f"🎬 Job ID: {job_id}")
        
        # Check video file exists
        if not os.path.exists(video_path):
            error = f"Video dosyası bulunamadı: {video_path}"
            print(f"❌ {error}")
            # Mark all platforms as failed
            for platform in item['platforms']:
                self.queue_manager.mark_platform_failed(queue_id, platform, error, retry=False)
            return
        
        # Process each pending platform
        for platform in item['platforms']:
            platform_status = item['platform_status'].get(platform, {})
            
            if platform_status.get('status') != 'pending':
                continue  # Skip non-pending platforms
            
            if platform not in self.uploaders:
                self.queue_manager.mark_platform_failed(
                    queue_id, platform, 
                    f"{platform} uploader mevcut değil",
                    retry=False
                )
                continue
            
            self._upload_to_platform(item, platform)
    
    def _upload_to_platform(self, item: Dict, platform: str):
        """Upload video to specific platform"""
        queue_id = item['queue_id']
        video_path = item['video_path']
        base_metadata = item['metadata']
        
        print(f"\n📱 [{platform.upper()}] Yükleniyor...")
        
        # Mark as processing
        self.queue_manager.mark_platform_processing(queue_id, platform)
        
        # Get platform-specific metadata
        platform_meta = item.get('platform_metadata', {}).get(platform, {})

        # Upload oncesi platform-aware SEO metadata uret
        if not platform_meta:
            try:
                # Once social_queue'daki description'i dene (gercek script metni)
                script_text = base_metadata.get('description', '')
                # Yoksa script.json'dan yukle
                if not script_text:
                    script_text = self._get_script_text(item['job_id'])
                # Hala yoksa baslik ile devam et
                if not script_text:
                    script_text = base_metadata.get('title', '')

                original_title = base_metadata.get('title', '')
                base_tags = base_metadata.get('tags', [])
                use_ai = bool(getattr(self.metadata_optimizer, 'gemini_key', None))

                if platform == 'youtube':
                    # YouTube: MetadataOptimizer → title + description + tags
                    print(f"   [META] YouTube icin AI metadata uretiliyor... ({len(script_text)} kar)")
                    platform_meta = self.yt_metadata_optimizer.optimize_metadata(
                        original_title=original_title,
                        script_text=script_text,
                        tags=base_tags,
                        use_ai=use_ai
                    )
                else:
                    # TikTok / Instagram / Facebook: PlatformMetadataOptimizer → caption + hashtags
                    print(f"   [META] {platform.title()} icin AI metadata uretiliyor... ({len(script_text)} kar)")
                    platform_meta = self.metadata_optimizer.optimize_for_platform(
                        platform=platform,
                        original_title=original_title,
                        script_text=script_text,
                        base_tags=base_tags,
                        use_ai=use_ai
                    )
            except Exception as e:
                print(f"   [WARN] Metadata optimizasyonu basarisiz, fallback kullaniliyor: {e}")
                platform_meta = {
                    'title': base_metadata.get('title', 'Video'),
                    'description': base_metadata.get('description', ''),
                    'caption': base_metadata.get('description', base_metadata.get('title', '')),
                    'tags': base_metadata.get('tags', []),
                    'hashtags': base_metadata.get('tags', [])
                }
        
        try:
            uploader = self.uploaders[platform]
            
            if platform == 'youtube':
                # Optimize edilmis title'i kullan (MetadataOptimizer'dan geliyor)
                yt_title = (
                    platform_meta.get('title')
                    or base_metadata.get('title', 'Video')
                )
                yt_description = (
                    platform_meta.get('description')
                    or platform_meta.get('caption', '')
                    or base_metadata.get('description', '')
                )
                yt_tags = (
                    platform_meta.get('tags')
                    or platform_meta.get('hashtags')
                    or base_metadata.get('tags', [])
                )
                print(f"   [YT] Baslik: {yt_title[:60]}")
                result = uploader.upload_video(
                    video_path=video_path,
                    title=yt_title,
                    description=yt_description,
                    tags=yt_tags,
                    privacy_status=base_metadata.get('privacy_status', 'public')
                )
                
                if result and result.get('status') == 'success':
                    self.queue_manager.mark_platform_success(
                        queue_id, platform,
                        result['video_id'],
                        result['video_url']
                    )
                    print(f"   [OK] YouTube başarılı: {result['video_url']}")
                else:
                    error = result.get('error', 'Unknown error') if result else 'Upload failed'
                    self.queue_manager.mark_platform_failed(queue_id, platform, error)
                    print(f"   ❌ YouTube başarısız: {error}")
            
            else:
                # TikTok, Instagram, Facebook
                result = uploader.upload_video(
                    video_path=video_path,
                    caption=platform_meta.get('caption', base_metadata.get('title', '')),
                    hashtags=platform_meta.get('hashtags', [])
                )
                
                if result.success:
                    self.queue_manager.mark_platform_success(
                        queue_id, platform,
                        result.post_id,
                        result.post_url
                    )
                    print(f"   [OK] {platform.title()} başarılı: {result.post_url}")
                else:
                    self.queue_manager.mark_platform_failed(
                        queue_id, platform, result.error,
                        retry='quota' not in str(result.error).lower()
                    )
                    print(f"   ❌ {platform.title()} başarısız: {result.error}")
        
        except Exception as e:
            error = str(e)
            self.queue_manager.mark_platform_failed(queue_id, platform, error, retry=True)
            print(f"   ❌ {platform.title()} exception: {error}")
        
        # Update job file
        self._update_job(item['job_id'], queue_id)
    
    def _update_job(self, job_id: str, queue_id: str):
        """Update job JSON file with social upload status"""
        job_file = self.jobs_dir / f'{job_id}.json'
        
        if not job_file.exists():
            return
        
        try:
            with open(job_file, 'r', encoding='utf-8') as f:
                job = json.load(f)
            
            # Get current status from queue
            status = self.queue_manager.get_job_status(job_id)
            
            if status:
                job['social_upload'] = {
                    'queue_id': queue_id,
                    'status': status['status'],
                    'platforms': status['platforms'],
                    'updated_at': datetime.now(timezone.utc).isoformat()
                }
                
                with open(job_file, 'w', encoding='utf-8') as f:
                    json.dump(job, f, ensure_ascii=False, indent=2)
        
        except Exception as e:
            print(f"   [WARN] Job güncelleme hatası: {e}")


def main():
    """CLI entry point"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Social Media Upload Scheduler')
    parser.add_argument(
        '--base-dir',
        default=str(Path(__file__).parent.parent.parent),
        help='Base directory of the application'
    )
    parser.add_argument(
        '--interval',
        type=int,
        default=60,
        help='Check interval in seconds (default: 60)'
    )
    
    args = parser.parse_args()
    
    scheduler = SocialMediaScheduler(args.base_dir, args.interval)
    scheduler.run()


if __name__ == '__main__':
    main()
