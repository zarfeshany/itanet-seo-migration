# Migration report — GSC URLs → tastebox.ir

Date: 2026-07-22

## Result
- Migrated content pages from itanet.ir Performance export
- Exact path URLs verified HTTP 200 on tastebox.ir
- MU-plugin `wp-content/mu-plugins/itanet-exact-path-resolver.php` resolves long Persian slugs

## Created / updated (examples)
- /prices/ /ftth/ /charging/ /contact/ /speed-test/ /agentsservice/
- /2ghasemi/ /shahin/ /lows/ /kh/ /shops/
- /blog/* (14 posts as child pages of /blog/)
- /10806/article/ /moba/article/

## Intentionally skipped
- `/` homepage (tastebox front page not overwritten)
- `/app/profiles` (app surface)
- URL fragments `#blog*`
- `http://www.itanet.ir/` (host variant)

## Notes
- Interactive ASP.NET widgets (pricing selectors, FTTH project maps) are partially static HTML; deep app logic still on itanet/CRM.
- See `migrate-result.json` and `fix-slugs-result.json` for IDs.