from __future__ import annotations

import re
from difflib import SequenceMatcher
from typing import Any


def _tokens(text: str) -> set[str]:
    return {w for w in re.findall(r"[\w\u0900-\u097F]+", (text or "").lower()) if len(w) > 2}


def _content_text(content: dict[str, Any]) -> str:
    return " ".join(str(content.get(k) or "") for k in ("title", "headline", "caption", "body"))


def similarity(candidate: dict[str, Any], existing: dict[str, Any]) -> dict[str, Any]:
    if candidate.get("source_id") == existing.get("source_id") and candidate.get("external_id") and candidate.get("external_id") == existing.get("external_id"):
        return {"score": 1.0, "kind": "exact", "signals": ["source_external_id"]}
    if candidate.get("content_hash") and candidate.get("content_hash") == existing.get("content_hash"):
        return {"score": 1.0, "kind": "exact", "signals": ["content_hash"]}
    if candidate.get("permalink") and candidate.get("permalink") == existing.get("permalink"):
        return {"score": 0.995, "kind": "exact", "signals": ["permalink"]}

    a = _content_text(candidate)
    b = _content_text(existing)
    seq = SequenceMatcher(None, a.lower(), b.lower()).ratio() if a and b else 0.0
    ta, tb = _tokens(a), _tokens(b)
    jac = len(ta & tb) / max(1, len(ta | tb))
    score = 0.58 * seq + 0.42 * jac
    kind = "near" if score >= 0.72 else "related" if score >= 0.48 else "none"
    return {"score": round(score, 4), "kind": kind, "signals": [f"sequence:{seq:.3f}", f"jaccard:{jac:.3f}"]}


def find_duplicates(candidate: dict[str, Any], candidates: list[dict[str, Any]], limit: int = 5) -> list[dict[str, Any]]:
    hits = []
    for existing in candidates:
        if existing.get("id") == candidate.get("id"):
            continue
        result = similarity(candidate, existing)
        if result["score"] >= 0.48:
            hits.append({"content_id": existing.get("id"), **result})
    hits.sort(key=lambda x: -x["score"])
    return hits[:limit]
