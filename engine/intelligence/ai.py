from __future__ import annotations

import os
from typing import Any

import httpx


class ExternalAIClient:
    """Optional private AI adapter. Disabled unless AI_HTTP_ENDPOINT is configured."""

    def __init__(self, endpoint: str | None = None, token: str | None = None) -> None:
        self.endpoint = endpoint or os.getenv("AI_HTTP_ENDPOINT")
        self.token = token or os.getenv("AI_HTTP_TOKEN")

    @property
    def enabled(self) -> bool:
        return bool(self.endpoint)

    async def analyze(self, content: dict[str, Any], taxonomy: list[dict[str, Any]], locations: list[dict[str, Any]]) -> dict[str, Any] | None:
        if not self.endpoint:
            return None
        headers = {"Content-Type": "application/json"}
        if self.token:
            headers["Authorization"] = f"Bearer {self.token}"
        payload = {"content": content, "taxonomy": taxonomy, "locations": locations}
        async with httpx.AsyncClient(timeout=20.0) as client:
            response = await client.post(self.endpoint, json=payload, headers=headers)
            response.raise_for_status()
            data = response.json()
        return data if isinstance(data, dict) else None
