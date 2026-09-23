from __future__ import annotations

import ipaddress
import socket
from urllib.parse import urljoin, urlparse

import httpx


class UnsafeUrl(ValueError):
    pass


def validate_public_url(url: str, allow_private: bool = False) -> str:
    parsed = urlparse(url)
    if parsed.scheme not in {"http", "https"} or not parsed.hostname:
        raise UnsafeUrl("Only absolute http/https URLs are allowed")
    if parsed.username or parsed.password:
        raise UnsafeUrl("Embedded URL credentials are not allowed")
    host = parsed.hostname.rstrip(".").lower()
    if not allow_private and host in {"localhost", "localhost.localdomain"}:
        raise UnsafeUrl("Localhost is blocked")
    try:
        infos = socket.getaddrinfo(host, parsed.port or (443 if parsed.scheme == "https" else 80), type=socket.SOCK_STREAM)
    except socket.gaierror as exc:
        raise UnsafeUrl(f"Host could not be resolved: {host}") from exc
    for info in infos:
        ip = ipaddress.ip_address(info[4][0])
        if not allow_private and (not ip.is_global or ip.is_loopback or ip.is_link_local or ip.is_private or ip.is_reserved):
            raise UnsafeUrl("Private, loopback, link-local, or reserved destination is blocked")
    return url


async def safe_get(url: str, *, params: dict | None = None, timeout: float = 15.0, allow_private: bool = False, max_redirects: int = 3) -> httpx.Response:
    current = validate_public_url(url, allow_private)
    async with httpx.AsyncClient(timeout=timeout, follow_redirects=False) as client:
        for _ in range(max_redirects + 1):
            response = await client.get(current, params=params if current == url else None)
            if response.status_code not in {301, 302, 303, 307, 308}:
                return response
            location = response.headers.get("location")
            if not location:
                return response
            current = validate_public_url(urljoin(current, location), allow_private)
        raise UnsafeUrl("Too many redirects")
