from __future__ import annotations

from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Any


class ProviderError(RuntimeError):
    """Expected provider failure (configuration, auth, quota or remote response)."""


@dataclass(slots=True)
class ProviderResult:
    items: list[dict[str, Any]]
    cursor: str | None = None
    meta: dict[str, Any] | None = None


class ContentProvider(ABC):
    name = "base"

    def validate(self, source: dict[str, Any]) -> list[str]:
        return []

    @abstractmethod
    async def test(self, source: dict[str, Any]) -> dict[str, Any]:
        raise NotImplementedError

    @abstractmethod
    async def fetch_latest(
        self,
        source: dict[str, Any],
        limit: int = 10,
        cursor: str | None = None,
    ) -> ProviderResult:
        raise NotImplementedError

    @abstractmethod
    def normalize(self, source: dict[str, Any], raw: dict[str, Any]) -> dict[str, Any]:
        raise NotImplementedError

    def capabilities(self) -> dict[str, bool]:
        return {
            "posts": False,
            "video": False,
            "reels": False,
            "carousels": False,
            "live": False,
            "metrics": False,
            "history": False,
        }
