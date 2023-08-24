# BACKEND of Miratime - Timekeeper System for Miraculous Company

FRONTEND: <https://github.com/laam-egg/miratime-fe>

## Get Started
1. Copy `.env.example` into a new file called `.env`.
2. Run:
```shell
php artisan key:generate
php artisan app:jwt
```
3. Update the following variables in `.env` if necessary:
```
APP_ENV=...<local, testing or production>
APP_DEBUG=...<true or false>
APP_URL=...<The application host URL>

DB_CONNECTION=...
DB_HOST=...
DB_PORT=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```