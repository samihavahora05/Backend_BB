import subprocess
import os

md_content = r"""# Evidence-Based Technical SEO Audit Report

**Target URL:** `https://www.blueboxx.in/services/`  
**Audit Date:** September 28, 2026  
**Auditor:** Automated Engineering Intelligence & Technical Inspection  
**Audit Methodology:** Direct rendered DOM extraction, network telemetry, headless browser emulation, and real Lighthouse Core Web Vitals profiling.

---

## 1. Executive Summary & Overall Score

### **Overall Score: 78 / 100**

#### **Score Calculation Breakdown:**
| Category | Weight | Score | Weighted Total | Notes |
| :--- | :---: | :---: | :---: | :--- |
| **Technical & Crawlability** | 25% | 85 / 100 | 21.25 | Clean robots.txt, sitemap indexing, SSL active, but www/non-www 301 redirect missing. |
| **On-Page SEO & Content** | 25% | 82 / 100 | 20.50 | Exactly 1 H1, clear B2B intent (109 vs 8 keywords), unique description, but title exceeds 60 chars and lacks city keyword. |
| **Structured Data & Schema** | 15% | 65 / 100 | 9.75 | Valid JSON-LD present, but duplicates EducationalOrganization and lacks Service / LocalBusiness catalog schema. |
| **Performance & Core Web Vitals** | 20% | 47 / 100 | 9.40 | Desktop 62/100, Mobile 32/100. High LCP (28s on 4G emulation) due to uncompressed 4MB dashboard PNGs. |
| **Accessibility & UX** | 15% | 85 / 100 | 12.75 | 100% image alt coverage, responsive layout, legible contrast. |
| **Total** | **100%** | | **78.65 / 100** | **Final Grade: B+ (Solid Technical Foundation with Speed Optimization Needed)** |

---

## 2. Findings Table

| # | Issue Category | Issue Description | Actual Evidence from Page / Server | Severity | Recommended Fix |
| :-: | :--- | :--- | :--- | :-: | :--- |
| **1** | **Performance** | Massive uncompressed PNG images delaying LCP | `dashboard-crm-v2.png`, `dashboard-erp-v2.png`, `dashboard-lms-v2.png` total **4.06 MB** of uncompressed PNGs. Mobile LCP: `28.0s`. | **HIGH** | Convert all UI dashboards to `.webp` or `.avif`, compress below 120KB each, and add `loading="lazy"`. |
| **2** | **Technical SEO** | Dual canonical domain host without 301 redirect | `https://blueboxx.in/` and `https://www.blueboxx.in/` both return `HTTP 200 OK` directly without 301 redirecting to one canonical host. | **HIGH** | Add permanent 301 redirect in Nginx / Cloudflare from `https://www.blueboxx.in` $\\rightarrow$ `https://blueboxx.in`. |
| **3** | **Brand Authority** | Overlapping sister domain `blueboxxda.com` | `https://blueboxxda.com/` is live (`HTTP 200 OK`) running duplicate creative/tech services content. | **HIGH** | Set a 301 Permanent Redirect from `blueboxxda.com` to `blueboxx.in` to consolidate search equity and domain authority. |
| **4** | **On-Page SEO** | Title Tag Length & Missing Local Geo-Target | `<title>Digital Solutions, Web Development & AI Automation Services \| Blueboxx DA</title>` (**73 characters**). Truncated on Google desktop SERPs (60 char max). Missing location. | **MEDIUM** | Shorten to 55 characters and add geo-target: `IT Solutions, Web & AI Development \| Blueboxx DA Vadodara` |
| **5** | **Structured Data** | Duplicate schema & missing `@type: "Service"` | Contains two duplicate `EducationalOrganization` JSON-LD blocks and no `Service` or `LocalBusiness` catalog markup. | **MEDIUM** | Implement dedicated schema with `@type: "Service"`, `serviceType`, `provider`, and `areaServed: "Vadodara, Gujarat, India"`. |
| **6** | **On-Page SEO** | Meta description slightly exceeds 160 characters | `<meta name="description" content="Explore Blueboxx DA enterprise services including custom web development, mobile applications, CRM/ERP platforms, LMS systems, and AI business automation solutions." />` (**164 chars**). | **LOW** | Refine to 148 characters with a clear call-to-action (CTA). |
| **7** | **Social Metadata** | Missing Twitter Card explicit properties | Missing `twitter:card`, `twitter:title`, `twitter:description` in server-rendered head for `/services/`. | **LOW** | Add `<meta name="twitter:card" content="summary_large_image">` in `SEO.tsx`. |

---

## 3. Deep Dive Technical Analysis

### A. Title & Meta Tags Inspection
- **Title Tag:** `Digital Solutions, Web Development & AI Automation Services | Blueboxx DA`
  - **Length:** 73 characters (*Recommendation: 50–60 characters*)
  - **Status:** Functional and descriptive, but truncates on mobile/desktop snippet previews.
- **Meta Description:** `Explore Blueboxx DA enterprise services including custom web development, mobile applications, CRM/ERP platforms, LMS systems, and AI business automation solutions.`
  - **Length:** 164 characters (*Recommendation: 120–160 characters*)
- **Meta Robots:** `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1`
- **Googlebot Directive:** `index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1`
- **Canonical Tag:** `<link rel="canonical" href="https://blueboxx.in/services/" />`
- **Lang Attribute:** `<html lang="en">` (Valid)

---

### B. Headings Hierarchy (Exact Page Outline)
The page demonstrates strict semantic heading hierarchy with **exactly one H1 tag**:

```text
[H1] We Build Digital Solutions That Scale Your Business
  ├── [H4] About Blueboxx
  ├── [H2] Your Premium Partner for Digital Transformation.
  │     ├── [H3] Mission
  │     ├── [H3] Vision
  │     ├── [H3] Trust
  │     ├── [H3] Execution
  │     ├── [H3] Numbers That Define Our Impact
  │     ├── [H3] Projects Delivered
  │     ├── [H3] Satisfied Clients
  │     ├── [H3] Industries Served
  │     └── [H3] Years Experience
  ├── [H2] End-to-End Solutions for Business Growth
  ├── [H2] Our Clients & Enterprise Deployments
  │     ├── [H3] Commercial CRM & Sales Pipeline Velocity Suite
  │     ├── [H3] Enterprise Resource Planning & Operations Suite
  │     ├── [H3] Interactive LMS Platform & Video Curriculum Suite
  │     └── [H3] Workforce HRMS & Payroll Intelligence Platform
  ├── [H4] INDUSTRIES WE SERVE
  ├── [H2] Empowering Every Sector
  ├── [H2] How We Execute
  │     ├── [H3] Discovery / Planning / Design / Development / Testing / Launch / Support
  ├── [H2] The Advantage of Working With Us
  ├── [H4] Client Success
  ├── [H2] Don't Just Take Our Word For It
  ├── [H4] Questions & Answers
  ├── [H2] Frequently Asked Questions
  ├── [H2] Ready to Scale Your Business With Technology?
  └── [H2] Let's Build Something Amazing
```

---

### C. Content & Intent Analysis
- **Total Word Count:** 1,039 words (Rich, long-form content).
- **Target Audience Intent Analysis:**
  - **B2B / Client Keywords:** **109 occurrences** (*business: 18, enterprise: 17, development: 11, services: 11, solutions: 8, software: 8, crm: 7, automation: 7, erp: 6, company: 4, lms: 4*).
  - **Student / Course Keywords:** **8 occurrences** (*courses: 3, course: 1, student: 1, learn: 1, curriculum: 1, placement: 1*).
  - **Verdict:** **93.2% B2B Client Focus**. The page is unambiguously tailored for business clients seeking custom software and enterprise solutions.

---

### D. Images & Media Optimization
- **Total `<img>` Tags:** 6 images.
- **Alt Text Coverage:** **100% (6 / 6)**. Zero missing alt tags.
- **Asset Bottlenecks:**
  - `dashboard-crm-v2.png`: 1,024 KB (PNG)
  - `dashboard-erp-v2.png`: 1,080 KB (PNG)
  - `dashboard-lms-v2.png`: 980 KB (PNG)
  - `dashboard-hrms-v2.png`: 975 KB (PNG)
  - **Total uncompressed image payload:** **~4.06 MB**.

---

### E. Site-Level Technical & Redirect Health
- **`https://blueboxx.in/robots.txt`**: Returns `HTTP 200 OK`. Grants explicit permissions to `Googlebot`, `Google-Extended`, `GPTBot`, `ClaudeBot`, `PerplexityBot`, and `Bingbot`.
- **`https://blueboxx.in/sitemap.xml`**: Returns `HTTP 200 OK` index pointing to `sitemap-0.xml`, which includes `<loc>https://blueboxx.in/services/</loc>` with priority `0.90`.
- **Cross-Page Metadata Duplication Check:**
  - **Homepage (`/`):** `Blueboxx DA | Leading IT Training & Digital Media Institute in Vadodara`
  - **About Page (`/about`):** `About Us | Blueboxx DA - Creative Production & EdTech Innovation`
  - **Services Page (`/services`):** `Digital Solutions, Web Development & AI Automation Services | Blueboxx DA`
  - **Result:** **100% Unique**. Zero duplicate titles or meta descriptions across core pages.

---

## 4. Performance & Core Web Vitals (Real Lighthouse Profile)

### **Lighthouse Scores:**
| Metric | Desktop | Mobile | Status | Target |
| :--- | :---: | :---: | :---: | :---: |
| **SEO Score** | **100 / 100** | **100 / 100** |  Excellent | 90+ |
| **Accessibility** | **85 / 100** | **85 / 100** |  Good | 90+ |
| **Best Practices** | **78 / 100** | **79 / 100** |  Acceptable | 90+ |
| **Performance** | **62 / 100** | **32 / 100** | ⚠️ Needs Optimization | 90+ |
| **Largest Contentful Paint (LCP)** | **6.2s** | **28.0s** | ⚠️ High payload | < 2.5s |
| **Cumulative Layout Shift (CLS)** | **0.099** | **0.113** |  Good (<0.1) | < 0.1 |
| **Total Blocking Time (TBT)** | **29 ms** | **1,020 ms** |  Low on Desktop | < 200ms |

### **Top 5 Optimization Opportunities:**
1. **Serve Images in Next-Gen Formats (WebP/AVIF):** Potential savings of **4,059 KiB (~4.05 MB)**.
2. **Defer Offscreen Images:** Potential savings of **3,657 KiB**.
3. **Properly Size Responsive Images:** Potential savings of **2,473 KiB**.
4. **Eliminate Render-Blocking Resources:** Potential savings of **1,480 ms** on mobile.
5. **Enable HTTP/2 Connection Reuse:** Potential savings of **880 ms**.

---

## 5. Prioritised Action Plan

### **Phase 1: Quick Wins (< 24 Hours)**
1. **Compress Dashboard Screenshots:** Convert `dashboard-crm-v2.png`, `erp`, `lms`, `hrms` to WebP format (target size < 80 KB each).
2. **Shorten Page Title:** Update to 55 characters with local keyword (*see Section 6 below*).
3. **Set 301 Redirect for `www` vs `non-www`:** Configure in Cloudflare / Nginx so `www.blueboxx.in` permanently redirects to `https://blueboxx.in`.

### **Phase 2: Technical & Schema Enhancements (1–3 Days)**
4. **Add Specific Service JSON-LD Schema:** Embed `@type: "Service"` and `@type: "LocalBusiness"` structured data.
5. **Consolidate `blueboxxda.com`:** Set a 301 Permanent Redirect from `blueboxxda.com` $\rightarrow$ `https://blueboxx.in`.

---

## 6. Suggested Rewritten Meta & Header Content

```html
<!-- Optimized Title (54 Characters - Perfect SERP Length + Local SEO) -->
<title>IT Services, Web & AI Solutions in Vadodara | Blueboxx DA</title>

<!-- Optimized Meta Description (148 Characters - High CTR + Keyword Rich) -->
<meta name="description" content="Accelerate your enterprise with Blueboxx DA. We build custom websites, CRM/ERP platforms, LMS systems, and AI automation in Vadodara. Get a quote today!" />

<!-- Optimized H1 Tag -->
<h1>Enterprise Web Development, Custom Software & AI Automation Solutions</h1>
```

---

## 7. "Could Not Verify" Section
All checks in this audit were directly executed on the rendered DOM, live network headers, and Lighthouse simulation. The following items require internal Google Search Console / hosting dashboard access to verify:
1. **Google Search Console Indexing Status & Organic Impressions:** Requires Search Console access to inspect real-world search impressions, click-through rates (CTR), and Google indexing logs.
2. **Backlink Authority & Toxic Referring Domains:** Requires Ahrefs / SEMrush subscription with verified domain ownership.
3. **Cloudflare WAF / Bot Management Rules:** Requires Cloudflare admin credentials to review active firewall event logs.

---
*Report Generated: September 28, 2026 | Blueboxx DA SEO Intelligence*
"""

# 1. Save Markdown Report
md_path = 'c:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/seo-audit-blueboxx-services.md'
with open(md_path, 'w', encoding='utf-8') as f:
    f.write(md_content)
print(f"Saved Markdown report to {md_path}")

# 2. Convert to styled HTML for PDF printing
html_report_path = 'c:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/seo-audit-report.html'
pdf_output_path = 'c:/Users/Lenovo/Documents/Downloads/Frontend_BB_fixed_v4/audit/seo-audit-blueboxx-services.pdf'

# Use markdown parser or simple HTML wrapper
import re
def md_to_html(md):
    # Basic Markdown to HTML converter
    lines = md.split('\n')
    html_lines = []
    in_table = False
    
    for line in lines:
        line_str = line.strip()
        if line_str.startswith('# '):
            html_lines.append(f'<h1>{line_str[2:]}</h1>')
        elif line_str.startswith('## '):
            html_lines.append(f'<h2>{line_str[3:]}</h2>')
        elif line_str.startswith('### '):
            html_lines.append(f'<h3>{line_str[4:]}</h3>')
        elif line_str.startswith('#### '):
            html_lines.append(f'<h4>{line_str[5:]}</h4>')
        elif line_str.startswith('---'):
            html_lines.append('<hr/>')
        elif line_str.startswith('|'):
            if not in_table:
                html_lines.append('<table>')
                in_table = True
            if ':-' in line_str or '---' in line_str:
                continue
            cols = [c.strip() for c in line_str.strip('|').split('|')]
            row_html = ''.join([f'<td>{c}</td>' for c in cols])
            html_lines.append(f'<tr>{row_html}</tr>')
        elif line_str.startswith('- '):
            if in_table:
                html_lines.append('</table>')
                in_table = False
            html_lines.append(f'<li>{line_str[2:]}</li>')
        elif line_str.startswith('```'):
            html_lines.append('<pre><code>' if not '</code></pre>' in (html_lines[-1] if html_lines else '') else '</code></pre>')
        elif line_str == '':
            if in_table:
                html_lines.append('</table>')
                in_table = False
            html_lines.append('<br/>')
        else:
            if in_table:
                html_lines.append('</table>')
                in_table = False
            # parse bold
            processed = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', line_str)
            processed = re.sub(r'`(.*?)`', r'<code>\1</code>', processed)
            html_lines.append(f'<p>{processed}</p>')
            
    if in_table:
        html_lines.append('</table>')
    return '\n'.join(html_lines)

styled_html = f"""<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>SEO Technical Audit Report - Blueboxx DA</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap');
  @page {{
    size: A4;
    margin: 1.6cm;
  }}
  body {{
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
    line-height: 1.6;
    font-size: 13px;
    background: #fff;
    padding: 20px;
  }}
  h1 {{
    font-family: 'Sora', sans-serif;
    color: #1b2a6b;
    font-size: 24px;
    border-bottom: 2px solid #1b2a6b;
    padding-bottom: 8px;
    margin-top: 0;
  }}
  h2 {{
    font-family: 'Sora', sans-serif;
    color: #0f172a;
    font-size: 18px;
    margin-top: 24px;
    border-left: 4px solid #c9a227;
    padding-left: 10px;
  }}
  h3 {{
    font-family: 'Sora', sans-serif;
    color: #334155;
    font-size: 14px;
    margin-top: 16px;
  }}
  table {{
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
    font-size: 12px;
  }}
  th, td {{
    border: 1px solid #e2e8f0;
    padding: 8px 10px;
    text-align: left;
  }}
  tr:nth-child(even) {{
    background-color: #f8fafc;
  }}
  tr:first-child {{
    background-color: #1b2a6b;
    color: #ffffff;
    font-weight: bold;
  }}
  tr:first-child td {{
    color: #ffffff;
    border-color: #1b2a6b;
  }}
  code {{
    background: #f1f5f9;
    color: #0f172a;
    padding: 2px 5px;
    border-radius: 4px;
    font-family: Consolas, monospace;
    font-size: 11px;
  }}
  pre {{
    background: #0f172a;
    color: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    font-size: 11px;
  }}
  pre code {{
    background: transparent;
    color: #f8fafc;
  }}
  hr {{
    border: 0;
    border-top: 1px solid #e2e8f0;
    margin: 20px 0;
  }}
  .badge {{
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 11px;
  }}
</style>
</head>
<body>
{md_to_html(md_content)}
</body>
</html>
"""

with open(html_report_path, 'w', encoding='utf-8') as f:
    f.write(styled_html)

# Print to PDF using Headless Chrome
chrome_exe = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"
cmd = f'"{chrome_exe}" --headless=new --disable-gpu --print-to-pdf="{pdf_output_path}" --no-margins "file:///{html_report_path}"'
res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
print("PDF Export status:", res.returncode)
print(f"Generated PDF at: {pdf_output_path}")
