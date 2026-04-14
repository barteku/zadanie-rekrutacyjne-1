# Notatki (krótko, tylko to co „ciekawe”)

## Logowanie — najważniejsza decyzja
W kodzie startowym logowanie było podatne na **SQL injection** i trzymało **token w URL** (`/auth/{username}/{token}`), co jest złe pod kątem logów (reverse proxy, historia przeglądarki, referrery).

Dlatego planuję to **etapami**:
- **Minimum (faza 1):** zamknięcie SQL injection + **jedno zapytanie** ładujące `AuthToken` razem z `User` (bez drugiego `SELECT`).
- **Pełna (faza 2):** `POST` z polem `token` + **CSRF** + wycięcie tokenu z URL (kontrakt HTTP bardziej „normalny” i bezpieczniejszy operacyjnie).

## Likes — dlaczego w ogóle ruszać model
Poza czytelnością kodu, realny problem to **spójność przy równoległych requestach** (race) i sensowna liczba `flush()` w jednej operacji biznesowej. Tu pomaga transakcja + twardsze reguły w bazie (np. unikalność pary user+photo), zamiast „łatać” objawy w kontrolerze.

## Integracja z PhoenixApi — jedna rzecz konfiguracyjna
Base URL PhoenixApi trzymam w **konfiguracji środowiskowej** (np. `.env` / zmienne w deployu) pod jednym parametrem (np. `PHOENIX_API_BASE_URL`), żeby **dev/staging/prod** mogły wskazywać różne hosty bez zmian w kodzie.

## AI
Korzystałem z AI głównie do **szybszego zrozumienia istniejącego kodu** i jako **wsparcie przy opracowaniu planu** (ryzyka, kolejność prac, checklista testów). Końcowe decyzje, zakres zmian i jakość rozwiązania weryfikuję samodzielnie.
