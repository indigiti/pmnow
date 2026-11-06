# PMNow — Area Pulse + Editorial Reader Refresh

## Design direction

Based on the supplied six mobile news UI references: a light, editorial-first app with Work Sans for interface typography, a Georgia editorial serif for article reading, generous white space, rounded media, compact pill navigation, and blue states for interaction. Pune Mirror red remains reserved for breaking/news brand emphasis; the screenshots are references, not copied production art.

The primary UI stylesheet is `resources/css/m8/editorial-2026.css`; it extends the existing M8 system instead of creating a second CSS framework. Google-hosted Work Sans + Noto Sans Devanagari are optional webfonts, with stable local system fallbacks when unavailable. The Content-Security-Policy default allows only the official Google Fonts stylesheet and font host for typography.

## Automatic area connection

The existing Python intelligence pipeline normalizes and classifies each imported article; this milestone enhances its deterministic **location matching**, not location guessing:

- Explicit neighbourhood mentions in title/body; title matches are ranked higher
- Word-boundary protection (e.g. Baner must not match Banerjee)
- Marathi names for 12 common Pune/PCMC areas
- Generic Pune city does not outrank a specific area
- An explainable evidence list and confidence score for each match

Current content publishing already carries `location_slugs` into saved Story location IDs; this enhancement therefore uses existing indexing and taxonomy contracts. **Editorial assignment remains reviewable; confidence is not independent fact verification.**

## Area Pulse — /area-map

A responsive, illustrated Pune/PCMC region locator shows approximate centroids for 12 neighbourhoods. Pins and an adjacent accessible list show **published news reports from the past seven days** and which are categorised as traffic reporting. The product never calls these traffic-speed data or live congestion: it does not have an authenticated road-segment provider. A story's traffic category alone is insufficient proof of congestion.

Readers can open each `/area/{slug}` feed, or use My Pune to follow locations. My Pune also has an optional browser geolocation control: it runs only after a button click, refuses inaccurate or distant positions, suggests a nearby area from the fixed local centroids, and requires explicit **Save My Pune**. Raw location, accuracy and GPS coordinates are not sent to the server or stored.

### Live congestion later

A separate licensed provider integration is required for road-segment flow or incidents, with legal display/storage rights, an authenticated server-side worker, source timestamps, TTL, data-quality flags, backoff, quota controls, privacy handling and per-road visualisation. TomTom Traffic Flow or Google traffic-aware routing are candidates but **no live congestion is claimed or enabled in this milestone**. Zero traffic reports means no recent tagged reporting, not clear roads.

## Usability & implementation

- Reader Home: editorial heading and entry into Area Pulse
- Explore: area-map promo, rounded cards, genuine engagement metrics only
- Story and article reader: more readable serif hierarchy, generous spacing
- My Pune: area suggestion via consented coarse location, no automatic preferences write
- Navigation: blue active controls and rounded mobile bottom bar, preserved accessible labels
- Layout: desktop rail integration, responsive profiles and map, reduced-motion respect
- Separate PHP `AreaPulseService` aggregates recent published story/category/location IDs in one pass; no new DB or third-party geocoder
- Tests: Python aliases and false positives, PHP report counts and freshness, Playwright map navigation and location suggestion

## Known boundaries

The map is **schematic**, not a street-level base map, road-routing app, traffic heatmap, geocoding engine, or alternative to authoritative mobility services. Approximate neighbourhood centres are intended only for coarse local discovery.

The file-backed repository `all()` scan is appropriate for the early-stage content volume and only runs on the dedicated Area Pulse route; it needs an indexed aggregate for large datasets. The optional Google font service may be blocked by an organisation-specific CSP, in which case system fonts remain usable.

## QA and deployment

New checks are included in the existing PHP/Python/Playwright security workflow. DigiOps should deploy only a successful `main` certification artifact (`digiops-release`). No external traffic API keys or migrations are needed to enable Area Pulse.
