# Tech Context

## Tech stack
- **Language**: PHP (>= 8.2)
- **Framework**: Symfony 7.4.*
- **Database/Persistence**: Doctrine ORM 3.6, Doctrine Migrations
- **Admin UI**: EasyAdmin 4.x
- **Templating**: Twig
- **Frontend tooling**: AssetMapper, Importmap, Symfony UX Turbo, Symfony UX Stimulus
- **Security**: Symfony Security bundle; Scheb 2FA Google Authenticator
- **Testing**: PHPUnit (dev dependency)

## Repository conventions (observed)
- PSR-4 autoload: `App\\` => `src/`
- Console entrypoint: `bin/console`

## Local development (baseline)
- Install PHP 8.2+ and Composer
- Install dependencies:
  - `composer install`
- Run console commands:
  - `php bin/console`

## Config files likely relevant (to review when needed)
- `config/packages/doctrine.yaml`
- `config/packages/doctrine_migrations.yaml`
- `config/packages/security.yaml`
- `config/routes.yaml` and `config/routes/*`
- `config/packages/easyadmin.yaml` or `config/routes/easyadmin.yaml` (recipe indicates routes file)
- `.env`, `.env.dev` (Flex recipes indicate they should exist; presence in repo is unknown/ignored here)

## Constraints / risks
- Environment configuration (DB, mailer, app secrets) may be local-only and not committed.
- 2FA integration details and user entity requirements need verification from the actual security/user model.
