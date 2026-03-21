"""
YouTube OAuth 2.0 Authentication Module
Handles authentication flow, token management, and refresh
"""
import os
import sys
import json
import pickle
from pathlib import Path
from typing import Optional, Dict

try:
    from google.oauth2.credentials import Credentials
    from google_auth_oauthlib.flow import InstalledAppFlow
    from google.auth.transport.requests import Request
    from googleapiclient.discovery import build
except ImportError:
    print("HATA: Google API kütüphaneleri bulunamadı!")
    print("Yüklemek için: pip install google-auth-oauthlib google-api-python-client")
    sys.exit(1)

# YouTube Data API v3 scopes
SCOPES = [
    'https://www.googleapis.com/auth/youtube.upload',
    'https://www.googleapis.com/auth/youtube',
    'https://www.googleapis.com/auth/youtube.force-ssl'
]

class YouTubeAuth:
    """YouTube OAuth 2.0 authentication manager"""
    
    def __init__(self, credentials_dir: str):
        """
        Initialize auth manager
        
        Args:
            credentials_dir: Directory containing client_secrets.json and tokens
        """
        self.credentials_dir = Path(credentials_dir)
        self.credentials_dir.mkdir(parents=True, exist_ok=True)
        self.client_secrets_file = self.credentials_dir / 'client_secrets.json'
    
    def get_credentials(self, channel_id: Optional[str] = None) -> Optional[Credentials]:
        """
        Get valid credentials for YouTube API
        
        Args:
            channel_id: Specific channel ID, if None uses default
            
        Returns:
            Valid Credentials object or None
        """
        token_file = self._get_token_file(channel_id)
        creds = None
        
        # Load existing token
        if token_file.exists():
            try:
                with open(token_file, 'rb') as f:
                    creds = pickle.load(f)
            except Exception as e:
                print(f"Token yükleme hatası: {e}", file=sys.stderr)
        
        # Refresh if expired
        if creds and creds.expired and creds.refresh_token:
            try:
                creds.refresh(Request())
                self._save_token(creds, channel_id)
                print("Token yenilendi", file=sys.stderr)
            except Exception as e:
                print(f"Token yenileme hatası: {e}", file=sys.stderr)
                creds = None
        
        return creds if creds and creds.valid else None
    
    def authenticate(self, channel_id: Optional[str] = None) -> Optional[Credentials]:
        """
        Perform OAuth flow to get new credentials
        
        Args:
            channel_id: Channel ID to associate with token
            
        Returns:
            Valid Credentials object or None
        """
        if not self.client_secrets_file.exists():
            print(f"HATA: {self.client_secrets_file} bulunamadı!", file=sys.stderr)
            print("Google Cloud Console'dan client_secrets.json dosyasını indirin.", file=sys.stderr)
            return None
        
        try:
            flow = InstalledAppFlow.from_client_secrets_file(
                str(self.client_secrets_file),
                scopes=SCOPES
            )
            
            # Run local server for OAuth callback
            creds = flow.run_local_server(
                port=8080,
                prompt='consent',
                authorization_prompt_message='Tarayıcı açılıyor...'
            )
            
            # Save token
            self._save_token(creds, channel_id)
            print("✅ Kimlik doğrulama başarılı!", file=sys.stderr)
            
            return creds
            
        except Exception as e:
            print(f"Kimlik doğrulama hatası: {e}", file=sys.stderr)
            return None
    
    def get_or_authenticate(self, channel_id: Optional[str] = None) -> Optional[Credentials]:
        """
        Get existing credentials or authenticate if needed
        
        Args:
            channel_id: Channel ID
            
        Returns:
            Valid Credentials object or None
        """
        creds = self.get_credentials(channel_id)
        if not creds:
            print("Yeni kimlik doğrulama gerekli...", file=sys.stderr)
            creds = self.authenticate(channel_id)
        return creds
    
    def build_service(self, channel_id: Optional[str] = None):
        """
        Build YouTube API service
        
        Args:
            channel_id: Channel ID
            
        Returns:
            YouTube API service object or None
        """
        creds = self.get_or_authenticate(channel_id)
        if not creds:
            return None
        
        try:
            service = build('youtube', 'v3', credentials=creds)
            return service
        except Exception as e:
            print(f"Service oluşturma hatası: {e}", file=sys.stderr)
            return None
    
    def get_channel_info(self, service) -> Optional[Dict]:
        """
        Get authenticated user's channel information
        
        Args:
            service: YouTube API service
            
        Returns:
            Channel info dict or None
        """
        try:
            request = service.channels().list(
                part='snippet,contentDetails,statistics',
                mine=True
            )
            response = request.execute()
            
            if not response.get('items'):
                print("Kanal bulunamadı!", file=sys.stderr)
                return None
            
            channel = response['items'][0]
            return {
                'channel_id': channel['id'],
                'channel_title': channel['snippet']['title'],
                'thumbnail': channel['snippet']['thumbnails']['default']['url'],
                'subscriber_count': int(channel['statistics'].get('subscriberCount', 0)),
                'video_count': int(channel['statistics'].get('videoCount', 0)),
                'description': channel['snippet'].get('description', '')
            }
            
        except Exception as e:
            print(f"Kanal bilgisi alma hatası: {e}", file=sys.stderr)
            return None
    
    def revoke_credentials(self, channel_id: Optional[str] = None) -> bool:
        """
        Revoke and delete stored credentials
        
        Args:
            channel_id: Channel ID
            
        Returns:
            True if successful
        """
        token_file = self._get_token_file(channel_id)
        try:
            if token_file.exists():
                token_file.unlink()
                print("Token silindi", file=sys.stderr)
            return True
        except Exception as e:
            print(f"Token silme hatası: {e}", file=sys.stderr)
            return False
    
    def _get_token_file(self, channel_id: Optional[str] = None) -> Path:
        """Get token file path for channel"""
        if channel_id:
            return self.credentials_dir / f'{channel_id}_token.pickle'
        return self.credentials_dir / 'default_token.pickle'
    
    def _save_token(self, creds: Credentials, channel_id: Optional[str] = None):
        """Save credentials to file"""
        token_file = self._get_token_file(channel_id)
        try:
            with open(token_file, 'wb') as f:
                pickle.dump(creds, f)
            print(f"Token kaydedildi: {token_file.name}", file=sys.stderr)
        except Exception as e:
            print(f"Token kaydetme hatası: {e}", file=sys.stderr)


def main():
    """CLI test for authentication"""
    import sys
    
    base_dir = Path(__file__).parent.parent.parent
    creds_dir = base_dir / 'data' / 'youtube_credentials'
    
    auth = YouTubeAuth(str(creds_dir))
    
    print("YouTube Kimlik Doğrulama")
    print("=" * 50)
    
    # Build service and get channel info
    service = auth.build_service()
    if service:
        print("\n✅ API servisi hazır!")
        
        # Get channel info
        channel_info = auth.get_channel_info(service)
        if channel_info:
            print(f"\n📺 Kanal: {channel_info['channel_title']}")
            print(f"📊 Aboneler: {channel_info['subscriber_count']:,}")
            print(f"🎬 Videolar: {channel_info['video_count']:,}")
            
            # Save channel info to JSON
            channels_file = base_dir / 'data' / 'youtube_channels.json'
            if channels_file.exists():
                with open(channels_file, 'r', encoding='utf-8') as f:
                    data = json.load(f)
            else:
                data = {'channels': []}
            
            # Update or add channel
            channel_exists = False
            for i, ch in enumerate(data['channels']):
                if ch['channel_id'] == channel_info['channel_id']:
                    data['channels'][i] = {**channel_info, 'is_active': True, 'is_default': True}
                    channel_exists = True
                    break
            
            if not channel_exists:
                data['channels'].append({
                    **channel_info,
                    'is_active': True,
                    'is_default': len(data['channels']) == 0,
                    'connected_at': None
                })
            
            with open(channels_file, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            
            print(f"\n💾 Kanal bilgileri kaydedildi: {channels_file}")
    else:
        print("\n❌ Kimlik doğrulama başarısız!")
        sys.exit(1)


if __name__ == '__main__':
    main()
