from __future__ import annotations

from datetime import datetime, timezone
from typing import Any

from .ai import ExternalAIClient
from .classifier import classify
from .entities import extract_entities
from .language import detect_language
from .location import detect_locations
from .summary import summarize


class IntelligencePipeline:
    VERSION = "intelligence-beta-v1"

    def __init__(self, ai_client: ExternalAIClient | None = None) -> None:
        self.ai = ai_client or ExternalAIClient()

    async def analyze(
        self,
        content: dict[str, Any],
        taxonomy: list[dict[str, Any]] | None = None,
        locations: list[dict[str, Any]] | None = None,
        rules: list[dict[str, Any]] | None = None,
        allow_external_ai: bool = False,
    ) -> dict[str, Any]:
        text = " ".join(str(content.get(k) or "") for k in ("title", "caption", "body"))
        language = detect_language(text)
        category = classify(text, taxonomy or [], rules or [])
        location_hits = detect_locations(text, locations or [])
        entities = extract_entities(text)
        summary = summarize(text)
        ai_result = None
        if allow_external_ai and self.ai.enabled:
            try:
                ai_result = await self.ai.analyze(content, taxonomy or [], locations or [])
            except Exception as exc:
                ai_result = {"error": str(exc), "used": False}
        return {
            "analyzer": self.VERSION,
            "created_at": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
            "language": language,
            "category": category,
            "locations": location_hits,
            "entities": entities,
            "summary": summary,
            "external_ai": ai_result,
        }
