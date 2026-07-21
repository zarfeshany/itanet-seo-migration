# GSC Coverage Analysis — itanet.ir

Source: `itanet.ir-Coverage-2026-07-21.xlsx`  
Scope: Sitemap = All known pages

## Latest snapshot (from Chart)

- Date: 2026-07-09
- Indexed: 32
- Not indexed: 96
- Impressions that day: 27689

Trend: Indexed count rose from ~19 (late Apr) to ~30+ (May/Jun). Not indexed stayed high (~118–137).

## Critical issues (must fix before / during WP migration)

| Reason | Pages | Migration implication |
|---|---:|---|
| Server error (5xx) | 47 | Highest risk — find and fix or exclude before cutover |
| Crawled - currently not indexed | 27 | Thin/duplicate/quality signal — review content |
| Excluded by `noindex` tag | 9 | Confirm intentional (app/login?) vs accidental |
| Page with redirect | 5 | Preserve same 301 chain in WordPress |
| Duplicate canonical (Google chose different) | 3 | Slash variants / www / http — canonicalize |
| Blocked due to other 4xx | 3 | Fix or redirect |
| Not found (404) | 2 | 301 to replacement or restore |

## Important limitation of this export

This file is an **issue summary only**. It does **not** include the URL lists for Indexed or each issue reason.

We still need per-reason URL exports from GSC Page indexing:

1. Open each Critical reason → Export (or copy sample URLs)
2. Especially export **Indexed** pages list if available
3. Export **Server error (5xx)** URL list (47 pages) — blocking for migration QA

## Merge with Performance export

Performance already gave ~28 paths with clicks (priority preserve).  
Coverage says many more URLs are known but not indexed (~120+). Those should be audited: migrate only if valuable; otherwise noindex/404 cleanly so they do not create soft-404s on WordPress.