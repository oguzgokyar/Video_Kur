"""
Test script for queue reset functionality
"""
import json
import sys
from pathlib import Path
from datetime import datetime

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent))

data_dir = Path(__file__).parent / 'data'
queues_file = data_dir / 'queues.json'

def test_reset_queue():
    """Test the queue reset logic"""
    
    # Load queues
    with open(queues_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    queue = data['queues'][0]
    print(f"\n📊 BEFORE RESET:")
    print(f"   Queue: {queue['name']}")
    print(f"   Total videos: {len(queue['videos'])}")
    
    # Count duplicates
    job_ids = [v['job_id'] for v in queue['videos']]
    duplicates = len(job_ids) - len(set(job_ids))
    print(f"   Duplicate videos: {duplicates}")
    
    # Statistics
    stats = {
        'duplicates_removed': 0,
        'positions_fixed': 0,
        'status_reset': 0
    }
    
    # 1. Remove duplicates
    unique_videos = []
    seen_job_ids = []
    
    for video in queue['videos']:
        job_id = video['job_id']
        if job_id not in seen_job_ids:
            seen_job_ids.append(job_id)
            unique_videos.append(video)
        else:
            stats['duplicates_removed'] += 1
    
    queue['videos'] = unique_videos
    
    # 2. Fix positions
    for idx, video in enumerate(queue['videos']):
        old_position = video.get('position', 0)
        video['position'] = idx + 1
        if old_position != video['position']:
            stats['positions_fixed'] += 1
    
    # 3. Reset processing statuses
    for video in queue['videos']:
        for platform, status in video.get('platform_status', {}).items():
            if isinstance(status, dict):
                if status.get('status') == 'processing':
                    status['status'] = 'pending'
                    stats['status_reset'] += 1
            elif status == 'processing':
                video['platform_status'][platform] = 'pending'
                stats['status_reset'] += 1
        
        if video.get('status') == 'processing':
            video['status'] = 'queued'
            stats['status_reset'] += 1
    
    # 4. Activate queue
    queue['is_active'] = True
    queue['resumed_at'] = datetime.now().isoformat()
    queue['consecutive_fails'] = 0
    if 'paused_at' in queue:
        del queue['paused_at']
    
    # Save
    with open(queues_file, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    
    print(f"\n✅ RESET COMPLETE:")
    print(f"   Duplicates removed: {stats['duplicates_removed']}")
    print(f"   Positions fixed: {stats['positions_fixed']}")
    print(f"   Statuses reset: {stats['status_reset']}")
    print(f"   Final video count: {len(queue['videos'])}")
    print(f"   Queue is now: {'ACTIVE' if queue['is_active'] else 'PAUSED'}")
    
    return stats

if __name__ == '__main__':
    test_reset_queue()
