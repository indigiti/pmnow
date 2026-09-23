from __future__ import annotations

from typing import Any

from .base import ContentProvider, ProviderResult
from engine.normalize.contract import normalized_content
from engine.normalize.utils import media_item


class ManualProvider(ContentProvider):
    name = "manual"

    async def test(self, source: dict[str, Any]) -> dict[str, Any]:
        return {"ok": True, "provider": self.name, "message": "Manual provider ready"}

    async def fetch_latest(self, source: dict[str, Any], limit: int = 10, cursor: str | None = None) -> ProviderResult:
        return ProviderResult(items=[], cursor=None, meta={"mode": "manual"})

    def normalize(self, source: dict[str, Any], raw: dict[str, Any]) -> dict[str, Any]:
        media = []
        for item in raw.get("media", []) or []:
            if isinstance(item, str):
                media.append(media_item("image", item))
            elif isinstance(item, dict):
                media.append(media_item(
                    str(item.get("type") or "image"),
                    item.get("source_url") or item.get("url"),
                    thumbnail=item.get("thumbnail"),
                    width=item.get("width"),
                    height=item.get("height"),
                    duration=item.get("duration"),
                    caption=item.get("caption"),
                ))
        external_id = str(raw.get("external_id") or raw.get("id") or raw.get("permalink") or raw.get("title") or "manual")
        return normalized_content(
            source=source,
            external_id=external_id,
            content_type=str(raw.get("content_type") or raw.get("type") or "article"),
            title=raw.get("title"),
            body=raw.get("body") or raw.get("text"),
            caption=raw.get("caption"),
            permalink=raw.get("permalink") or raw.get("url"),
            published_at=raw.get("published_at"),
            language=raw.get("language"),
            media=media,
            metrics=raw.get("metrics") or {},
            raw_meta={"manual": True},
        )

    def capabilities(self) -> dict[str, bool]:
        return {**super().capabilities(), "posts": True, "video": True, "reels": True, "carousels": True, "history": True}
