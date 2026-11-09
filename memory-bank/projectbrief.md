# Project Brief

## What this project is
`polaCRM` is a PHP/Symfony web application intended to support CRM-style workflows (customer/contact management and internal operations) with an admin-oriented UI.

## Primary goals
- Provide a secure, maintainable web application built on Symfony 7.4 and PHP 8.2+.
- Offer an efficient back-office/admin experience (EasyAdmin present).
- Persist core business data via Doctrine ORM + migrations.
- Support modern Symfony frontend tooling (AssetMapper/Importmap, Stimulus, Turbo).

## Non-goals (for now)
- Defining detailed business requirements beyond what’s expressed in the codebase.
- Committing to specific DB vendor, hosting, or deployment strategy before configuration review.

## Key constraints
- **Runtime**: PHP >= 8.2
- **Framework**: Symfony 7.4.*
- **Persistence**: Doctrine ORM 3.6 + Doctrine Migrations
- **Admin UI**: EasyAdmin 4.x
- **Auth**: Symfony Security; 2FA bundle is present (Scheb)

## Assumptions (explicitly unverified)
- The “CRM” scope includes contacts/companies/opportunities and internal notes/tasks.
- EasyAdmin is the primary internal UI (some public-facing pages may also exist).

## Sources of truth
- Codebase (controllers, entities, config) is the source of truth for behavior.
- This Memory Bank is the source of truth for project context between sessions.
