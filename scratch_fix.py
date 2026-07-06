import re
import os
import requests
import urllib.parse
import time

DB_PATH = r'c:\Users\USER\Downloads\swissconnect\swisscontact_db.sql'
LOCAL_DIR = r'c:\Users\USER\Downloads\swissconnect'
BASE_URL = 'https://www.swisscontact.org'

with open(DB_PATH, 'r', encoding='utf-8') as f:
    sql_content = f.read()

# Find all _ari URLs in the database
ari_urls = set(re.findall(r'(?:https://www.swisscontact.org)?(/_ari/[^"\']+)', sql_content))
ari_urls.update(re.findall(r'(/cdn-cgi/image/[^"\']+)', sql_content))

missing_static = [
    '/_Resources/Static/Packages/Internezzo.Neos/Favicon/manifest.json',
    '/_Resources/Static/Packages/Internezzo.Neos/Fonts/mulish-v12-latin-600.woff2',
    '/_Resources/Static/Packages/Internezzo.Neos/Fonts/mulish-v12-latin-700.woff2',
    '/_Resources/Static/Packages/Internezzo.Neos/Fonts/mulish-v12-latin-regular.woff2',
    '/cdn-cgi/challenge-platform/scripts/jsd/main.js'
]

urls_to_download = list(ari_urls) + missing_static

print(f"Found {len(urls_to_download)} missing assets to download.")

headers = {"User-Agent": "Mozilla/5.0"}
for asset_path in urls_to_download:
    if asset_path.startswith('/'):
        url = BASE_URL + asset_path
    else:
        url = asset_path
        
    local_path = urllib.parse.unquote(asset_path.lstrip('/'))
    full_local_path = os.path.join(LOCAL_DIR, local_path.replace('/', os.sep))
    
    if os.path.exists(full_local_path):
        continue
        
    os.makedirs(os.path.dirname(full_local_path), exist_ok=True)
    
    try:
        res = requests.get(url, headers=headers, timeout=5)
        if res.status_code == 200:
            with open(full_local_path, "wb") as out:
                out.write(res.content)
            print(f"Downloaded: {asset_path}")
        else:
            print(f"Failed (HTTP {res.status_code}): {asset_path}")
            # If it fails, touch an empty file to suppress 404s
            with open(full_local_path, "wb") as out:
                out.write(b"")
    except Exception as e:
        print(f"Error downloading {asset_path}: {e}")
        # Touch empty file to prevent 404 flooding
        with open(full_local_path, "wb") as out:
            out.write(b"")

print("Asset download complete.")
