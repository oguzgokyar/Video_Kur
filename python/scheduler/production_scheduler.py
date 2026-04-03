"""
Production Scheduler - Passive Mode
Video production is now handled via web UI
This scheduler runs in passive mode for compatibility
"""
import sys
import time
import json
from pathlib import Path
from datetime import datetime

sys.path.insert(0, str(Path(__file__).parent.parent))


class ProductionScheduler:
    """Production scheduler in passive mode"""
    
    def __init__(self, base_dir: str, check_interval: int = 30):
        self.base_dir = Path(base_dir)
        self.check_interval = check_interval
        self.queues_file = self.base_dir / 'data' / 'queues.json'
        
        print("=" * 70)
        print("Production Scheduler - Passive Mode")
        print("=" * 70)
        print(f"Base directory: {base_dir}")
        print(f"Check interval: {check_interval}s")
        print()
        print("INFO: Production scheduler is running in PASSIVE mode")
        print("INFO: Video production: Use web UI (frontend/create.php)")
        print("INFO: Video upload: Handled by social_scheduler")
        print("=" * 70)
    
    def run(self):
        """Main loop - passive monitoring"""
        print()
        print("Scheduler started (passive mode)")
        print("Press Ctrl+C to stop")
        print()
        
        try:
            while True:
                self._check_status()
                time.sleep(self.check_interval)
                
        except KeyboardInterrupt:
            print("\n\nProduction Scheduler stopped")
        except Exception as e:
            print(f"\nError: {e}")
            raise
    
    def _check_status(self):
        """Check system status"""
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        try:
            if self.queues_file.exists():
                with open(self.queues_file, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                
                total_videos = sum(len(q.get('videos', [])) for q in data.get('queues', []))
                active_queues = sum(1 for q in data.get('queues', []) if q.get('is_active', False))
                
                print(f"[{now}] OK | {active_queues} active queue(s) | {total_videos} video(s)", end='\r')
            else:
                print(f"[{now}] WARNING: queues.json not found", end='\r')
        except Exception as e:
            print(f"[{now}] ERROR: {e}", end='\r')


if __name__ == '__main__':
    import argparse
    
    parser = argparse.ArgumentParser(description='Production Scheduler (Passive Mode)')
    parser.add_argument('--interval', type=int, default=30, help='Check interval in seconds')
    args = parser.parse_args()
    
    base_dir = Path(__file__).parent.parent.parent
    scheduler = ProductionScheduler(str(base_dir), args.interval)
    scheduler.run()
