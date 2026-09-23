from __future__ import annotations

from typing import Any

from .utils import clean_text, iso_now, stable_hash


def normalized_content(
    *,
    source: dict[str, Any],
    external_id: str,
    content_type: str,
    title: str | None = None,
    body: str | None = None,
    caption: str | None = None,
    permalink: str | None = None,
    published_at: str | None = None,
    updated_at: str | None = None,
    language: str | None = None,
    media: list[dict[str, Any]] | None = None,
    metrics: dict[str, Any] | None = None,
    raw_meta: dict[str, Any] | None = None,
) -> dict[str, Any]:
    title = clean_text(title)
    body = clean_text(body)
    caption = clean_text(caption)
    provider = clean_text(source.get("provider")) or "manual"
    return {
        "source_id": source.get("id"),
        "provider": provider,
        "source_handle": source.get("handle") or source.get("name") or "",
        "external_id": clean_text(external_id),
        "content_type": content_type,
        "title": title or None,
        "body": body,
        "caption": caption,
        "permalink": permalink,
        "published_at": published_at or iso_now(),
        "discovered_at": iso_now(),
        "external_updated_at": updated_at,
        "language": language or "und",
        "media": media or [],
        "metrics": metrics or {},
        "raw_meta": raw_meta or {},
        "content_hash": stable_hash(title, body, caption, permalink or ""),
        "workflow_status": "ingested",
    }
