# Strona - Car Rental System

PHP car rental site (PL/EN/UK). Users register, log in, browse vehicles, book them for dates and cancel bookings.

See README.md for full documentation (in Ukrainian).

## Running
```bash
php -S 0.0.0.0:5000 router.php
```
`router.php` must be used with the built-in server: it blocks public access to `data/` and `includes/`.

## Data storage
JSON files in `data/`. `vehicles.json` is tracked in git; `users.json` and `reservations.json` are created at runtime and are git-ignored (personal data). All writes go through `updateJSON()` in `includes/config.php`, which holds an exclusive file lock.

## Conventions
- Every POST form includes `csrf_field()` and the handler checks `verify_csrf()`.
- Protected pages call `requireLogin()`.
- All UI strings go through `t('key')`; keys must exist in all three files in `includes/langs/`.
