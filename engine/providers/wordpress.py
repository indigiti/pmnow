from __future__ import annotations

from typing import Any
from urllib.parse import urljoin


from .base import ContentProvider, ProviderError, ProviderResult
from engine.normalize.contract import normalized_content
from engine.normalize.utils import clean_html, media_item
from engine.security.url_guard import safe_get, validate_public_url


class WordPressProvider(ContentProvider):
    name = "wordpress"

    def validate(self, source: dict[str, Any]) -> list[str]:
        settings = source.get("settings") or {}
        if not (settings.get("site_url") or source.get("url")):
            return ["settings.site_url is required"]
        return []

    def _endpoint(self, source: dict[str, Any]) -> str:
        settings = source.get("settings") or {}
        base = str(settings.get("site_url") or source.get("url") or "").rstrip("/") + "/"
        endpoint = urljoin(base, "wp-json/wp/v2/posts")
        return validate_public_url(endpoint, bool((source.get("settings") or {}).get("allow_private_url", False)))

    async def test(self, source: dict[str, Any]) -> dict[str, Any]:
        errors = self.validate(source)
        if errors:
            return {"ok": False, "provider": self.name, "errors": errors}
        try:
            response = await safe_get(self._endpoint(source), params={"per_page": 1, "_fields": "id,date,modified,link,slug"}, timeout=8.0, allow_private=bool((source.get("settings") or {}).get("allow_private_url", False)))
            response.raise_for_status()
            rows = response.json()
            return {"ok": True, "provider": self.name, "items_examined": len(rows) if isinstance(rows, list) else 0}
        except Exception as exc:
            return {"ok": False, "provider": self.name, "errors": [str(exc)]}

    async def fetch_latest(self, source: dict[str, Any], limit: int = 10, cursor: str | None = None) -> ProviderResult:
        errors = self.validate(source)
        if errors:
            raise ProviderError("; ".join(errors))
        params: dict[str, Any] = {
            "per_page": max(1, min(50, limit)),
            "orderby": "modified",
            "order": "desc",
            "_embed": "wp:featuredmedia",
        }
        if cursor:
            params["modified_after"] = cursor
        response = await safe_get(self._endpoint(source), params=params, timeout=15.0, allow_private=bool((source.get("settings") or {}).get("allow_private_url", False)))
        response.raise_for_status()
        rows = response.json()
        if not isinstance(rows, list):
            raise ProviderError("Unexpected WordPress response")
        next_cursor = max((str(r.get("modified_gmt") or r.get("modified") or "") for r in rows), default=cursor or "") or None
        return ProviderResult(items=rows, cursor=next_cursor, meta={"count": len(rows)})

    def normalize(self, source: dict[str, Any], raw: dict[str, Any]) -> dict[str, Any]:
        embedded = raw.get("_embedded") or {}
        featured = embedded.get("wp:featuredmedia") or []
        media = []
        if featured and isinstance(featured, list) and isinstance(featured[0], dict):
            fm = featured[0]
            media.append(media_item(
                "image",
                fm.get("source_url"),
                width=fm.get("media_details", {}).get("width"),
                height=fm.get("media_details", {}).get("height"),
                caption=clean_html((fm.get("caption") or {}).get("rendered")),
            ))
        title = clean_html((raw.get("title") or {}).get("rendered"))
        body = clean_html((raw.get("content") or {}).get("rendered"))
        excerpt = clean_html((raw.get("excerpt") or {}).get("rendered"))
        return normalized_content(
            source=source,
            external_id=f"wp:{raw.get('id')}",
            content_type="article",
            title=title,
            body=body,
            caption=excerpt,
            permalink=raw.get("link"),
            published_at=raw.get("date_gmt") or raw.get("date"),
            updated_at=raw.get("modified_gmt") or raw.get("modified"),
            media=media,
            raw_meta={"slug": raw.get("slug"), "status": raw.get("status")},
        )

    def capabilities(self) -> dict[str, bool]:
        return {**super().capabilities(), "posts": True, "history": True}
