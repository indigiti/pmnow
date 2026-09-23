from __future__ import annotations

from typing import Any

from .base import ContentProvider, ProviderError, ProviderResult
from engine.normalize.contract import normalized_content
from engine.normalize.utils import media_item
from engine.security.url_guard import safe_get


class InstagramProvider(ContentProvider):
    name = "instagram"

    def validate(self, source: dict[str, Any]) -> list[str]:
        settings = source.get("settings") or {}
        mode = settings.get("connection_mode") or source.get("connection_mode") or "official_api"
        if mode == "manual_only":
            return []
        errors = []
        if not settings.get("account_id"):
            errors.append("settings.account_id is required for official_api")
        if not settings.get("api_version") and not settings.get("api_base"):
            errors.append("settings.api_version or settings.api_base is required for official_api")
        if not (source.get("credentials") or {}).get("access_token"):
            errors.append("credentials.access_token is required for official_api")
        return errors

    async def test(self, source: dict[str, Any]) -> dict[str, Any]:
        settings = source.get("settings") or {}
        mode = settings.get("connection_mode") or source.get("connection_mode") or "official_api"
        if mode == "manual_only":
            return {"ok": True, "provider": self.name, "mode": "manual_only", "limited": True}
        errors = self.validate(source)
        if errors:
            return {"ok": False, "provider": self.name, "errors": errors}
        try:
            result = await self.fetch_latest(source, limit=1)
            return {"ok": True, "provider": self.name, "items_examined": len(result.items)}
        except Exception as exc:
            return {"ok": False, "provider": self.name, "errors": [str(exc)]}

    async def fetch_latest(self, source: dict[str, Any], limit: int = 10, cursor: str | None = None) -> ProviderResult:
        settings = source.get("settings") or {}
        mode = settings.get("connection_mode") or source.get("connection_mode") or "official_api"
        if mode == "manual_only":
            return ProviderResult([], None, {"mode": "manual_only", "limited": True})
        errors = self.validate(source)
        if errors:
            raise ProviderError("; ".join(errors))
        if settings.get("api_base"):
            base = str(settings["api_base"]).rstrip("/")
        else:
            base = "https://graph.facebook.com/" + str(settings["api_version"]).strip("/")
        account_id = settings["account_id"]
        token = source["credentials"]["access_token"]
        url = f"{base}/{account_id}/media"
        fields = "id,caption,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp,children{media_type,media_url,thumbnail_url,id}"
        params: dict[str, Any] = {"fields": fields, "limit": max(1, min(50, limit)), "access_token": token}
        if cursor:
            params["after"] = cursor
        response = await safe_get(
            url,
            params=params,
            timeout=15.0,
            allowed_hosts={"graph.facebook.com", "graph.instagram.com"},
        )
        response.raise_for_status()
        payload = response.json()
        rows = payload.get("data") or []
        next_cursor = (((payload.get("paging") or {}).get("cursors") or {}).get("after"))
        return ProviderResult(rows, next_cursor, {"count": len(rows)})

    def normalize(self, source: dict[str, Any], raw: dict[str, Any]) -> dict[str, Any]:
        media_type = str(raw.get("media_type") or "IMAGE").upper()
        product_type = str(raw.get("media_product_type") or "").upper()
        content_type = "reel" if product_type == "REELS" else {"CAROUSEL_ALBUM": "carousel"}.get(media_type, "post")
        media = []
        children = (raw.get("children") or {}).get("data") or []
        if children:
            for child in children:
                ctype = "video" if str(child.get("media_type") or "").upper() == "VIDEO" else "image"
                media.append(media_item(ctype, child.get("media_url"), thumbnail=child.get("thumbnail_url")))
        else:
            mtype = "video" if media_type == "VIDEO" else "image"
            media.append(media_item(mtype, raw.get("media_url"), thumbnail=raw.get("thumbnail_url")))
        caption = raw.get("caption") or ""
        return normalized_content(
            source=source,
            external_id=str(raw.get("id") or ""),
            content_type=content_type,
            caption=caption,
            body=caption,
            permalink=raw.get("permalink"),
            published_at=raw.get("timestamp"),
            media=media,
            raw_meta={"instagram_media_type": media_type, "instagram_product_type": product_type},
        )

    def capabilities(self) -> dict[str, bool]:
        return {**super().capabilities(), "posts": True, "video": True, "reels": True, "carousels": True, "metrics": True}
