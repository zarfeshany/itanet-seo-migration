# Itanet — SEO Migration Data (CMS → WordPress)

مخزن مشترک برای داده‌ها و آنالیزهای مهاجرت `itanet.ir` از CMS شخصی (ASP.NET) به وردپرس، با حفظ URL و سئو.

## محتویات

| مسیر | توضیح |
|------|--------|
| `itanet.ir-Performance-on-Search-2026-07-21.xlsx` | خروجی GSC Performance (۳ ماه) |
| `itanet.ir-Coverage-2026-07-21.xlsx` | خروجی GSC Coverage / Page indexing (خلاصه) |
| `gsc-analysis/` | CSVها و گزارش‌های استخراج‌شده |

### گزارش‌های کلیدی در `gsc-analysis/`

- `MIGRATION-URL-PRIORITY.md` — اولویت URLها از Performance
- `COVERAGE-ANALYSIS.md` — وضعیت ایندکس و Critical issues
- `pages.csv` — صفحات دارای کلیک/نمایش
- `queries-top1000.csv` — کوئری‌های برتر
- `url-inventory-priority.csv` — فهرست اولویت‌دار مسیرها
- `coverage-*.csv` — جزئیات Coverage

## خلاصه سریع

- دامنه اصلی: `https://itanet.ir/` (`www` → apex)
- CMS فعلی: ASP.NET MVC + ArvanCloud
- صفحه خانه ~۲۸k کلیک در ۳ ماه (اولویت مطلق حفظ URL)
- لندینگ‌های حیاتی: `/prices/` `/ftth/` `/charging/` `/contact/` `/agentsservice/` `/speed-test/`
- بلاگ: `/blog/{slug}/` (اسلش انتها مهم است)
- Coverage اخیر: حدود ۳۲ ایندکس‌شده در برابر ~۹۶ ایندکس‌نشده؛ ۴۷ خطای 5xx در گزارش Critical

## قدم بعدی

Export جزئی URL از هر دلیل Critical در GSC (به‌ویژه Server error 5xx) و افزودن به همین مخزن.
