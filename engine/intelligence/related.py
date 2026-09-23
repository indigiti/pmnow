from __future__ import annotations

from typing import Any

from .duplicate import similarity


def find_related(candidate: dict[str, Any], candidates: list[dict[str, Any]], limit: int = 8) -> list[dict[str, Any]]:
    out = []
    category = set(candidate.get("final_category_slugs") or candidate.get("category_slugs") or [])
    locations = set(candidate.get("final_location_slugs") or candidate.get("location_slugs") or [])
    for row in candidates:
        if row.get("id") == candidate.get("id"):
            continue
        sim = similarity(candidate, row)["score"]
        row_cat = set(row.get("final_category_slugs") or row.get("category_slugs") or [])
        row_loc = set(row.get("final_location_slugs") or row.get("location_slugs") or [])
        category_boost = 0.12 if category & row_cat else 0.0
        location_boost = 0.18 if locations & row_loc else 0.0
        score = min(1.0, sim + category_boost + location_boost)
        if score >= 0.45:
            out.append({"content_id": row.get("id"), "score": round(score, 4), "signals": {"text": sim, "category": bool(category_boost), "location": bool(location_boost)}})
    out.sort(key=lambda x: -x["score"])
    return out[:limit]
