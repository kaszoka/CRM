# PolaCRM

System CRM oparty na frameworku Symfony 7.4.

## Wymagania

- PHP >= 8.3 (rozszerzenia: pdo_mysql, intl, ctype, iconv, mbstring)
- Composer
- Symfony CLI
- MySQL lub MariaDB (np. XAMPP)

## Szybki start

1. Instalacja zależności:
```bash
composer install
```

2. Konfiguracja środowiska:
Skopiuj plik `.env.example` do `.env` i ustaw dane dostępowe do bazy w zmiennej `DATABASE_URL`:
```bash
cp .env.example .env
```
*(W systemie Windows: `copy .env.example .env`)*

3. Utworzenie bazy danych i wykonanie migracji:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4. Utworzenie konta administratora:
```bash
php bin/console app:create-admin
```
Podaj w konsoli adres e-mail, hasło (min. 8 znaków), imię i nazwisko.

5. Uruchomienie serwera:
```bash
symfony server:start
```

Aplikacja jest dostępna pod adresem: http://127.0.0.1:8000  
Logowanie do panelu administracyjnego: http://127.0.0.1:8000/login
