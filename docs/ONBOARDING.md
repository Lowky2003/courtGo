# CourtGo — Onboarding a New Developer

How a **second developer** gets this project running on their own computer to work on it with you.

> Key idea: they do **NOT** run `laravel new`. They **clone your existing code** and install its dependencies. The big folders (`vendor/`, `node_modules/`) and secrets (`.env`) are **not** in git on purpose — each developer installs/creates their own.

---

## Part A — One-time team setup: put the code on GitHub

Right now the project is only on your computer (a local git repo, no remote). To share it, push it to **GitHub** once:

1. Create an empty repo at https://github.com/new (name it `courtgo`, keep it **private**, don't add a README).
2. In your project (`C:\dev\CourtGo`), connect and push:
```bash
git remote add origin https://github.com/<your-username>/courtgo.git
git push -u origin main
git push --tags
```
Now your teammate can clone it. (You only do this once; afterwards you both just `git push` / `git pull`.)

> ✅ Safe by design: `.env` (your passwords/keys) and `vendor/`, `node_modules/` are **git-ignored**, so they are NOT uploaded. Each developer keeps their own.

---

## Part B — The new developer's setup (on their PC)

### 1. Install the same tools
- **Laravel Herd** (https://herd.laravel.com/windows) → run installer, then **open the Herd app once** (downloads PHP). Open a **new terminal** afterward.
- **Node.js LTS** (https://nodejs.org)
- **MySQL** via **Laragon** (https://laragon.org) → **Start All** (user `root`, no password)
- **Git** (https://git-scm.com) if not already installed.

### 2. Get the code
```bash
cd C:\dev
git clone https://github.com/<your-username>/courtgo.git CourtGo
cd CourtGo
```

### 3. Install the PHP libraries (creates `vendor/`)
```bash
composer install
```

### 4. Install the JavaScript libraries & build the CSS/JS
```bash
npm install
npm run build
```

### 5. Create their own settings file
```bash
copy .env.example .env       # Windows (use 'cp' on Mac/Linux)
php artisan key:generate     # generates their unique APP_KEY
```

### 6. Set the database in `.env` and create it
Edit `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courtgo
DB_USERNAME=root
DB_PASSWORD=
```
Create the empty database:
```bash
mysql -u root -e "CREATE DATABASE courtgo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 7. Build the tables + add starter data (incl. admin account)
```bash
php artisan migrate --seed
```

### 8. Run it
```bash
php artisan serve
```
Open **http://127.0.0.1:8000**. Log in as `admin@courtgo.test` / `password`.

### 9. Confirm everything works
```bash
php artisan test
```
All tests should pass (green). If they do, the setup is correct. ✅

---

## Part C — Working together (the daily git workflow)

**Each new feature goes on its own branch, then a Pull Request:**
```bash
git checkout main
git pull                              # get the latest team code
git checkout -b feature/owner-venues  # start a feature branch
# ... write code + tests ...
php artisan test                      # make sure tests pass
git add -A
git commit -m "Add venue management"
git push -u origin feature/owner-venues
# then open a Pull Request on GitHub for review
```

**After pulling new code from teammates, re-sync:**
```bash
git pull
composer install      # in case someone added a PHP library
npm install           # in case someone added a JS library
php artisan migrate   # in case someone added database changes
```

---

## Part D — Important notes for teammates

- 🔐 **Everyone has their own `.env`** (their own database password, and later their own **Google** and **Stripe test** keys). Secrets are never committed.
- 🗄️ **Everyone has their own local database** — `php artisan migrate:fresh --seed` rebuilds it from scratch anytime.
- ▶️ **`php` not found?** Open a new terminal (Herd updates PATH).
- 🗄️ **Database connection error?** Start MySQL in **Laragon → Start All**.
- 📋 Read `docs/SETUP-AND-LEARNING.md` (how it was built) and `docs/superpowers/` (the design spec + phase plans) to understand the project.

---

## Quick reference: what's shared vs. personal

| Shared via GitHub (in git) | Personal / installed locally (NOT in git) |
|----------------------------|--------------------------------------------|
| All your code (`app/`, `routes/`, `resources/`, `database/migrations`, `tests/`) | `vendor/` → `composer install` |
| `composer.json`, `package.json` (the dependency **lists**) | `node_modules/` → `npm install` |
| `.env.example` (the settings **template**) | `.env` → copy from example + `php artisan key:generate` |
| `docs/` (spec, plans, guides) | Your local **database** → `php artisan migrate --seed` |
