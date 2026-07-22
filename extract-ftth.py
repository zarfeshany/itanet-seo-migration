# -*- coding: utf-8 -*-
import re
import json
from html import unescape

with open(r"C:\Users\Itanet\Desktop\Itanet\ftth-source.html", "r", encoding="utf-8") as f:
    html = f.read()

def meta_content(name=None, prop=None):
    if name:
        m = re.search(rf'<meta\s+name="{re.escape(name)}"\s+content="([^"]*)"', html, re.I)
    else:
        m = re.search(rf'<meta\s+property="{re.escape(prop)}"\s+content="([^"]*)"', html, re.I)
    return unescape(m.group(1)) if m else ""

title_match = re.search(r"<title>([^<]+)</title>", html, re.I)
full_title = title_match.group(1).strip() if title_match else ""
post_title = re.split(r"\s*\|\s*", full_title)[0].strip()

article_match = re.search(
    r'<article\s+class="entry-detail">\s*<div\s+class="content-text\s+clearfix">(.*?)</div>\s*</article>',
    html,
    re.S | re.I,
)
if not article_match:
    raise SystemExit("Article content not found")
post_content = article_match.group(1).strip()

# Find itanet.ir images in content
itanet_imgs = re.findall(
    r'(?:src|href)=["\']((?:https?://itanet\.ir)?(/[^"\']+\.(?:png|jpg|jpeg|gif|webp|svg|PNG|Jpg)))["\']',
    post_content,
    re.I,
)

data = {
    "full_title": full_title,
    "post_title": post_title,
    "meta_description": meta_content(name="description"),
    "og_title": meta_content(prop="og:title"),
    "og_description": meta_content(prop="og:description") or meta_content(name="og:description"),
    "og_image": meta_content(prop="og:image"),
    "post_content": post_content,
    "itanet_images": [{"full": a or f"https://itanet.ir{b}", "path": b} for a, b in itanet_imgs],
}

with open(r"C:\Users\Itanet\Desktop\Itanet\ftth-data.json", "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Title: {post_title}")
print(f"Content length: {len(post_content)}")
print(f"Itanet images in content: {len(data['itanet_images'])}")
print(f"Meta description empty: {not data['meta_description']}")
