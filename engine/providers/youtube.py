from __future__ import annotations

from typing import Any

import httpx

from .base import ContentProvider, ProviderError, ProviderResult
from engine.normalize.contract import normalized_content
from engine.normalize.utils import media_item


class YouTubeProvider(ContentProvider):
    name = "youtube"

    def validate(self, source: dict[str, Any]) -> list[str]:
        settings = source.get("settings") or {}
        errors = []
        if not settings.get("channel_id"):
            errors.append("settings.channel_id is required")
        if not (source.get("credentials") or {}).get("api_key"):
            errors.append("credentials.api_key is required")
        return errors

    async def _uploads_playlist(self, source: dict[str, Any], client: httpx.AsyncClient) -> str:
        settings = source.get("settings") or {}
        base = str(settings.get("api_base") or "https://www.googleapis.com").rstrip("/")
        key = source["credentials"]["api_key"]
        response = await client.get(
            f"{base}/youtube/v3/channels",
            params={"part": "contentDetails", "id": settings["channel_id"], "key": key},
        )
        response.raise_for_status()
        items = response.json().get("items") or []
        if not items:
            raise ProviderError("YouTube channel not found or inaccessible")
        return items[0]["contentDetails"]["relatedPlaylists"]["uploads"]

    async def test(self, source: dict[str, Any]) -> dict[str, Any]:
        errors = self.validate(source)
        if errors:
            return {"ok": False, "provider": self.name, "errors": errors}
        try:
            async with httpx.AsyncClient(timeout=12.0) as client:
                playlist = await self._uploads_playlist(source, client)
            return {"ok": True, "provider": self.name, "uploads_playlist": playlist}
        except Exception as exc:
            return {"ok": False, "provider": self.name, "errors": [str(exc)]}

    async def fetch_latest(self, source: dict[str, Any], limit: int = 10, cursor: str | None = None) -> ProviderResult:
        errors = self.validate(source)
        if errors:
            raise ProviderError("; ".join(errors))
        settings = source.get("settings") or {}
        base = str(settings.get("api_base") or "https://www.googleapis.com").rstrip("/")
        key = source["credentials"]["api_key"]
        async with httpx.AsyncClient(timeout=15.0) as client:
            playlist = await self._uploads_playlist(source, client)
            params: dict[str, Any] = {
                "part": "snippet,contentDetails",
                "playlistId": playlist,
                "maxResults": max(1, min(50, limit)),
                "key": key,
            }
            if cursor:
                params["pageToken"] = cursor
            response = await client.get(f"{base}/youtube/v3/playlistItems", params=params)
            response.raise_for_status()
            payload = response.json()
        return ProviderResult(payload.get("items") or [], payload.get("nextPageToken"), {"count": len(payload.get("items") or [])})

    def normalize(self, source: dict[str, Any], raw: dict[str, Any]) -> dict[str, Any]:
        snippet = raw.get("snippet") or {}
        details = raw.get("contentDetails") or {}
        video_id = details.get("videoId") or (snippet.get("resourceId") or {}).get("videoId") or raw.get("id")
        thumbs = snippet.get("thumbnails") or {}
        thumb = (thumbs.get("maxres") or thumbs.get("standard") or thumbs.get("high") or thumbs.get("medium") or thumbs.get("default") or {}).get("url")
        media = [media_item("video", f"https://www.youtube.com/watch?v={video_id}" if video_id else None, thumbnail=thumb)]
        return normalized_content(
            source=source,
            external_id=str(video_id or ""),
            content_type="video",
            title=snippet.get("title"),
            body=snippet.get("description"),
            permalink=f"https://www.youtube.com/watch?v={video_id}" if video_id else None,
            published_at=snippet.get("publishedAt"),
            media=media,
            raw_meta={"playlist_item_id": raw.get("id")},
        )

    def capabilities(self) -> dict[str, bool]:
        return {**super().capabilities(), "video": True, "live": True, "history": True, "metrics": True}
