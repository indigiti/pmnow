from __future__ import annotations

import re


def summarize(text: str, max_chars: int = 280) -> dict:
    clean = re.sub(r"\s+", " ", text or "").strip()
    if not clean:
        return {"summary": "", "method": "extractive", "confidence": 0.0}
    sentences = re.split(r"(?<=[.!?])\s+", clean)
    summary = ""
    for sentence in sentences:
        candidate = (summary + " " + sentence).strip()
        if len(candidate) > max_chars and summary:
            break
        summary = candidate[:max_chars].rstrip()
        if len(summary) >= max_chars * 0.65:
            break
    return {"summary": summary, "method": "extractive", "confidence": 0.72}
