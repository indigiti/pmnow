from __future__ import annotations

import re

DEVANAGARI_RE = re.compile(r"[\u0900-\u097F]")
MARATHI_MARKERS = {"आहे", "आणि", "पुणे", "मध्ये", "वाहतूक", "पोलीस", "रस्ता", "पाऊस", "महाराष्ट्र"}
HINDI_MARKERS = {"है", "और", "में", "यातायात", "पुलिस", "बारिश", "सड़क", "भारत"}


def detect_language(text: str) -> dict:
    clean = (text or "").strip()
    if not clean:
        return {"language": "und", "confidence": 0.0, "signals": []}
    devanagari = DEVANAGARI_RE.findall(clean)
    latin = sum(1 for c in clean if c.isascii() and c.isalpha())
    if not devanagari:
        return {"language": "en", "confidence": 0.96 if latin > 10 else 0.75, "signals": ["latin_script"]}
    words = set(clean.split())
    mr = len(words & MARATHI_MARKERS)
    hi = len(words & HINDI_MARKERS)
    devanagari_ratio = len(devanagari) / max(1, len(clean))
    if latin > 8 and devanagari_ratio < 0.35:
        return {"language": "mixed", "confidence": 0.78, "signals": ["latin_and_devanagari"]}
    if mr > hi:
        return {"language": "mr", "confidence": min(0.98, 0.72 + mr * 0.06), "signals": sorted(words & MARATHI_MARKERS)}
    if hi > mr:
        return {"language": "hi", "confidence": min(0.98, 0.72 + hi * 0.06), "signals": sorted(words & HINDI_MARKERS)}
    return {"language": "mr-hi", "confidence": 0.55, "signals": ["devanagari_script"]}
