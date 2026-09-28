import urllib.request
import urllib.parse
import ssl
import json
from bs4 import BeautifulSoup

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

results = {}

# 1. robots.txt and sitemap.xml
def check_url(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=10) as res:
            return res.getcode(), res.read().decode('utf-8', errors='ignore')
    except urllib.error.HTTPError as e:
        return e.code, str(e)
    except Exception as e:
        return 0, str(e)

code, robots_txt = check_url('https://blueboxx.in/robots.txt')
results['robots_txt'] = {
    'status_code': code,
    'services_disallowed': '/services' in robots_txt and 'Disallow: /services' in robots_txt,
    'raw': robots_txt[:500]
}

code, sitemap_xml = check_url('https://blueboxx.in/sitemap.xml')
code0, sitemap0_xml = check_url('https://blueboxx.in/sitemap-0.xml')
results['sitemap'] = {
    'sitemap_xml_code': code,
    'sitemap0_xml_code': code0,
    'services_in_sitemap': 'services' in sitemap0_xml or 'services' in sitemap_xml
}

# 2. Redirect behavior checks (http -> https, www vs non-www)
redirect_tests = [
    'http://blueboxx.in/',
    'http://www.blueboxx.in/',
    'https://blueboxx.in/',
    'https://www.blueboxx.in/'
]

redirect_results = {}
for test_url in redirect_tests:
    class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            return None

    opener = urllib.request.build_opener(NoRedirectHandler, urllib.request.HTTPSHandler(context=ctx))
    try:
        req = urllib.request.Request(test_url, headers={'User-Agent': 'Mozilla/5.0'})
        res = opener.open(req, timeout=10)
        redirect_results[test_url] = {'status': res.getcode(), 'location': 'None (200 OK directly)'}
    except urllib.error.HTTPError as e:
        loc = e.headers.get('Location', 'None')
        redirect_results[test_url] = {'status': e.code, 'location': loc}
    except Exception as e:
        redirect_results[test_url] = {'status': 'Error', 'location': str(e)}

results['redirects'] = redirect_results

# 3. Compare title and meta description with homepage and about page
pages_to_check = {
    'homepage': 'https://blueboxx.in/',
    'about': 'https://blueboxx.in/about/',
    'services': 'https://blueboxx.in/services/'
}

page_metadata = {}
for p_name, p_url in pages_to_check.items():
    code, html = check_url(p_url)
    if code == 200:
        sp = BeautifulSoup(html, 'html.parser')
        t = sp.find('title')
        d = sp.find('meta', attrs={'name': 'description'})
        page_metadata[p_name] = {
            'title': t.get_text(strip=True) if t else '',
            'description': d.get('content', '').strip() if d else ''
        }
    else:
        page_metadata[p_name] = {'error': f"HTTP {code}"}

results['page_metadata_comparison'] = page_metadata

# 4. Check blueboxxda.com
code, bbda_html = check_url('https://blueboxxda.com/')
results['blueboxxda_com'] = {
    'status_code': code,
    'is_live': code == 200,
    'snippet': bbda_html[:300] if code == 200 else str(bbda_html)
}

with open('c:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/site_level_results.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, indent=2)

print('Site level checks completed and saved to site_level_results.json')
