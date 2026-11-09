# Product Context

## Why it exists
This project exists to centralize operational/customer data and workflows into a single, secure web application.

## Problems it aims to solve
- Scattered customer/contact data across files/tools
- Lack of consistent auditability and access control
- Slow internal workflows due to manual processes

## Who uses it
- **Internal users/admins**: manage data and workflows via the admin UI (EasyAdmin).
- **Operators/support/sales**: day-to-day data entry and updates (assumed; to confirm from roles/permissions).

## How it should work (high level)
- Users authenticate, then interact with domain data (entities) persisted via Doctrine.
- Admin/back-office tasks are handled through EasyAdmin CRUD screens.
- Security controls access; 2FA capability exists via Scheb 2FA (Google Authenticator).

## UX goals
- Fast, low-friction admin workflows (keyboard-friendly, minimal clicks).
- Clear validation and error messaging (Symfony Form/Validator stack is present).
- Predictable navigation and consistent UI patterns across entities.

## Open questions to answer from the codebase
- What are the primary domain entities (Contacts, Companies, Deals, Tickets, etc.)?
- What roles exist and what permissions do they grant?
- Which workflows are critical (imports, assignments, notifications, automation)?
