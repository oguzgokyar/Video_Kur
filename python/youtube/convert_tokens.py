"""
Convert Pickle YouTube Tokens to JSON Format
Converts old pickle-format token files to JSON for web OAuth compatibility
"""
import os
import sys
import json
import pickle
from pathlib import Path

def convert_pickle_to_json(token_file: Path) -> bool:
    """Convert a pickle token file to JSON format"""
    try:
        # Read pickle
        with open(token_file, 'rb') as f:
            creds = pickle.load(f)
        
        # Extract token data
        token_data = {
            'access_token': creds.token,
            'refresh_token': creds.refresh_token,
            'token_uri': creds.token_uri,
            'client_id': creds.client_id,
            'client_secret': creds.client_secret,
            'scopes': creds.scopes,
            'expiry': creds.expiry.isoformat() if creds.expiry else None
        }
        
        # Create backup
        backup_file = token_file.with_suffix('.json.pickle.backup')
        os.rename(token_file, backup_file)
        print(f"  📦 Backup: {backup_file.name}")
        
        # Write JSON
        with open(token_file, 'w', encoding='utf-8') as f:
            json.dump(token_data, f, indent=2)
        
        print(f"  ✅ Converted: {token_file.name}")
        return True
        
    except Exception as e:
        print(f"  ❌ Failed: {token_file.name} - {e}")
        return False

def main():
    # Get credentials directory
    base_dir = Path(__file__).parent.parent.parent
    creds_dir = base_dir / 'data' / 'youtube_credentials'
    
    if not creds_dir.exists():
        print(f"❌ Credentials directory not found: {creds_dir}")
        return
    
    print(f"🔍 Searching for token files in: {creds_dir}")
    print("=" * 60)
    
    # Find all token files
    token_files = list(creds_dir.glob('*_token.json'))
    
    if not token_files:
        print("✅ No token files found")
        return
    
    print(f"Found {len(token_files)} token file(s)\n")
    
    converted = 0
    skipped = 0
    failed = 0
    
    for token_file in token_files:
        print(f"Processing: {token_file.name}")
        
        # Check if already JSON
        try:
            with open(token_file, 'r', encoding='utf-8') as f:
                json.load(f)
            print(f"  ⏭️  Already JSON format, skipping")
            skipped += 1
            continue
        except (json.JSONDecodeError, UnicodeDecodeError):
            # Not JSON, try to convert
            pass
        
        # Convert pickle to JSON
        if convert_pickle_to_json(token_file):
            converted += 1
        else:
            failed += 1
        
        print()
    
    print("=" * 60)
    print(f"✅ Converted: {converted}")
    print(f"⏭️  Skipped: {skipped}")
    print(f"❌ Failed: {failed}")
    print(f"📊 Total: {len(token_files)}")
    
    if converted > 0:
        print("\n⚠️  IMPORTANT:")
        print("  Backup files (.pickle.backup) were created.")
        print("  You can delete them after verifying tokens work.")
        print("\n  To test: Run a video upload or check Accounts page.")

if __name__ == '__main__':
    main()
