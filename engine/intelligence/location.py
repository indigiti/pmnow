from __future__ import annotations

import re
from typing import Any

# Curated Pune-area aliases: mapping only, not geocoding or live traffic evidence.
# Prefer exact whole-name matches; do not guess locations from generic terms.
LOCAL_ALIASES: dict[str, tuple[str, ...]] = {
    "pune": ("पुणे",),
    "baner": ("बाणेर",),
    "aundh": ("औंध",),
    "shivajinagar": ("शिवाजी नगर", "शिवाजीनगर"),
    "koregaon-park": ("कोरेगाव पार्क",),
    "kothrud": ("कोथरुड", "कोथरूड"),
    "hadapsar": ("हडपसर",),
    "viman-nagar": ("विमान नगर", "विमाननगर"),
    "wakad": ("वाकड",),
    "katraj": ("कात्रज",),
    "pimpri": ("पिंपरी",),
    "hinjewadi": ("हिंजवडी", "हिंजवाडी"),
}

GENERIC_CITY_SLUGS = {"pune", "pcmc"}


def _contains(text: str, value: str) -> bool:
    normalized = str(value).casefold().replace("-", " ").strip()
    if not normalized:
        return False
    return re.search(r"(?<!\w)" + re.escape(normalized) + r"(?!\w)", text) is not None


def detect_locations(
    text: str, locations: list[dict[str, Any]], title: str = ""
) -> list[dict[str, Any]]:
    """Assign area tags from explicit, boundary-aware place-name mentions.

    Stronger headline matches rank first. The generic city must not outrank a
    directly mentioned neighbourhood. Confidence describes the text match,
    not verified geography, traffic conditions or the accuracy of reporting.
    """
    hay = (text or "").casefold()
    heading = (title or "").casefold()
    # Publisher names do not by themselves establish a story's geography.
    hay = re.sub(r"\bpune\s+mirror\b", " ", hay)
    heading = re.sub(r"\bpune\s+mirror\b", " ", heading)
    hits: list[dict[str, Any]] = []

    for loc in locations or []:
        slug = str(loc.get("slug") or "").strip().casefold()
        names = [loc.get("name"), str(loc.get("slug") or "").replace("-", " ")]
        names.extend(loc.get("aliases") or [])
        names.extend(LOCAL_ALIASES.get(slug, ()))

        signals: list[str] = []
        headline_hit = False
        seen_names: set[str] = set()
        for item in names:
            if not item:
                continue
            name = str(item).strip()
            canonical = name.casefold()
            if not name or canonical in seen_names:
                continue
            seen_names.add(canonical)
            if _contains(hay, name):
                signals.append(name)
                headline_hit = headline_hit or _contains(heading, name)

        if not signals:
            continue
        generic = slug in GENERIC_CITY_SLUGS
        score = (0.63 if generic else 0.82) + min(0.08, 0.03 * (len(signals) - 1))
        if headline_hit:
            score += 0.08
        hits.append({
            "location_id": loc.get("id"),
            "slug": loc.get("slug"),
            "name": loc.get("name"),
            "confidence": round(min(0.98, score), 2),
            "decision_source": "explicit_place_mention",
            "headline_match": headline_hit,
            "signals": signals[:5],
        })

    hits.sort(key=lambda item: (-item["confidence"], str(item.get("name") or "")))
    return hits
