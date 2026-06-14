# CourtGo Phase 1 — Foundation & Authentication — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up the Laravel app so people can register/log in (email + Google), with three roles (customer/owner/admin) and role-protected routes.

**Architecture:** Fresh Laravel 13 app using the official **Livewire starter kit** (Fortify-based auth pages). Add a `role` column to `users` (cast to a PHP enum), a `role:` route middleware, "Sign in with Google" via Socialite, and an admin seeder.

**Tech Stack:** PHP 8.3+, Laravel 13, Livewire 4, Tailwind 4, MySQL 8, Laravel Socialite, Pest. Local dev via Laravel Herd (Windows).

---

## Files created/modified in this phase
- `app/Enums/UserRole.php` *(create)* — the customer/owner/admin enum.
- `database/migrations/xxxx_add_role_and_google_to_users_table.php` *(create)* — `role`, `google_id`, nullable `password`.
- `app/Models/User.php` *(modify)* — fillable + cast for `role`/`google_id`.
- `app/Http/Middleware/EnsureUserHasRole.php` *(create)* — role gate.
- `bootstrap/app.php` *(modify)* — register the `role` middleware alias.
- `app/Http/Controllers/Auth/GoogleController.php` *(create)* — Google redirect + callback.
- `config/services.php` *(modify)* — Google credentials block.
- `routes/web.php` *(modify)* — Google routes + a role-protected `/owner` smoke route.
- `database/seeders/AdminUserSeeder.php` *(create)* + `DatabaseSeeder.php` *(modify)* — seed the admin.
- `resources/views/pages/auth/login.blade.php` *(modify)* — add the "Sign in with Google" button (this is the starter kit's login page — a Flux/Blade page).
- `tests/Feature/RoleAccessTest.php`, `tests/Feature/GoogleLoginTest.php`, `tests/Feature/AdminSeederTest.php`, `tests/Feature/DefaultRoleTest.php` *(create)*.

---

## Task 0: Prerequisites (one-time machine setup)

**No tests — this is environment setup. Do it once.**

- [ ] **Step 1: Install the tools**
  - **Laravel Herd for Windows** (free): https://herd.laravel.com/windows — run the installer **as administrator**. This gives you `php`, `composer`, and `laravel` in your terminal.
  - **Node.js LTS**: https://nodejs.org (needed for Tailwind/Vite asset building). Herd bundles Node, but a standalone LTS is fine too.
  - **MySQL 8**: install **MySQL Community Server** (https://dev.mysql.com/downloads/) or **Laragon** (https://laragon.org, bundles MySQL + a GUI). Remember the **root password** you set.

- [ ] **Step 2: Verify the tools** (open a new terminal)

Run:
```bash
php --version
composer --version
laravel --version
node --version
mysql --version
```
Expected: each prints a version (PHP 8.3+, Laravel installer present, Node 20+, MySQL 8).

- [ ] **Step 3: OneDrive heads-up (important on this machine)**

This project lives under `OneDrive\Desktop`. Laravel creates large `vendor/` and `node_modules/` folders that should **not** sync to OneDrive (it's slow and causes file-lock errors). Either:
  - Right-click the project folder → OneDrive → **"Always keep on this device"** is fine, but after Task 1, right-click `vendor` and `node_modules` → **"Free up space"** is NOT enough — instead exclude them, or
  - **Recommended:** move the project out of OneDrive (e.g. to `C:\dev\CourtGo`) before continuing. If you move it, run all later commands from the new path.

> If you move the folder, do it now. The rest of the plan uses the folder you choose as the project root.

---

## Task 1: Scaffold the Laravel app (Livewire starter kit) into this repo

**Files:** the whole Laravel app. **No automated test** — verified manually by loading the site.

This repo already contains `docs/` and `.git`. We generate the app in a temporary folder, then move its files in.

- [ ] **Step 1: Generate the app in a temp folder**

Run from the **parent** of the project folder (e.g. `C:\Users\lowky\OneDrive\Desktop` or `C:\dev`):
```bash
laravel new courtgo_tmp
```
Answer the prompts:
- **Starter kit:** `Livewire`
- **Authentication provider:** `Laravel's built-in authentication` (NOT WorkOS)
- **Testing framework:** `Pest`
- **Database:** `MySQL`
- **Run npm install / build:** `Yes`

(The starter-kit menu shows 5 options — None / React / Vue / Livewire / Svelte — pick **Livewire**. Non-interactive equivalent: `laravel new courtgo_tmp --livewire --database=mysql --pest`.)

- [ ] **Step 2: Move the generated files into this repo root**

Run from the parent folder (adjust `CourtGo` to your project folder name/path):
```bash
rm -rf courtgo_tmp/.git            # drop the temp repo; we keep this repo's history
shopt -s dotglob                   # include dotfiles in the move
mv courtgo_tmp/* CourtGo/          # Laravel's .gitignore overwrites the placeholder one
shopt -u dotglob
rmdir courtgo_tmp
```

- [ ] **Step 3: Re-add the brainstorm ignore to Laravel's .gitignore**

Append this line to `CourtGo/.gitignore` (Laravel's version already ignores `.env`, `/vendor`, `/node_modules`):
```
/.superpowers
```

- [ ] **Step 4: Create the MySQL database**

Run (enter the MySQL root password you set):
```bash
mysql -u root -p -e "CREATE DATABASE courtgo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
Expected: returns to prompt with no error.

- [ ] **Step 5: Point the app at MySQL**

In the project root, edit `.env` so the DB block reads:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courtgo
DB_USERNAME=root
DB_PASSWORD=your_mysql_root_password
```
Also set `APP_NAME=CourtGo`.

- [ ] **Step 6: Run migrations and start the app**

Run from the project root:
```bash
php artisan migrate
php artisan serve
```
Expected: migrations run green; the app serves at `http://127.0.0.1:8000`.

- [ ] **Step 7: Manually verify auth works**

In a browser, open `http://127.0.0.1:8000`, click **Register**, create an account, then log out and log back in.
Expected: registration + login succeed; you land on the dashboard.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Scaffold Laravel app with Livewire starter kit (MySQL)"
```

---

## Task 2: Add the `role` enum + columns to users

**Files:**
- Create: `app/Enums/UserRole.php`
- Create: `database/migrations/xxxx_add_role_and_google_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/DefaultRoleTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DefaultRoleTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;

test('a new user defaults to the customer role', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe(UserRole::Customer);
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --filter=DefaultRoleTest`
Expected: FAIL — `App\Enums\UserRole` class not found (and `role` not cast).

- [ ] **Step 3: Create the enum**

Create `app/Enums/UserRole.php`:
```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Owner = 'owner';
    case Admin = 'admin';
}
```

- [ ] **Step 4: Create the migration**

Run: `php artisan make:migration add_role_and_google_to_users_table`
Then set its contents:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('email');
            $table->string('google_id')->nullable()->after('role');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'google_id']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 5: Cast + fill the new fields on the User model**

In `app/Models/User.php`:
1. Add `use App\Enums\UserRole;` near the top.
2. Add `'role'` and `'google_id'` to the `$fillable` array.
3. Add a default so a brand-new `User` object has `role` set **in memory** (not just in the DB) — this avoids a null-role gotcha (a DB `default` is not loaded back onto a freshly-created model). Add this property:
```php
protected $attributes = [
    'role' => 'customer',
];
```
4. Add the `role` cast inside the `casts()` method:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];
}
```

- [ ] **Step 6: Migrate and run the test**

Run:
```bash
php artisan migrate
php artisan test --filter=DefaultRoleTest
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add role enum + role/google_id columns to users"
```

---

## Task 3: Role-based route protection

**Files:**
- Create: `app/Http/Middleware/EnsureUserHasRole.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/RoleAccessTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/RoleAccessTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;

test('a customer cannot open an owner-only route', function () {
    $customer = User::factory()->create(); // defaults to customer

    $this->actingAs($customer)->get('/owner')->assertForbidden();
});

test('an owner can open an owner-only route', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner')->assertOk();
});

test('a guest is redirected to login from an owner-only route', function () {
    $this->get('/owner')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --filter=RoleAccessTest`
Expected: FAIL — route `/owner` and the `role` middleware don't exist yet.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/EnsureUserHasRole.php`:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the middleware alias**

In `bootstrap/app.php`, inside `->withMiddleware(function (Middleware $middleware) { ... })`, add:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```

- [ ] **Step 5: Add the protected smoke route**

In `routes/web.php`, add:
```php
Route::get('/owner', fn () => 'Owner area')
    ->middleware(['auth', 'role:owner'])
    ->name('owner.dashboard');
```

- [ ] **Step 6: Run the test**

Run: `php artisan test --filter=RoleAccessTest`
Expected: PASS (all three).

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add role middleware + owner-only smoke route"
```

---

## Task 4: Seed the platform admin account

**Files:**
- Create: `database/seeders/AdminUserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/AdminSeederTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AdminSeederTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

test('the admin seeder creates an admin user', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@courtgo.test')->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe(UserRole::Admin);
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --filter=AdminSeederTest`
Expected: FAIL — `AdminUserSeeder` class not found.

- [ ] **Step 3: Create the seeder**

Create `database/seeders/AdminUserSeeder.php`:
```php
<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@courtgo.test'],
            [
                'name' => 'CourtGo Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
```

- [ ] **Step 4: Call it from DatabaseSeeder**

In `database/seeders/DatabaseSeeder.php`, inside `run()`, add:
```php
$this->call(AdminUserSeeder::class);
```

- [ ] **Step 5: Run the test, then seed your dev DB**

Run:
```bash
php artisan test --filter=AdminSeederTest
php artisan db:seed --class=AdminUserSeeder
```
Expected: test PASS; seeding creates `admin@courtgo.test` (password `password`).

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Seed platform admin account"
```

---

## Task 5: "Sign in with Google" (Socialite)

**Files:**
- Modify: `config/services.php`
- Create: `app/Http/Controllers/Auth/GoogleController.php`
- Modify: `routes/web.php`
- Modify: the login view (add the button)
- Test: `tests/Feature/GoogleLoginTest.php`

- [ ] **Step 1: Install Socialite**

Run: `composer require laravel/socialite`
Expected: installs `laravel/socialite` (v5.24+ — needed for the `Socialite::fake()` test helper below).

- [ ] **Step 2: Write the failing test**

This uses Socialite's official `fake()` helper (cleaner than hand-mocking).

Create `tests/Feature/GoogleLoginTest.php`:
```php
<?php

use App\Enums\UserRole;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('the google callback creates and logs in a new customer', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc-123',
        'name' => 'Ah Meng',
        'email' => 'ahmeng@example.com',
    ]));

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'ahmeng@example.com',
        'google_id' => 'google-abc-123',
        'role' => UserRole::Customer->value,
    ]);
    $response->assertRedirect('/dashboard');
});
```

- [ ] **Step 3: Run it to confirm it fails**

Run: `php artisan test --filter=GoogleLoginTest`
Expected: FAIL — `/auth/google/callback` route not defined.

- [ ] **Step 4: Add the Google config block**

In `config/services.php`, add to the returned array:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],
```

- [ ] **Step 5: Create the controller**

Create `app/Http/Controllers/Auth/GoogleController.php`:
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, remember: true);

        return redirect('/dashboard');
    }
}
```

- [ ] **Step 6: Add the routes**

In `routes/web.php`, add (with `use App\Http\Controllers\Auth\GoogleController;` at the top):
```php
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
```

- [ ] **Step 7: Run the test**

Run: `php artisan test --filter=GoogleLoginTest`
Expected: PASS.

- [ ] **Step 8: Add the button to the login page**

Open the starter kit's login page at **`resources/views/pages/auth/login.blade.php`** (it's a Blade page using Flux UI components — not a Livewire component). Just below the existing "Log in" submit button, add a Flux button that links to the Google redirect route:
```blade
<flux:button :href="route('google.redirect')" variant="outline" class="w-full mt-3">
    {{ __('Sign in with Google') }}
</flux:button>
```
(If you prefer a plain link instead of a Flux button, this also works:)
```blade
<a href="{{ route('google.redirect') }}"
   class="mt-3 flex w-full items-center justify-center rounded-md border px-4 py-2 text-sm font-medium hover:bg-gray-50">
    Sign in with Google
</a>
```

- [ ] **Step 9: (Real credentials — do when ready)**

To make the button work against real Google (not just the test): create OAuth credentials at https://console.cloud.google.com → APIs & Services → Credentials → "OAuth client ID" (Web application). Set the redirect URI to `http://127.0.0.1:8000/auth/google/callback`. Put the values in `.env`:
```
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```
(Tests pass without this because they mock Socialite.)

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "Add Sign in with Google via Socialite"
```

---

## Task 6: Phase wrap-up

- [ ] **Step 1: Run the whole test suite**

Run: `php artisan test`
Expected: ALL tests pass (the starter kit's auth tests + the four new feature tests).

- [ ] **Step 2: Manual smoke checklist**
  - `php artisan serve` → register a new account → confirm it lands on the dashboard.
  - Log in as the seeded admin (`admin@courtgo.test` / `password`).
  - Visit `/owner` while logged in as a non-owner → see a 403; (temporarily set a user's `role` to `owner` in the DB and confirm `/owner` shows "Owner area").

- [ ] **Step 3: Final commit / tag**

```bash
git add -A
git commit -m "Phase 1 complete: foundation & authentication" --allow-empty
git tag phase-1-complete
```

---

## Phase 1 done — what you have now
- A running Laravel app on MySQL.
- Email/password auth (register, login, password reset, email verification) from the starter kit.
- "Sign in with Google".
- Three roles with a reusable `role:` route guard.
- A seeded admin account.
- A green test suite.

**Next:** Phase 2 — Owner: Venues & Courts (we'll write that plan when Phase 1 is working).
