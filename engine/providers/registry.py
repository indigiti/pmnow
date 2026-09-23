from __future__ import annotations

from .base import ContentProvider, ProviderError
from .instagram import InstagramProvider
from .manual import ManualProvider
from .wordpress import WordPressProvider
from .x import XProvider
from .youtube import YouTubeProvider


class ProviderRegistry:
    def __init__(self) -> None:
        providers: list[ContentProvider] = [
            ManualProvider(),
            WordPressProvider(),
            InstagramProvider(),
            YouTubeProvider(),
            XProvider(),
        ]
        self._providers = {p.name: p for p in providers}

    def get(self, name: str) -> ContentProvider:
        key = (name or "").lower().strip()
        if key == "twitter":
            key = "x"
        if key not in self._providers:
            raise ProviderError(f"Unknown provider: {name}")
        return self._providers[key]

    def describe(self) -> list[dict]:
        return [
            {"name": name, "capabilities": provider.capabilities()}
            for name, provider in sorted(self._providers.items())
        ]
