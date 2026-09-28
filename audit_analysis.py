import json
import re
import urllib.request
import urllib.parse
from bs4 import BeautifulSoup

html_path = 'c:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/services.html'
with open(html_path, 'r', encoding='utf-8', errors='ignore') as f:
    raw_html = f.read()

soup = BeautifulSoup(raw_html, 'html.parser')

results = {}

# 1. Title
title_tag = soup.find('title')
title_text = title_tag.get_text(strip=True) if title_tag else "MISSING"
results['title'] = {
    'text': title_text,
    'length': len(title_text),
    'target_range_50_60': 50 <= len(title_text) <= 60
}

# 2. Meta Description
meta_desc = soup.find('meta', attrs={'name': 'description'})
desc_text = meta_desc.get('content', '').strip() if meta_desc else "MISSING"
results['meta_description'] = {
    'text': desc_text,
    'length': len(desc_text),
    'target_range_120_160': 120 <= len(desc_text) <= 160
}

# 3. Robots, Canonical, Viewport, Hreflang, Lang
meta_robots = soup.find('meta', attrs={'name': 'robots'})
results['meta_robots'] = meta_robots.get('content', '').strip() if meta_robots else "MISSING"

meta_googlebot = soup.find('meta', attrs={'name': 'googlebot'})
results['meta_googlebot'] = meta_googlebot.get('content', '').strip() if meta_googlebot else "MISSING"

link_canonical = soup.find('link', attrs={'rel': 'canonical'})
results['canonical'] = link_canonical.get('href', '').strip() if link_canonical else "MISSING"

meta_viewport = soup.find('meta', attrs={'name': 'viewport'})
results['viewport'] = meta_viewport.get('content', '').strip() if meta_viewport else "MISSING"

hreflang_tags = soup.find_all('link', attrs={'rel': 'alternate', 'hreflang': True})
results['hreflang'] = [{'lang': tag.get('hreflang'), 'href': tag.get('href')} for tag in hreflang_tags]

html_tag = soup.find('html')
results['lang_attribute'] = html_tag.get('lang', 'MISSING') if html_tag else "MISSING"

# 4. Open Graph & Twitter Cards
og_tags = {}
for meta in soup.find_all('meta'):
    prop = meta.get('property', '')
    if prop.startswith('og:'):
        og_tags[prop] = meta.get('content', '')
results['open_graph'] = og_tags

twitter_tags = {}
for meta in soup.find_all('meta'):
    name = meta.get('name', '')
    if name.startswith('twitter:'):
        twitter_tags[name] = meta.get('content', '')
results['twitter_cards'] = twitter_tags

# 5. Headings Outline
headings = []
for h in soup.find_all(['h1', 'h2', 'h3', 'h4', 'h5', 'h6']):
    headings.append({
        'level': h.name.upper(),
        'text': h.get_text(' ', strip=True)
    })
h1_count = len([h for h in headings if h['level'] == 'H1'])
results['headings'] = {
    'total_h1': h1_count,
    'exact_one_h1': h1_count == 1,
    'outline': headings
}

# 6. Word Count & Keyword density
body = soup.find('body')
body_text = body.get_text(' ', strip=True) if body else ""
words = re.findall(r'\b[A-Za-z0-9\'-]+\b', body_text)
results['word_count'] = len(words)

client_keywords = ['business', 'enterprise', 'software', 'crm', 'erp', 'lms', 'custom', 'solutions', 'clients', 'company', 'development', 'automation', 'outsourcing', 'services']
student_keywords = ['course', 'courses', 'student', 'students', 'learn', 'training', 'classes', 'internship', 'curriculum', 'placement', 'batch', 'enroll']

client_counts = {kw: len(re.findall(r'\b' + kw + r'\b', body_text, re.I)) for kw in client_keywords}
student_counts = {kw: len(re.findall(r'\b' + kw + r'\b', body_text, re.I)) for kw in student_keywords}

results['intent_analysis'] = {
    'client_keywords_frequency': {k: v for k, v in client_counts.items() if v > 0},
    'client_keywords_total': sum(client_counts.values()),
    'student_keywords_frequency': {k: v for k, v in student_counts.items() if v > 0},
    'student_keywords_total': sum(student_counts.values()),
    'primary_intent': 'Clients / B2B Services' if sum(client_counts.values()) > sum(student_counts.values()) else 'Students / Training Courses'
}

# 7. Links Analysis
links = soup.find_all('a', href=True)
internal_links = []
external_links = []

for a in links:
    href = a.get('href', '').strip()
    text = a.get_text(' ', strip=True) or '[Image / Empty]'
    if href.startswith('http://') or href.startswith('https://'):
        if 'blueboxx.in' in href:
            internal_links.append({'href': href, 'text': text})
        else:
            external_links.append({'href': href, 'text': text})
    elif href.startswith('/') or href.startswith('#'):
        internal_links.append({'href': href, 'text': text})

results['links'] = {
    'total': len(links),
    'internal_count': len(internal_links),
    'external_count': len(external_links),
    'sample_internal': internal_links[:10],
    'sample_external': external_links[:10]
}

# 8. Images Analysis
images = soup.find_all('img')
img_list = []
missing_alt_count = 0

for img in images:
    src = img.get('src', '')
    alt = img.get('alt', None)
    loading = img.get('loading', 'eager')
    if alt is None or alt.strip() == '':
        missing_alt_count += 1
    img_list.append({
        'src': src,
        'alt': alt,
        'loading': loading
    })

results['images'] = {
    'total_count': len(images),
    'missing_alt_count': missing_alt_count,
    'images': img_list[:15]
}

# 9. Structured Data (JSON-LD)
json_ld_scripts = soup.find_all('script', type='application/ld+json')
schemas = []
for s in json_ld_scripts:
    try:
        data = json.loads(s.get_text())
        schemas.append(data)
    except Exception as e:
        schemas.append({'error': str(e), 'raw': s.get_text()})

results['json_ld_schemas'] = schemas

# 10. Initial HTML vs JS Client-Side Rendering check
results['initial_html_analysis'] = {
    'has_prerendered_h1': bool(soup.find('h1')),
    'has_prerendered_body_text': len(body_text) > 500,
    'body_text_sample': body_text[:300]
}

with open('c:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/analysis_results.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, indent=2)

print('Analysis completed and saved to analysis_results.json')
print(f"Title: {title_text} ({len(title_text)} chars)")
print(f"Description: {desc_text} ({len(desc_text)} chars)")
print(f"H1 Count: {h1_count}")
print(f"Word Count: {len(words)}")
print(f"Client vs Student Score: {sum(client_counts.values())} vs {sum(student_counts.values())}")
