# System Patterns

## Architecture (current understanding)
- **Framework**: Symfony 7.4 application with standard directory layout.
- **Presentation**: Twig templating + Symfony UX (Turbo + Stimulus).
- **Admin UI**: EasyAdmin bundle used for back-office CRUD patterns.
- **Persistence**: Doctrine ORM entities and repositories; migrations via Doctrine Migrations.
- **Security**: Symfony Security bundle; Scheb 2FA bundles are present (Google Authenticator).

## Common patterns we expect to see (to confirm in code)
- **Controllers** in `src/Controller/` handling routes and rendering Twig templates.
- **Entities** in `src/Entity/` with Doctrine mapping (attributes or YAML/XML).
- **Repositories** in `src/Repository/` for query composition.
- **Forms/Validation** using Symfony Form + Validator for user input.
- **Admin CRUD** via EasyAdmin Dashboard + CRUD controllers (commonly under `src/Controller/Admin/`).
- **Security configuration** in `config/packages/security.yaml` defining providers, firewalls, access_control.
- **2FA flow** integrated through Scheb 2FA routes/config in `config/packages/scheb_2fa.yaml` and `config/routes/scheb_2fa.yaml`.

## Integration points (known)
- **Asset pipeline**: AssetMapper + Importmap (see `symfony/asset-mapper` recipe entries; `importmap.php` expected).
- **Logging**: Monolog bundle.
- **Mailer/Notifications**: Symfony Mailer + Notifier are present (usage to confirm).

## Decisions recorded (from dependencies)
- Stay on the Symfony 7.4 line (Flex constraint `7.4.*`).
- Prefer modern “no Node required” frontend where possible (AssetMapper/Importmap).

## Client list query (recorded)
- The client index applies EasyAdmin modal filters (`filters` query param) in `createIndexQueryBuilder()`, then `ClientListQueryFilter` for the custom `f_*` bar.
- Any bulk operation on the visible client list (e.g. email export) must reuse that same query builder instead of applying only `ClientListQueryFilter`. Otherwise modal filters are silently dropped.
