from __future__ import annotations
from typing import Any
from urllib.parse import urlparse

ALLOWED_TYPES = {"image", "video", "thumbnail", "audio", "embed"}
MAX_DIMENSION = 20000
MAX_DURATION_SECONDS = 6 * 60 * 60


def validate_media(item: dict[str, Any]) -> list[str]:
    errors: list[str] = []
    media_type = str(item.get("type") or "image").lower()
    if media_type not in ALLOWED_TYPES:
        errors.append("unsupported media type")
    url = item.get("source_url") or item.get("cached_url") or item.get("thumbnail")
    if url:
        p = urlparse(str(url))
        if p.scheme not in {"http", "https", ""}:
            errors.append("unsafe media URL scheme")
        if p.scheme == "" and not str(url).startswith("/media/"):
            errors.append("relative media URL must use /media/")
    for key in ("width", "height"):
        value = item.get(key)
        if value is not None and (not isinstance(value, (int, float)) or value <= 0 or value > MAX_DIMENSION):
            errors.append(f"invalid {key}")
    duration = item.get("duration")
    if duration is not None and (not isinstance(duration, (int, float)) or duration < 0 or duration > MAX_DURATION_SECONDS):
        errors.append("invalid duration")
    return errors
