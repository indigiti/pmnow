from __future__ import annotations

import re
from typing import Any


def detect_locations(text: str, locations: list[dict[str, Any]]) -> list[dict[str, Any]]:
    hay = (text or "").lower()
    hits: list[dict[str, Any]] = []
    for loc in locations or []:
        names = [loc.get("name"), loc.get("slug")]
        names.extend(loc.get("aliases") or [])
        matched = []
        for name in names:
            if not name:
                continue
            needle = str(name).replace("-", " ").lower().strip()
            if needle and re.search(r"(?<!\w)" + re.escape(needle) + r"(?!\w)", hay):
                matched.append(str(name))
        if matched:
            hits.append({
                "location_id": loc.get("id"),
                "slug": loc.get("slug"),
                "name": loc.get("name"),
                "confidence": min(0.99, 0.82 + 0.04 * len(matched)),
                "signals": matched,
            })
    hits.sort(key=lambda x: (-x["confidence"], str(x.get("name"))))
    return hits
