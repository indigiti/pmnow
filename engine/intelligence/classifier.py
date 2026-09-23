from __future__ import annotations

import re
from collections import defaultdict
from typing import Any

DEFAULT_RULES: dict[str, list[str]] = {
    "traffic": ["traffic", "diversion", "jam", "congestion", "road closed", "vehicle", "commute", "वाहतूक", "रस्ता बंद"],
    "metro": ["metro", "station", "train service", "मेट्रो"],
    "civic": ["pmc", "pcmc", "municipal", "civic", "pothole", "water supply", "waste", "riverfront"],
    "crime": ["police", "crime", "arrest", "fraud", "theft", "cyber", "पोलीस", "गुन्हा"],
    "weather": ["rain", "rainfall", "monsoon", "weather", "waterlogging", "shower", "पाऊस"],
    "sports": ["match", "tournament", "cricket", "football", "sports", "league"],
    "events": ["festival", "event", "concert", "exhibition", "celebration"],
    "lifestyle": ["food", "restaurant", "cafe", "lifestyle", "fashion", "travel"],
    "education": ["school", "college", "university", "exam", "student", "education"],
    "health": ["hospital", "health", "doctor", "disease", "clinic", "medical"],
    "business": ["business", "market", "startup", "company", "economy", "investment"],
}


def classify(text: str, taxonomy: list[dict[str, Any]] | None = None, rules: list[dict[str, Any]] | None = None) -> dict[str, Any]:
    hay = (text or "").lower()
    scores: dict[str, float] = defaultdict(float)
    signals: dict[str, list[str]] = defaultdict(list)

    # Explicit editorial/source rules have first priority.
    for rule in rules or []:
        if not rule.get("enabled", True):
            continue
        terms = rule.get("contains_any") or []
        matched = [str(t) for t in terms if str(t).lower() in hay]
        if matched and rule.get("category_slug"):
            slug = str(rule["category_slug"])
            scores[slug] += 5.0 + len(matched)
            signals[slug].extend([f"rule:{t}" for t in matched])

    allowed = {str(x.get("slug")) for x in (taxonomy or []) if x.get("slug")}
    for slug, keywords in DEFAULT_RULES.items():
        if allowed and slug not in allowed:
            continue
        for keyword in keywords:
            pattern = re.escape(keyword.lower())
            if re.search(pattern, hay):
                scores[slug] += 1.0 if " " not in keyword else 1.5
                signals[slug].append(keyword)

    if not scores:
        default = next(iter(allowed), "general") if allowed else "general"
        return {"category_slug": default, "confidence": 0.35, "alternatives": [], "signals": ["default"], "decision_source": "default"}

    ordered = sorted(scores.items(), key=lambda x: (-x[1], x[0]))
    best_slug, best_score = ordered[0]
    total = sum(scores.values())
    confidence = min(0.99, 0.52 + (best_score / max(total, 1.0)) * 0.43)
    alternatives = [
        {"category_slug": slug, "score": round(score, 3)}
        for slug, score in ordered[1:4]
    ]
    source = "rule" if any(str(s).startswith("rule:") for s in signals[best_slug]) else "heuristic"
    return {
        "category_slug": best_slug,
        "confidence": round(confidence, 3),
        "alternatives": alternatives,
        "signals": signals[best_slug],
        "decision_source": source,
    }
