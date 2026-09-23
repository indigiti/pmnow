from __future__ import annotations

import asyncio

from engine.intelligence.duplicate import find_duplicates
from engine.intelligence.pipeline import IntelligencePipeline
from engine.providers.manual import ManualProvider
from engine.providers.registry import ProviderRegistry


async def main() -> None:
    registry = ProviderRegistry()
    names = {p["name"] for p in registry.describe()}
    assert {"manual", "wordpress", "instagram", "youtube", "x"} <= names

    source = {"id": "0199b3f8-b849-7d73-a90d-178ea09bb364", "provider": "manual", "name": "Desk", "handle": "@desk"}
    raw = {
        "external_id": "demo-1",
        "title": "Pune Metro traffic diversion after heavy rain in Shivajinagar",
        "body": "Pune Traffic Police announced a road diversion near Shivajinagar after heavy rain and waterlogging.",
        "published_at": "2026-09-23T10:00:00+05:30",
    }
    normalized = ManualProvider().normalize(source, raw)
    assert normalized["provider"] == "manual"
    assert normalized["content_hash"]

    taxonomy = [{"slug": "traffic"}, {"slug": "metro"}, {"slug": "weather"}, {"slug": "civic"}]
    locations = [{"id": "0199b3f8-b849-7d73-a90d-178ea09bb365", "name": "Shivajinagar", "slug": "shivajinagar", "aliases": ["Shivaji Nagar"]}]
    analysis = await IntelligencePipeline().analyze(normalized, taxonomy, locations)
    assert analysis["category"]["category_slug"] in {"traffic", "metro", "weather"}
    assert analysis["locations"] and analysis["locations"][0]["slug"] == "shivajinagar"

    dup = dict(normalized)
    dup["id"] = "0199b3f8-b849-7d73-a90d-178ea09bb366"
    hits = find_duplicates(normalized, [dup])
    assert hits and hits[0]["kind"] == "exact"
    print("Python M4/M5 smoke: PASS")


if __name__ == "__main__":
    asyncio.run(main())
