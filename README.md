# PolaCRM

System CRM stworzony w oparciu o framework **Symfony 7.4** oraz **PHP 8.2+**.  
Aplikacja służy do zarządzania klientami, zadaniami, kontaktami i branżami z poziomu panelu administracyjnego **EasyAdmin**.

---

## 🛠 Wymagania systemowe

Przed uruchomieniem upewnij się, że posiadasz zainstalowane:
- **PHP** w wersji `>= 8.2` z rozszerzeniami: `pdo_mysql`, `intl`, `ctype`, `iconv`, `mbstring`
- **Composer** (menedżer pakietów PHP)
- **MySQL** lub **MariaDB** (np. poprzez pakiet XAMPP, Laragon lub Docker)
- Opcjonalnie: **Symfony CLI** (zalecane do uruchamiania lokalnego serwera)

---

## 🚀 Instalacja i pierwsze uruchomienie

Postępuj zgodnie z poniższymi krokami, aby postawić projekt na lokalnym środowisku:

### 1. Pobranie zależności (Composer)
Będąc w głównym katalogu projektu, pobierz wszystkie wymagane biblioteki:
```bash
composer install
```

### 2. Konfiguracja pliku środowiskowego (`.env`)
Skopiuj plik szablonu `.env.example` i utwórz z niego lokalny `.env`:
- **Windows (PowerShell / CMD):**
  ```powershell
  copy .env.example .env
  ```
- **Linux / macOS:**
  ```bash
  cp .env.example .env
  ```

Otwórz utworzony plik `.env` i upewnij się, że parametr `DATABASE_URL` odpowiada Twojej konfiguracji bazy danych MySQL:
```env
# Domyślna konfiguracja dla XAMPP (użytkownik root bez hasła):
DATABASE_URL="mysql://root:@127.0.0.1:3306/crm_pola?serverVersion=8.0.32&charset=utf8mb4"

# Jeśli Twoja baza posiada hasło:
# DATABASE_URL="mysql://uzytkownik:twoje_haslo@127.0.0.1:3306/crm_pola?serverVersion=8.0.32&charset=utf8mb4"
```

### 3. Utworzenie bazy danych i uruchomienie migracji
Wpisz w terminalu poniższe polecenia, aby utworzyć bazę danych oraz strukturę tabel:
```bash
# 1. Tworzenie pustej bazy crm_pola (jeśli jeszcze nie istnieje)
php bin/console doctrine:database:create

# 2. Wykonanie migracji struktury tabel
php bin/console doctrine:migrations:migrate
```
*(Gdy konsola zapyta o potwierdzenie wykonania migracji, wpisz `yes`)*.

---

## 👤 Tworzenie konta administratora (Dane do logowania)

Projekt zawiera wbudowaną komendę konsolową do utworzenia konta głównego administratora:

```bash
php bin/console app:create-admin
```

Konsola zapyta o:
1. **Adres e-mail** (będzie Twoim loginem, np. `admin@crmpola.pl`)
2. **Hasło** (minimum 8 znaków)
3. **Imię**
4. **Nazwisko**

Konto zostanie utworzone z uprawnieniami administratora (`ROLE_ADMIN`).

---

## 🖥 Uruchomienie aplikacji

Możesz uruchomić serwer deweloperski na kilka sposobów:

### Sposób 1: Symfony CLI (Rekomendowany)
```bash
symfony server:start
```
Aplikacja będzie dostępna pod adresem: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Sposób 2: Wbudowany serwer PHP
```bash
php -S 127.0.0.1:8000 -t public
```
Aplikacja będzie dostępna pod adresem: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Sposób 3: Apache w XAMPP
Upewnij się, że Apache w XAMPP jest uruchomiony i wskazuje na podkatalog `public/` (lub wejdź przez przeglądarkę pod adres `http://localhost/<sciezka_do_folderu>/public`).

---

## 🔐 Logowanie do panelu

1. Wejdź pod adres: [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login) (lub `/admin`).
2. Zaloguj się danymi podanymi w kroku `php bin/console app:create-admin`.
3. Po zalogowaniu zostaniesz przekierowany bezpośrednio do panelu EasyAdmin (`/admin`).

> **Wskazówka dotycząca 2FA:**  
> W profilu użytkownika dostępna jest opcjonalna integracja z Google Authenticator (dwuetapowa weryfikacja kodem z aplikacji mobilnej). Domyślnie dla nowo utworzonego konta jest ona wyłączona.
