import os
import re
import time
import urllib.parse
import sqlite3
import requests
from bs4 import BeautifulSoup

BASE_URL = "https://www.swisscontact.org"
NEW_DOMAIN = "www.swisscontact.online"
LOCAL_DIR = r"c:\Users\USER\Downloads\swissconnect"

# Ensure directories exist
os.makedirs(LOCAL_DIR, exist_ok=True)

# Initialize SQLite database for local testing and recovery
sqlite_db_path = os.path.join(LOCAL_DIR, "local_fallback.db")
conn = sqlite3.connect(sqlite_db_path)
cursor = conn.cursor()
cursor.execute("""
CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url_path TEXT UNIQUE,
    lang TEXT,
    title TEXT,
    meta_description TEXT,
    content_html TEXT
)
""")
conn.commit()

# Seed URLs to prioritize
url_queue = [
    "/en", "/de", "/fr", "/es",
    "/en/what-we-do", "/en/where-we-work", "/en/get-involved-with-us", "/en/news", "/en/about-us",
    "/de/unsere-arbeit", "/de/wo-wir-arbeiten", "/de/mitwirken", "/de/aktuelles", "/de/ueber-uns",
    "/fr/ce-que-nous-faisons", "/fr/ou-nous-intervenons", "/fr/participer", "/fr/nouvelles", "/fr/a-propos-de-nous",
    "/es/que-hacemos", "/es/donde-trabajamos", "/es/participe", "/es/noticias", "/es/nosotros",
    "/en/footer/contact", "/en/footer/imprint", "/en/footer/privacy-statement", "/en/footer/legal-notice", "/en/footer/media", "/en/footer/newsletter",
    "/de/footer/kontakt", "/de/footer/impressum", "/de/footer/datenschutz", "/de/footer/rechtliche-hinweise", "/de/footer/medien", "/de/footer/newsletter",
    "/fr/footer/contact", "/fr/footer/mentions-legales", "/fr/footer/declaration-de-confidentialite", "/fr/footer/notes-legales", "/fr/footer/medias", "/fr/footer/newsletter",
    "/es/footer/contacto", "/es/footer/aviso-legal", "/es/footer/declaracion-de-privacidad", "/es/footer/notas-legales", "/es/footer/prensa", "/es/footer/boletin-de-noticias"
]

visited_urls = set()

# Load already crawled URLs from SQLite database to resume
cursor.execute("SELECT url_path FROM pages")
for row in cursor.fetchall():
    visited_urls.add(row[0])

headers = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
}

def clean_path(path):
    parsed = urllib.parse.urlparse(path)
    p = parsed.path.rstrip('/')
    if parsed.query and "ajaxDestination" in parsed.query:
        return parsed.path + "?" + parsed.query
    return p

def download_asset(asset_url):
    if not asset_url.startswith("http"):
        asset_url = BASE_URL + asset_url
    
    parsed = urllib.parse.urlparse(asset_url)
    local_path = parsed.path.lstrip('/')
    local_path = urllib.parse.unquote(local_path)
    
    full_local_path = os.path.join(LOCAL_DIR, local_path.replace('/', os.sep))
    if os.path.exists(full_local_path):
        return
    
    os.makedirs(os.path.dirname(full_local_path), exist_ok=True)
    
    # Fast timeouts and single attempt for asset downloads to avoid hanging on 404s/broken links
    try:
        print(f"Downloading asset: {asset_url}")
        res = requests.get(asset_url, headers=headers, timeout=3)
        if res.status_code == 200:
            with open(full_local_path, "wb") as f:
                f.write(res.content)
    except Exception as e:
        print(f"Failed asset download: {asset_url} - {e}")

def process_html_content(html_str, lang):
    # Replace domains
    html_str = html_str.replace("www.swisscontact.org", NEW_DOMAIN)
    html_str = html_str.replace("https://swisscontact.org", f"https://{NEW_DOMAIN}")
    
    # Replace donations block in footer with professional form inquiry link
    en_target = r'<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection"><div class="content__text"><h3>Donations</h3><p>IBAN: <strong>CH60 0020 6206 3134 2301 B</strong>.*?</div></div></div>'
    en_replacement = '''<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection">
          <div class="content__text" style="border-left: 3px solid #F47B20; padding-left: 15px;">
            <h3 style="color: #F47B20; font-weight: bold; margin-bottom: 10px;">Support Our Mission</h3>
            <p style="margin-bottom: 12px; line-height: 1.5; font-size: 14px;">To support our projects or make a donation, please contact our team directly:</p>
            <p style="margin-bottom: 8px; font-size: 14px;">📧 <strong>Email:</strong> <a href="mailto:donations@swisscontact.online" style="color: #F47B20; text-decoration: underline; font-weight: bold;">donations@swisscontact.online</a></p>
            <p style="font-size: 14px;">📝 <strong>Inquiry:</strong> <a href="/en/footer/contact" style="color: #F47B20; text-decoration: underline; font-weight: bold;">Online Contact Form</a></p>
          </div>
        </div></div>'''
       
    de_target = r'<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection"><div class="content__text"><h3>Spenden</h3><p>IBAN: <strong>CH60 0020 6206 3134 2301 B</strong>.*?</div></div></div>'
    de_replacement = '''<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection">
          <div class="content__text" style="border-left: 3px solid #F47B20; padding-left: 15px;">
            <h3 style="color: #F47B20; font-weight: bold; margin-bottom: 10px;">Unterstützen Sie uns</h3>
            <p style="margin-bottom: 12px; line-height: 1.5; font-size: 14px;">Um unsere Projekte zu unterstützen oder eine Spende zu tätigen, kontaktieren Sie uns bitte direkt:</p>
            <p style="margin-bottom: 8px; font-size: 14px;">📧 <strong>E-Mail:</strong> <a href="mailto:donations@swisscontact.online" style="color: #F47B20; text-decoration: underline; font-weight: bold;">donations@swisscontact.online</a></p>
            <p style="font-size: 14px;">📝 <strong>Anfrage:</strong> <a href="/de/footer/kontakt" style="color: #F47B20; text-decoration: underline; font-weight: bold;">Kontaktformular</a></p>
          </div>
        </div></div>'''

    fr_target = r'<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection"><div class="content__text"><h3>Dons</h3><p>IBAN: <strong>CH60 0020 6206 3134 2301 B</strong>.*?</div></div></div>'
    fr_replacement = '''<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection">
          <div class="content__text" style="border-left: 3px solid #F47B20; padding-left: 15px;">
            <h3 style="color: #F47B20; font-weight: bold; margin-bottom: 10px;">Soutenir notre mission</h3>
            <p style="margin-bottom: 12px; line-height: 1.5; font-size: 14px;">Pour soutenir nos projets ou faire un don, veuillez contacter notre équipe directement :</p>
            <p style="margin-bottom: 8px; font-size: 14px;">📧 <strong>E-mail:</strong> <a href="mailto:donations@swisscontact.online" style="color: #F47B20; text-decoration: underline; font-weight: bold;">donations@swisscontact.online</a></p>
            <p style="font-size: 14px;">📝 <strong>Demande:</strong> <a href="/fr/footer/contact" style="color: #F47B20; text-decoration: underline; font-weight: bold;">Formulaire de contact</a></p>
          </div>
        </div></div>'''

    es_target = r'<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection"><div class="content__text"><h3>Donaciones</h3><p>IBAN: <strong>CH60 0020 6206 3134 2301 B</strong>.*?</div></div></div>'
    es_replacement = '''<div class="footer__content footer__centerContent col-lg-4"><div class="neos-contentcollection">
          <div class="content__text" style="border-left: 3px solid #F47B20; padding-left: 15px;">
            <h3 style="color: #F47B20; font-weight: bold; margin-bottom: 10px;">Apoye nuestra misión</h3>
            <p style="margin-bottom: 12px; line-height: 1.5; font-size: 14px;">Para apoyar nuestros proyectos o realizar una donación, póngase en contacto directamente con nosotros:</p>
            <p style="margin-bottom: 8px; font-size: 14px;">📧 <strong>Correo:</strong> <a href="mailto:donations@swisscontact.online" style="color: #F47B20; text-decoration: underline; font-weight: bold;">donations@swisscontact.online</a></p>
            <p style="font-size: 14px;">📝 <strong>Consulta:</strong> <a href="/es/footer/contacto" style="color: #F47B20; text-decoration: underline; font-weight: bold;">Formulario de contacto</a></p>
          </div>
        </div></div>'''

    html_str = re.sub(en_target, en_replacement, html_str, flags=re.DOTALL)
    html_str = re.sub(de_target, de_replacement, html_str, flags=re.DOTALL)
    html_str = re.sub(fr_target, fr_replacement, html_str, flags=re.DOTALL)
    html_str = re.sub(es_target, es_replacement, html_str, flags=re.DOTALL)
    
    return html_str

def crawl_site(max_pages=60):
    global url_queue
    crawled_count = len(visited_urls)
    
    while url_queue and crawled_count < max_pages:
        current_path = url_queue.pop(0)
        cleaned = clean_path(current_path)
        if not cleaned:
            cleaned = "/en"
            
        if cleaned in visited_urls:
            continue
            
        visited_urls.add(cleaned)
        crawled_count += 1
        url = BASE_URL + cleaned
        print(f"[{crawled_count}/{max_pages}] Crawling: {url}")
        
        # Add a small delay between requests
        time.sleep(0.5)
        
        res = None
        for attempt in range(2):
            try:
                res = requests.get(url, headers=headers, timeout=5)
                break
            except Exception as e:
                print(f"Crawl attempt {attempt+1} failed for {url}: {e}")
                time.sleep(1)
                
        if not res or res.status_code != 200:
            print(f"Skipping page (status: {res.status_code if res else 'None'})")
            continue
            
        # Determine language
        lang = "en"
        if cleaned.startswith("/de"):
            lang = "de"
        elif cleaned.startswith("/fr"):
            lang = "fr"
        elif cleaned.startswith("/es"):
            lang = "es"
            
        try:
            soup = BeautifulSoup(res.text, "html.parser")
            
            # Extract page data
            title = soup.title.string if soup.title else ""
            meta_desc_tag = soup.find("meta", attrs={"name": "description"})
            meta_desc = meta_desc_tag.get("content", "") if meta_desc_tag else ""
            
            # Find static assets referenced in this page
            # Stylesheets
            for link in soup.find_all("link", rel="stylesheet", href=True):
                download_asset(link["href"])
            
            # Scripts
            for script in soup.find_all("script", src=True):
                src = script["src"]
                if "usercentrics" not in src and "cloudflare" not in src:
                    download_asset(src)
            
            # Images
            for img in soup.find_all("img", src=True):
                download_asset(img["src"])
                
            for source in soup.find_all("source", srcset=True):
                srcset = source["srcset"]
                for part in srcset.split(','):
                    img_url = part.strip().split(' ')[0]
                    download_asset(img_url)
            
            # Extract from data-ari-params
            for div in soup.find_all(class_=re.compile("img-ari")):
                params_str = div.get("data-ari-params")
                if params_str:
                    try:
                        hash_match = re.search(r"'hash'\s*:\s*'([^']+)'", params_str)
                        name_match = re.search(r"'name'\s*:\s*'([^']+)'", params_str)
                        if hash_match and name_match:
                            h = hash_match.group(1)
                            n = name_match.group(1)
                            h_dir = f"{h[0]}/{h[1]}/{h[2]}/{h[3]}"
                            asset_url = f"/_Resources/Persistent/{h_dir}/{h}/{n}"
                            download_asset(asset_url)
                            download_asset(asset_url + ".webp")
                    except:
                        pass
            
            # Find more links
            # Alternate links
            for alt in soup.find_all("link", rel="alternate", href=True):
                path = urllib.parse.urlparse(alt["href"]).path
                clean_alt = clean_path(path)
                if clean_alt and clean_alt not in visited_urls and clean_alt not in url_queue:
                    url_queue.append(clean_alt)
            
            # Normal links
            for a in soup.find_all("a", href=True):
                href = a["href"]
                if href.startswith("/") or "swisscontact.org" in href:
                    path = urllib.parse.urlparse(href).path
                    if not any(path.lower().endswith(ext) for ext in ['.pdf', '.zip', '.docx', '.xlsx', '.png', '.jpg', '.jpeg', '.svg']):
                        clean_link = clean_path(path)
                        if clean_link and (clean_link.startswith("/en") or clean_link.startswith("/de") or clean_link.startswith("/fr") or clean_link.startswith("/es")):
                            if clean_link not in visited_urls and clean_link not in url_queue:
                                url_queue.append(clean_link)
            
            # AJAX teasers
            for div in soup.find_all(attrs={"data-internezzo-teaser--proxyajaxuri": True}):
                ajax_uri = div["data-internezzo-teaser--proxyajaxuri"]
                url_queue.append(ajax_uri)
            
            # Save page to SQLite directly
            clean_html = process_html_content(res.text, lang)
            cursor.execute(
                "INSERT OR REPLACE INTO pages (url_path, lang, title, meta_description, content_html) VALUES (?, ?, ?, ?, ?)",
                (cleaned, lang, title, meta_desc, clean_html)
            )
            conn.commit()
            
        except Exception as e:
            print(f"Error processing page {url}: {e}")

crawl_site()

# Export SQLite content to SQL dump
sql_dump_path = os.path.join(LOCAL_DIR, "swisscontact_db.sql")
print(f"Generating MySQL dump at {sql_dump_path}...")
cursor.execute("SELECT url_path, lang, title, meta_description, content_html FROM pages")
rows = cursor.fetchall()

with open(sql_dump_path, "w", encoding="utf-8") as f:
    f.write("CREATE TABLE IF NOT EXISTS `pages` (\n")
    f.write("    `id` INT AUTO_INCREMENT PRIMARY KEY,\n")
    f.write("    `url_path` VARCHAR(255) UNIQUE NOT NULL,\n")
    f.write("    `lang` VARCHAR(10) NOT NULL,\n")
    f.write("    `title` VARCHAR(255) NOT NULL,\n")
    f.write("    `meta_description` TEXT,\n")
    f.write("    `content_html` LONGTEXT NOT NULL,\n")
    f.write("    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n")
    f.write(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n")
    
    f.write("INSERT INTO `pages` (`url_path`, `lang`, `title`, `meta_description`, `content_html`) VALUES \n")
    
    values_list = []
    for r in rows:
        path_esc = r[0].replace("'", "''")
        lang_esc = r[1].replace("'", "''")
        title_esc = r[2].replace("'", "''") if r[2] else ""
        desc_esc = r[3].replace("'", "''") if r[3] else ""
        html_esc = r[4].replace("'", "''")
        values_list.append(f"('{path_esc}', '{lang_esc}', '{title_esc}', '{desc_esc}', '{html_esc}')")
        
    f.write(",\n".join(values_list))
    f.write(";\n")

conn.close()
print("All tasks complete!")
