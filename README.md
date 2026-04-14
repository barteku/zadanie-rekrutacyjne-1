## Architektura

Ten projekt składa się z dwóch oddzielnych aplikacji z własnymi bazami danych:

- **Symfony App** (port 8000): Główna aplikacja internetowa
  - Baza danych: `symfony-db` (PostgreSQL, port 5432)
  - Nazwa bazy danych: `symfony_app`

- **Phoenix API** (port 4000): Mikroserwis REST API
  - Baza danych: `phoenix-db` (PostgreSQL, port 5433)
  - Nazwa bazy danych: `phoenix_api`

## Szybki start
```bash
docker-compose up -d

# Konfiguracja bazy danych Symfony
docker-compose exec symfony php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec symfony php bin/console app:seed

# Konfiguracja bazy danych Phoenix
docker-compose exec phoenix mix ecto.migrate
docker-compose exec phoenix mix run priv/repo/seeds.exs
```

Dostęp do aplikacji:
- Symfony App: http://localhost:8000
- Phoenix API: http://localhost:4000

Uwaga: migracja `Version20260414000000` tworzy rozszerzenie PostgreSQL `pg_trgm` i indeksy GIN pod filtrowanie `LOWER(...) LIKE`.

## Komendy Symfony

### Migracja bazy danych
```bash
docker-compose exec symfony php bin/console doctrine:migrations:migrate --no-interaction
```

### Ponowne tworzenie bazy danych
```bash
docker-compose exec symfony php bin/console doctrine:schema:drop --force --full-database
docker-compose exec symfony php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec symfony php bin/console app:seed
```

### Czyszczenie pamięci podręcznej (Cache)
```bash
docker-compose exec symfony php bin/console cache:clear
```

### Restart
```bash
docker-compose restart symfony
```

### Uruchamianie testów
```bash
docker-compose exec symfony php vendor/bin/phpunit
```

## Komendy Phoenix

### Migracja bazy danych
```bash
docker-compose exec phoenix mix ecto.migrate
```

### Seedowanie bazy danych
```bash
docker-compose exec phoenix mix run priv/repo/seeds.exs
```

### Ponowne tworzenie bazy danych
```bash
docker-compose exec phoenix mix ecto.reset
docker-compose exec phoenix mix run priv/repo/seeds.exs
```

### Restart
```bash
docker-compose restart phoenix
```

### Uruchamianie testów
```bash
docker-compose exec phoenix env MIX_ENV=test DB_HOST=phoenix-db mix test
```

## Weryfikacja zadania rekrutacyjnego

### 1) Jakość kodu (task 1)
- Sprawdź logowanie:
  - poprawny token: `http://localhost:8000/auth/nature_lover/<TOKEN>`
  - zły token: powinien pojawić się komunikat flash `Invalid token or username`
- Sprawdź like/unlike:
  - zaloguj się i kliknij serce na tej samej fotografii kilka razy
  - licznik powinien rosnąć/maleć poprawnie i nigdy nie schodzić poniżej `0`

### 2) Import z PhoenixApi (task 2)
- Otwórz profil użytkownika w Symfony (`/profile`).
- Wpisz token PhoenixApi (np. z seeda Phoenix: `test_token_user1_abc123`) i kliknij **Save token**.
- Kliknij **Import photos**:
  - poprawny token -> komunikat o liczbie zaimportowanych zdjęć,
  - błędny token -> komunikat o niepoprawnym tokenie.
- Kliknij import drugi raz: nie powinny pojawić się duplikaty.

### 3) Filtrowanie galerii (task 3)
- Na stronie głównej użyj formularza filtrów:
  - `location`
  - `camera`
  - `description`
  - `taken_at`
  - `username`
- Filtry łączą się logiką `AND`.

### 4) Rate limiting PhoenixApi (task 4)
- Endpoint: `GET http://localhost:4000/api/photos` z nagłówkiem `access-token`.
- Limity:
  - per user: 5 importów/10 min,
  - global: 1000 importów/godz.
- Po przekroczeniu limitu API zwraca `429` i nagłówek `Retry-After`.
