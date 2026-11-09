# Progress

## Current status
- **Memory Bank**: initialized (core files created).

## What works (known)
- Symfony 7.4 application skeleton is present.
- Dependencies indicate support for:
  - Doctrine ORM + migrations
  - EasyAdmin admin UI
  - Symfony Security + Scheb 2FA
  - Twig templating
  - AssetMapper/Importmap + UX Turbo/Stimulus
- Client records can store `createdBy`; the client list displays creator plus creation date and can filter by creator.
- Client email export (`/admin/client/email-export`) uses the same index query as the client list, including EasyAdmin modal filters.

## What’s left to document (high priority)
- Actual domain entities and their relationships.
- Authentication model (User entity/provider), roles, access control.
- EasyAdmin structure (dashboards, CRUD controllers, custom pages).
- Environment setup details (DB, mailer, secrets handling) without storing secrets in git.

## Known unknowns / risks
- DB vendor and schema maturity.
- Whether there is a public-facing frontend beyond admin screens.
- Presence and handling of sensitive config files in the repo.
- Existing clients without matching `ActivityLog` create entries will show `Nieznany` as creator.
