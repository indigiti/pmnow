from __future__ import annotations

import hashlib
import html
import re
from datetime import datetime, timezone
from typing import Any

_TAG_RE = re.compile(r"<[^>]+>")
_WS_RE = re.compile(r"\s+")


def clean_html(value: str | None) -> str:
    if not value:
        return ""
    text = _TAG_RE.sub(" ", value)
    text = html.unescape(text)
    return _WS_RE.sub(" ", text).strip()


def clean_text(value: Any) -> str:
    if value is None:
        return ""
    return _WS_RE.sub(" ", str(value)).strip()


def iso_now() -> str:
    return datetime.now(timezone.utc).isoformat().replace("+00:00", "Z")


def stable_hash(*parts: str) -> str:
    payload = "\n".join(p.strip().lower() for p in parts if p)
    return hashlib.sha256(payload.encode("utf-8")).hexdigest()


def media_item(
    media_type: str,
    source_url: str | None,
    *,
    thumbnail: str | None = None,
    width: int | None = None,
    height: int | None = None,
    duration: float | None = None,
    caption: str | None = None,
) -> dict[str, Any]:
    return {
        "type": media_type,
        "source_url": source_url,
        "cached_url": None,
        "thumbnail": thumbnail,
        "width": width,
        "height": height,
        "duration": duration,
        "caption": caption or "",
    }
