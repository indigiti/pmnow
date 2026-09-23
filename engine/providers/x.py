from __future__ import annotations

from typing import Any

from .base import ContentProvider, ProviderError, ProviderResult
from engine.normalize.contract import normalized_content
from engine.normalize.utils import media_item
from engine.security.url_guard import safe_get


class XProvider(ContentProvider):
    name = "x"

    def validate(self, source: dict[str, Any]) -> list[str]:
        settings = source.get("settings") or {}
        mode = settings.get("connection_mode") or source.get("connection_mode") or "limited"
        if mode != "api":
            return []
        errors = []
        if not settings.get("user_id"):
            errors.append("settings.user_id is required for API mode")
        if not (source.get("credentials") or {}).get("bearer_token"):
            errors.append("credentials.bearer_token is required for API mode")
        return errors

    async def test(self, source: dict[str, Any]) -> dict[str, Any]:
        settings = source.get("settings") or {}
        mode = settings.get("connection_mode") or source.get("connection_mode") or "limited"
        if mode != "api":
            return {"ok": True, "provider": self.name, "mode": "limited", "limited": True}
        errors = self.validate(source)
        if errors:
            return {"ok": False, "provider": self.name, "errors": errors}
        try:
            result = await self.fetch_latest(source, limit=5)
            return {"ok": True, "provider": self.name, "items_examined": len(result.items)}
        except Exception as exc:
            return {"ok": False, "provider": self.name, "errors": [str(exc)]}

    async def fetch_latest(self, source: dict[str, Any], limit: int = 10, cursor: str | None = None) -> ProviderResult:
        settings = source.get("settings") or {}
        mode = settings.get("connection_mode") or source.get("connection_mode") or "limited"
        if mode != "api":
            return ProviderResult([], None, {"mode": "limited", "limited": True})
        errors = self.validate(source)
        if errors:
            raise ProviderError("; ".join(errors))
        base = str(settings.get("api_base") or "https://api.x.com").rstrip("/")
        url = f"{base}/2/users/{settings['user_id']}/tweets"
        headers = {"Authorization": f"Bearer {source['credentials']['bearer_token']}"}
        params: dict[str, Any] = {
            "max_results": max(5, min(100, limit)),
            "tweet.fields": "created_at,lang,public_metrics,attachments",
            "expansions": "attachments.media_keys",
            "media.fields": "type,url,preview_image_url,width,height,duration_ms",
        }
        if cursor:
            params["pagination_token"] = cursor
        response = await safe_get(
            url,
            headers=headers,
            params=params,
            timeout=15.0,
            allowed_hosts={"api.x.com", "api.twitter.com"},
        )
        response.raise_for_status()
        payload = response.json()
        media_by_key = {m.get("media_key"): m for m in ((payload.get("includes") or {}).get("media") or [])}
        rows = []
        for tweet in payload.get("data") or []:
            tweet = dict(tweet)
            tweet["_media"] = [media_by_key[k] for k in (tweet.get("attachments") or {}).get("media_keys", []) if k in media_by_key]
            rows.append(tweet)
        return ProviderResult(rows, (payload.get("meta") or {}).get("next_token"), {"count": len(rows)})

    def normalize(self, source: dict[str, Any], raw: dict[str, Any]) -> dict[str, Any]:
        media = []
        for item in raw.get("_media") or []:
            kind = "video" if item.get("type") in {"video", "animated_gif"} else "image"
            media.append(media_item(
                kind,
                item.get("url"),
                thumbnail=item.get("preview_image_url"),
                width=item.get("width"),
                height=item.get("height"),
                duration=(item.get("duration_ms") or 0) / 1000 if item.get("duration_ms") else None,
            ))
        text = raw.get("text") or ""
        handle = str(source.get("handle") or "").lstrip("@")
        tweet_id = str(raw.get("id") or "")
        permalink = f"https://x.com/{handle}/status/{tweet_id}" if handle and tweet_id else None
        return normalized_content(
            source=source,
            external_id=tweet_id,
            content_type="post",
            body=text,
            caption=text,
            permalink=permalink,
            published_at=raw.get("created_at"),
            language=raw.get("lang"),
            media=media,
            metrics=raw.get("public_metrics") or {},
        )

    def capabilities(self) -> dict[str, bool]:
        return {**super().capabilities(), "posts": True, "video": True, "metrics": True}
