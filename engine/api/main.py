from __future__ import annotations

from typing import Any

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from engine.intelligence.duplicate import find_duplicates
from engine.intelligence.pipeline import IntelligencePipeline
from engine.intelligence.related import find_related
from engine.providers.base import ProviderError
from engine.providers.registry import ProviderRegistry

app = FastAPI(title="Pune Mirror Content Engine", version="0.7.0")
registry = ProviderRegistry()
pipeline = IntelligencePipeline()


class SourceEnvelope(BaseModel):
    source: dict[str, Any]


class SyncEnvelope(BaseModel):
    source: dict[str, Any]
    limit: int = Field(default=10, ge=1, le=50)
    cursor: str | None = None


class NormalizeEnvelope(BaseModel):
    source: dict[str, Any]
    raw: dict[str, Any]


class AnalyzeEnvelope(BaseModel):
    content: dict[str, Any]
    taxonomy: list[dict[str, Any]] = []
    locations: list[dict[str, Any]] = []
    rules: list[dict[str, Any]] = []
    allow_external_ai: bool = False


class CompareEnvelope(BaseModel):
    content: dict[str, Any]
    candidates: list[dict[str, Any]] = []
    limit: int = Field(default=5, ge=1, le=50)


@app.get("/health")
async def health() -> dict[str, Any]:
    return {"status": "healthy", "service": "content-engine", "version": app.version, "providers": len(registry.describe())}


@app.get("/v1/providers")
async def providers() -> dict[str, Any]:
    return {"data": registry.describe()}


@app.post("/v1/source/test")
async def source_test(request: SourceEnvelope) -> dict[str, Any]:
    try:
        provider = registry.get(str(request.source.get("provider") or ""))
        return {"data": await provider.test(request.source)}
    except ProviderError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc


@app.post("/v1/source/sync")
async def source_sync(request: SyncEnvelope) -> dict[str, Any]:
    try:
        provider = registry.get(str(request.source.get("provider") or ""))
        result = await provider.fetch_latest(request.source, request.limit, request.cursor)
        normalized = [provider.normalize(request.source, row) for row in result.items]
        return {"data": normalized, "meta": {"cursor": result.cursor, **(result.meta or {})}}
    except ProviderError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    except Exception as exc:
        raise HTTPException(status_code=502, detail=f"Provider sync failed: {exc}") from exc


@app.post("/v1/content/normalize")
async def content_normalize(request: NormalizeEnvelope) -> dict[str, Any]:
    try:
        provider = registry.get(str(request.source.get("provider") or ""))
        return {"data": provider.normalize(request.source, request.raw)}
    except ProviderError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc


@app.post("/v1/content/analyze")
async def content_analyze(request: AnalyzeEnvelope) -> dict[str, Any]:
    result = await pipeline.analyze(request.content, request.taxonomy, request.locations, request.rules, request.allow_external_ai)
    return {"data": result}


@app.post("/v1/content/duplicate")
async def content_duplicate(request: CompareEnvelope) -> dict[str, Any]:
    return {"data": find_duplicates(request.content, request.candidates, request.limit)}


@app.post("/v1/content/related")
async def content_related(request: CompareEnvelope) -> dict[str, Any]:
    return {"data": find_related(request.content, request.candidates, request.limit)}
