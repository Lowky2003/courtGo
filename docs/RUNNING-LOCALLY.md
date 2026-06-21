# Running CourtGo locally (after a PC restart)

Your setup uses **two tools**:

- **Laragon** → runs the **MySQL database** (the `courtgo` database, on port 3306).
- **Herd** → provides **PHP** (used by `php artisan serve`).

When you restart your PC, **Laragon's MySQL stops**, so the site shows a database
error until you start it again. Here's how to bring everything back.

---

## Quick start — do this every time after a restart

### 1. Start the database (Laragon)

The `courtgo` database is served by Laragon's MySQL — it must be running.

- Open **Laragon** and click **"Start All"** (this starts MySQL on port 3306).

> ⚠️ If you skip this, the site shows
> `Illuminate\Database\QueryException … [2002] No connection could be made` on port 3306.
> That just means **MySQL isn't running** — start it in Laragon.

> Manual fallback (only if Laragon won't start MySQL) — run in a terminal and leave it open:
> ```
> & "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" `
>     --basedir="C:\laragon\bin\mysql\mysql-8.4.3-winx64" `
>     --datadir="C:\laragon\data" --port=3306 --console
> ```
> (Don't run this **and** Laragon's "Start All" at the same time — they'd both fight for port 3306.)

### 2. Start the app server

Open a terminal and run:

```
cd C:\dev\CourtGo
php artisan serve
```

This serves the app at **http://127.0.0.1:8000**.

> If `php` isn't found, use the full Herd PHP path:
> ```
> & "C:\Users\lowky\.config\herd\bin\php84\php.exe" artisan serve
> ```

### 3. Open it in the browser

Go to **http://127.0.0.1:8000**.
Leave the `php artisan serve` window open while you work; press **Ctrl+C** to stop it.

---

## Optional

- **Live reload while editing CSS/views** — in a *second* terminal:
  ```
  npm run dev
  ```
  Otherwise the prebuilt assets are used. After changing CSS/JS, run once:
  ```
  npm run build
  ```

- **Empty / fresh database?** Create the tables (and demo data):
  ```
  php artisan migrate          # tables only
  php artisan migrate --seed   # tables + a demo owner & venue
  ```

---

## Demo login (after seeding)

- **Owner:** `owner@courtgo.test` / `password`
- Register a new account from the site to get a **customer**.

---

## Troubleshooting

| What you see | Cause | Fix |
|---|---|---|
| `QueryException … [2002] … refused` (port 3306) | MySQL not running | Start MySQL in **Laragon** ("Start All") |
| `Failed to listen on 127.0.0.1:8000` | A server is already running | Use it, or stop it and re-run `php artisan serve` |
| Page looks unstyled / missing CSS | Assets not built | `npm run build` |
| "table/column not found" errors | DB not migrated | `php artisan migrate` |

---

## Running the tests

Tests use an in-memory SQLite database, so they **don't** need MySQL/Laragon running:

```
php artisan test
```
