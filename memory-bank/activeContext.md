# Active Context

## Current focus
Client email export must match the visible client list, including EasyAdmin modal filters.

## What changed recently
- Email export is now a hidden EasyAdmin CRUD action (`emailExport`) on `ClientCrudController`, not a standalone `/admin/client-email-export` controller.
- Export builds the query with the same `createIndexQueryBuilder()` as the client index (EasyAdmin `filters` + custom `f_*` bar + search).
- Pagination/JSON assembly lives in `ClientEmailExporter` and uses Doctrine `Paginator` so page contents stay aligned with the list.
- The export toolbar copies the current URL query with duplicate keys preserved (`filters[industries][value][]`) and does not overwrite `crudAction`.

## Export mode across pages
The email export bar stays open while moving between client-list pages. The open flag lives in `sessionStorage` (`clientEmailExportBarOpen`). It closes only from the Zamknij button, or when a link leaves the client list path (tasks, client detail, other admin pages).

## Immediate next steps
- Manually verify export with a modal filter (branża / konto biznesowe / nazwa firmy) after logging in.
- Remaining Bugbot findings not yet addressed: over-restrictive search AND, UniqueEntity vs soft-deleted company names, multi-client task activity logs.

## Open decisions
- What the primary product scope is (which CRM modules matter most).
- Deployment target and operational constraints (hosting, DB, mail transport).
