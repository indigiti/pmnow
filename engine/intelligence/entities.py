from __future__ import annotations

import re

KNOWN = [
    "Pune Municipal Corporation",
    "PMC",
    "PCMC",
    "Pune Police",
    "Pune Traffic Police",
    "Pune Metro",
    "PMPML",
]


def extract_entities(text: str) -> list[dict]:
    hay = text or ""
    out: list[dict] = []
    seen: set[str] = set()
    for name in KNOWN:
        if name.lower() in hay.lower() and name.lower() not in seen:
            seen.add(name.lower())
            out.append({"name": name, "type": "organization", "confidence": 0.94})
    # Conservative title-case phrase extraction for beta; capped to avoid noisy output.
    for match in re.findall(r"\b(?:[A-Z][a-z]+(?:\s+|$)){2,4}", hay):
        name = " ".join(match.split()).strip()
        if len(name) < 5 or name.lower() in seen:
            continue
        seen.add(name.lower())
        out.append({"name": name, "type": "named_entity", "confidence": 0.58})
        if len(out) >= 12:
            break
    return out
