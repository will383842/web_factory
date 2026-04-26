# ADR 0006 — Use Cloudflare for CDN, WAF, and edge cache

## Status

Accepted — 2026-04-26

## Context

WebFactory will publish hundreds of thousands of localized SEO/AEO pages, plus a SaaS console.
Origin-only serving from a single Hetzner VPS would mean:

- Geographic latency for international visitors (ms → seconds for Asia/Americas).
- Each crawl spike hits the origin (Google bots, AI bots, social previews).
- No cheap WAF.

We have already validated the pattern on SOS Expat (Edge Cache Worker, 2026-04-12) where a
Cloudflare Worker brought Google Search latency from 7-14s down to < 50ms.

## Decision

Cloudflare sits in front of the origin for all WebFactory tenant domains:

- **CDN**: cache static assets and HTML for cacheable routes.
- **WAF + DDoS**: managed rules + custom rules for /admin and /api.
- **Worker**: edge cache for SSR/blog/sitemaps with stale-while-revalidate strategy
  (mirrored from SOS Expat Edge Cache Worker).
- **DNS + TLS**: managed zones, automatic certificates.
- **IndexNow + GSC API**: integrate with edge for fresh-content signaling.

The origin (Hetzner VPS) only serves uncached or revalidated requests.

## Alternatives considered

- **Origin-only** — unacceptable at WebFactory's planned content volume.
- **Bunny / Fastly / KeyCDN** — competitive features but Cloudflare's WAF + Worker compute combo
  is the cheapest path for our use case.
- **AWS CloudFront** — over-priced for our volume and adds AWS billing complexity.

## Consequences

- Positives:
  - Sub-100ms first-byte globally.
  - Origin protected from crawl/DDoS spikes.
  - One vendor for DNS, TLS, CDN, WAF, edge compute.
- Negatives:
  - One vendor dependency for production traffic — outage on Cloudflare is felt.
  - Debugging cache behavior requires familiarity with Cloudflare-specific headers.
- Neutral:
  - Cache headers must be set rigorously by the origin (Laravel) — pattern already validated
    on SOS Expat.

## References

- Memory `project_edge_cache_2026_04_12.md`
- Spec 27 — Architecture & scalability
