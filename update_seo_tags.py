import os

seo_file = r"c:\Users\Lenovo\Documents\Downloads\Frontend_BB_fixed_v4\src\components\seo\SEO.tsx"
services_file = r"c:\Users\Lenovo\Documents\Downloads\Frontend_BB_fixed_v4\pages\services.tsx"

# 1. Update SEO.tsx
with open(seo_file, "r", encoding="utf-8") as f:
    seo_content = f.read()

old_seo_block = '''  "/services": {
    title: "Digital Solutions, Web Development & AI Automation Services | Blueboxx DA",
    description: "Explore Blueboxx DA enterprise services including custom web development, mobile applications, CRM/ERP platforms, LMS systems, and AI business automation solutions.",
    keywords: "web development company vadodara, custom CRM development, ERP solutions gujarat, AI automation services, LMS platform development, IT outsourcing india",
  },'''

new_seo_block = '''  "/services": {
    title: "IT Services, Web & AI Solutions in Vadodara | Blueboxx DA",
    description: "Accelerate your enterprise with Blueboxx DA. We build custom websites, CRM/ERP platforms, LMS systems, and AI automation in Vadodara. Get a quote today!",
    keywords: "IT services vadodara, web development company vadodara, custom CRM development, ERP solutions gujarat, AI automation services, LMS platform development, software company vadodara",
  },'''

if old_seo_block in seo_content:
    seo_content = seo_content.replace(old_seo_block, new_seo_block)
    with open(seo_file, "w", encoding="utf-8") as f:
        f.write(seo_content)
    print("Updated SEO.tsx successfully!")
else:
    print("Could not find old_seo_block in SEO.tsx")

# 2. Update pages/services.tsx
with open(services_file, "r", encoding="utf-8") as f:
    services_content = f.read()

old_services_seo = '''      <SEO
        title="Digital Solutions, Web Development & AI Automation Services | Blueboxx DA"
        description="Explore Blueboxx DA enterprise services including custom web development, mobile applications, CRM/ERP platforms, LMS systems, and AI business automation solutions."
        keywords="web development company vadodara, custom CRM development, ERP solutions gujarat, AI automation services, LMS platform development, IT outsourcing india"
      />'''

new_services_seo = '''      <SEO
        title="IT Services, Web & AI Solutions in Vadodara | Blueboxx DA"
        description="Accelerate your enterprise with Blueboxx DA. We build custom websites, CRM/ERP platforms, LMS systems, and AI automation in Vadodara. Get a quote today!"
        keywords="IT services vadodara, web development company vadodara, custom CRM development, ERP solutions gujarat, AI automation services, LMS platform development, software company vadodara"
        schema={{
          "@context": "https://schema.org",
          "@type": "Service",
          "serviceType": "Enterprise Web Development, Custom Software & AI Automation",
          "provider": {
            "@type": "LocalBusiness",
            "name": "Blueboxx DA",
            "url": "https://blueboxx.in",
            "address": {
              "@type": "PostalAddress",
              "addressLocality": "Vadodara",
              "addressRegion": "Gujarat",
              "addressCountry": "IN"
            }
          },
          "areaServed": "Vadodara, Gujarat, India",
          "description": "Custom enterprise web development, mobile applications, CRM/ERP platforms, LMS systems, and AI automation solutions in Vadodara."
        }}
      />'''

old_h1 = '''            <motion.h1
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.1 }}
              className="text-3xl sm:text-5xl md:text-7xl lg:text-8xl font-heading font-black text-[#0d1635] tracking-tight leading-[1.12] max-w-7xl mx-auto mb-9 font-sora"
            >
              We Build <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#1b2a6b] via-[#c9a227] to-[#e0b840]">Digital Solutions</span> That Scale Your Business
            </motion.h1>'''

new_h1 = '''            <motion.h1
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.1 }}
              className="text-3xl sm:text-5xl md:text-7xl lg:text-8xl font-heading font-black text-[#0d1635] tracking-tight leading-[1.12] max-w-7xl mx-auto mb-9 font-sora"
            >
              Enterprise <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#1b2a6b] via-[#c9a227] to-[#e0b840]">Web Development</span>, Custom Software & AI Automation Solutions
            </motion.h1>'''

if old_services_seo in services_content:
    services_content = services_content.replace(old_services_seo, new_services_seo)
    print("Replaced SEO block in services.tsx")
else:
    print("Could not match old_services_seo in services.tsx")

if old_h1 in services_content:
    services_content = services_content.replace(old_h1, new_h1)
    print("Replaced H1 block in services.tsx")
else:
    print("Could not match old_h1 in services.tsx")

with open(services_file, "w", encoding="utf-8") as f:
    f.write(services_content)
print("Updated pages/services.tsx successfully!")
