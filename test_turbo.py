import sys, time
sys.path.insert(0, r"c:\Users\user\Documents\GitHub\Antigravity\Video_Kur\python")
from image_gen import generate_image_pollinations

# Wait a bit for API to cool down
print("Waiting 10 seconds for API cooldown...")
time.sleep(10)

result = generate_image_pollinations(
    "sunset", 
    r"c:\Users\user\Documents\GitHub\Antigravity\Video_Kur\test_turbo.jpg", 
    model='turbo'
)
print(f"\nResult: {'SUCCESS' if result else 'FAILED'}")
