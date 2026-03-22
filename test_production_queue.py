#!/usr/bin/env python3
"""Test script for production queue system"""

import sys
sys.path.insert(0, 'python/scheduler')
from production_queue_manager import ProductionQueueManager

def test_add_job():
    """Test adding a job to production queue"""
    # Use proper file paths instead of base_dir
    manager = ProductionQueueManager(
        queue_file='data/production_queue.json',
        jobs_dir='data/jobs',
        queues_file='data/queues.json'
    )
    
    result = manager.add_to_production_queue(
        job_id='job_test_20260322_001',
        queue_id='youtube-testr-8737ec',
        priority=0
    )
    
    if result:
        print('✅ Test job production queue ya eklendi')
        
        # Check queue status
        queue_items = manager.get_queue_items(status='waiting')
        print(f'📋 Bekleyen videolar: {len(queue_items)}')
        
        if queue_items:
            item = queue_items[0]
            print(f'   Job ID: {item["job_id"]}')
            print(f'   Queue ID: {item["queue_id"]}')
            print(f'   Status: {item["status"]}')
            
        # Test get_next_job
        print('\n🔍 get_next_job test:')
        next_job = manager.get_next_job()
        if next_job:
            print(f'   Next job: {next_job["job_id"]}')
            print(f'   Queue active: {next_job.get("queue_is_active", "unknown")}')
        else:
            print('   No job ready (queue might be inactive)')
            
        return True
    else:
        print('❌ Job eklenemedi')
        return False

if __name__ == '__main__':
    test_add_job()
