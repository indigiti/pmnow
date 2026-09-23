from engine.security.url_guard import validate_public_url, assert_allowed_host, UnsafeUrl
from engine.media.validate import validate_media

def expect_block(url: str):
    try:
        validate_public_url(url)
    except UnsafeUrl:
        return
    raise AssertionError(f"expected URL to be blocked: {url}")

expect_block("http://127.0.0.1/admin")
expect_block("http://169.254.169.254/latest/meta-data")
assert validate_media({"type":"image","source_url":"https://example.com/a.jpg","width":1200,"height":800}) == []
assert validate_media({"type":"image","source_url":"file:///etc/passwd"})
print("M7 engine smoke tests passed.")

assert assert_allowed_host("https://api.x.com/2/test", {"api.x.com"}) == "https://api.x.com/2/test"
try:
    assert_allowed_host("https://example.com/steal", {"api.x.com"})
except UnsafeUrl:
    pass
else:
    raise AssertionError("expected non-official API host to be blocked")
