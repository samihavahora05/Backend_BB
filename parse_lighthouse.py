import json
import os

mobile_path = 'C:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/lighthouse-mobile.json'
desktop_path = 'C:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/lighthouse-desktop.json'

output = {}

def parse_report(path, label):
    if not os.path.exists(path):
        return {'status': 'not found'}
    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    categories = data.get('categories', {})
    audits = data.get('audits', {})

    scores = {
        'performance': int(categories.get('performance', {}).get('score', 0) * 100),
        'accessibility': int(categories.get('accessibility', {}).get('score', 0) * 100),
        'best_practices': int(categories.get('best-practices', {}).get('score', 0) * 100),
        'seo': int(categories.get('seo', {}).get('score', 0) * 100)
    }

    metrics = {
        'lcp': audits.get('largest-contentful-paint', {}).get('displayValue', 'N/A'),
        'lcp_numeric_ms': audits.get('largest-contentful-paint', {}).get('numericValue', 0),
        'cls': audits.get('cumulative-layout-shift', {}).get('displayValue', 'N/A'),
        'cls_numeric': audits.get('cumulative-layout-shift', {}).get('numericValue', 0),
        'tbt': audits.get('total-blocking-time', {}).get('displayValue', 'N/A'),
        'tbt_numeric_ms': audits.get('total-blocking-time', {}).get('numericValue', 0),
        'fcp': audits.get('first-contentful-paint', {}).get('displayValue', 'N/A'),
        'speed_index': audits.get('speed-index', {}).get('displayValue', 'N/A')
    }

    # Top opportunities
    opportunities = []
    for audit_key, audit_val in audits.items():
        if audit_val.get('details', {}).get('type') == 'opportunity':
            savings = audit_val.get('details', {}).get('overallSavingsMs', 0)
            if savings > 0:
                opportunities.append({
                    'title': audit_val.get('title'),
                    'savings_ms': savings,
                    'displayValue': audit_val.get('displayValue', '')
                })
    opportunities.sort(key=lambda x: x['savings_ms'], reverse=True)

    return {
        'scores': scores,
        'metrics': metrics,
        'top_opportunities': opportunities[:5]
    }

output['mobile'] = parse_report(mobile_path, 'mobile')
output['desktop'] = parse_report(desktop_path, 'desktop')

with open('C:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/lighthouse_summary.json', 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=2)

print('Lighthouse summary parsed and saved.')
print(json.dumps(output, indent=2))
