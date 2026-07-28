import json
from bs4 import BeautifulSoup
import re

html_content = open(r"C:\Users\urvashi\.gemini\antigravity-ide\brain\6a184f36-ed2f-4999-8982-a78d3a165c6c\.system_generated\steps\924\content.md", "r", encoding="utf-8").read()
soup = BeautifulSoup(html_content, "html.parser")

colleges = []
for item in soup.find_all("div", class_=re.compile("lms_author_expert")):
    img_tag = item.find("img")
    title_tag = item.find("h4")
    
    if img_tag and title_tag:
        img_src = img_tag.get("src", "")
        if img_src and not img_src.startswith("http"):
            img_src = "https://www.blueboxx.in/" + img_src.lstrip("/")
        
        colleges.append({
            "name": title_tag.text.strip(),
            "location": "India",
            "logoUrl": img_src
        })

print(json.dumps(colleges, indent=2))
