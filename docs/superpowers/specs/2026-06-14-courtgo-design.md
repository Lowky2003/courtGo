# CourtGo — Design Document

**Date:** 2026-06-14
**Status:** Approved (brainstorming complete) — pending final user review
**Author:** lowky + Claude
**Technical claims verified against official Stripe / Laravel docs on 2026-06-14** (see §12 references).

---

## 1. One-line summary

CourtGo is a **Laravel marketplace** where court owners pay a **monthly subscription** to list venues/courts and set weekly session schedules, and customers **book and pay** for sessions online with money going **straight to owners via Stripe Connect** (platform takes 0% of bookings). Built beginner-friendly with Livewire, developed entirely in Stripe **test mode** first. Malaysia-only (MYR).

---

## 2. Goals & non-goals

### Goals (in scope for v1)
- Multi-owner **marketplace** (many independent court businesses).
- Owners can list **venues** containing multiple **courts** of **any sport**.
- Owners set a **weekly recurring schedule** of bookable **sessions** (each with its own length + price) and can **block specific dates**.
- Customers **browse/search**, **book**, and **pay online** for a session.
- Three roles: **customer**, **owner**, **platform admin**.
- **Two money flows:** owner monthly subscription (→ platform) and per-booking payment (→ owner, 0% to platform).
- Email/password **and** Google login.
- Everything works in Stripe **test mode**, structured to flip to live later.

### Non-goals (explicitly out for v1, may add later)
- **Reviews / ratings** on courts.
- **Customer cancellations / refunds** (bookings are final once paid; the only refunds are automatic, for the rare double-booking race).
- Phone/OTP (SMS) login.
- Mobile native apps (web only; responsive).
- Multi-language (English only for v1).
- Platform taking a per-booking commission (model is subscription-only).
- **Non-Malaysian owners.** Stripe forbids cross-border application fees and cross-border payouts to Malaysia, so all owners must be Malaysia-registered businesses.

---

## 3. Users & roles

A single login system; each `user` has a `role` that decides what they can access.

| Role | Who | Can do |
|------|-----|--------|
| **customer** | The public | Browse/search courts, book + pay, view "my bookings", manage profile. |
| **owner** ("boss") | Court businesses | Subscribe, connect payout bank, manage venues/courts, set weekly schedule, block dates, view their bookings, manage billing. |
| **admin** | The platform operator (you) | Dashboard/oversight, manage owners (approve/suspend), set subscription price, view all venues/bookings, settings. |

For v1, one role per user (an owner account is separate from a customer account).

---

## 4. Technology stack

> Versions confirmed current as of June 2026.

| Layer | Choice | Notes |
|-------|--------|-------|
| Backend language | **PHP 8.3+ with Laravel 13** | Core framework. (Laravel 12 also fine; 13 is current.) |
| Interactive UI | **Livewire 4 + Tailwind CSS 4 + Flux UI** | Build the calendar/dashboards in mostly-PHP; Alpine.js is bundled with Livewire. |
| Auth scaffold | **Official Laravel Livewire starter kit** (Fortify-based) | Ready-made login, register, password reset, email verification. **Not Laravel Breeze** (retired for Laravel 12+). 2FA is on by default in Fortify — can be disabled. |
| Google login | **Laravel Socialite** | "Sign in with Google" — wired manually (redirect + callback routes) since the starter kit uses Fortify. (Alt: WorkOS AuthKit variant for built-in social login, needs a WorkOS account.) |
| Database | **MySQL 8** — development **and** production | Used throughout (per preference). Needs a local install on Windows: Herd Pro's managed database, or a standalone MySQL (e.g. MySQL Community Server / Laragon). PostgreSQL is an easy drop-in alternative (cleaner partial-index support) if preferred later. |
| Owner subscriptions | **Laravel Cashier 16 (Stripe Billing)** | Recurring monthly subscriptions. Paid by **card** (FPX/GrabPay can't do recurring). |
| Booking payments → owner | **Stripe Connect — destination charges** via the **stripe-php** that Cashier ships (v17.x) | `application_fee_amount = 0` routes 100% to the owner. **Do not** separately pin a newer `stripe/stripe-php` — use `Cashier::stripe()` / one `StripeClient`. |
| Local dev (Windows) | **Laravel Herd for Windows** (v1.28+) | Bundles PHP + nginx + Node. Installer needs administrator rights. For MySQL: use Herd Pro's managed database, or install MySQL standalone (e.g. MySQL Community Server / Laragon). |
| Going live (later) | **Laravel Cloud** or **Railway** | Provides managed Postgres/MySQL; flip Stripe to live mode. |

**Key stack decisions & why:**
- **Livewire over React/Vue** — beginner stays in PHP.
- **New Livewire starter kit over Breeze** — Breeze is retired for Laravel 12+; the new kit is the supported, documented path (Fortify + Flux UI + Tailwind 4).
- **Cashier for subscriptions, stripe-php (Connect) for payouts** — Cashier doesn't handle marketplace payouts; they coexist as long as we use the single stripe-php Cashier installs.
- **MySQL throughout (dev + prod)** — most hosting/tutorial support in Malaysia; PostgreSQL is an easy swap if ever wanted.

---

## 5. Screens / pages by role

### Customer (public + logged-in)
- **Home / Browse** — search courts by sport, area, date.
- **Court/Venue detail** — photos, info, prices, available sessions.
- **Booking & checkout** — pick a session → pay.
- **Booking confirmed** — receipt + details.
- **My bookings** — upcoming & past.
- **Sign up / Log in** — email or Google.
- **Profile**.

### Owner ("boss") dashboard
- **Get started / onboarding** — subscribe (monthly, card) + connect bank for payouts (Stripe Connect, incl. **Business Registration Number** needed for FPX).
- **Dashboard** — today's bookings, earnings.
- **My venues & courts** — add/edit venue (name, address, photos) and its courts (name, sport, active).
- **Weekly schedule** — set recurring sessions per court (day, start, end, price).
- **Block dates** — holidays / maintenance.
- **Bookings** — who booked, when.
- **Billing** — manage subscription & payout/connect status.

### Platform Admin (you)
- **Admin dashboard** — totals: owners, bookings, subscription revenue.
- **Owners** — view, approve, suspend.
- **Subscription plans** — set monthly price(s).
- **All venues & bookings** — oversight.
- **Settings**.

---

## 6. Data model

Tables and key fields (Laravel migrations). Relationships noted as "→".

### `users`
- `name`, `email`, `password` (nullable for Google-only), `google_id` (nullable)
- `role` — `customer` | `owner` | `admin`
- Owner-only payment fields:
  - `stripe_id` (Cashier customer id, for the subscription)
  - `stripe_connect_account_id` (for receiving booking payouts)
  - `connect_onboarded` (bool — Connect onboarding complete)
  - `business_registration_number` (BRN — required by Stripe FPX)
  - `is_subscribed` (convenience/derived from Cashier)

### `venues` → `users` (owner)
- `owner_id`, `name`, `description`, `address`, `city`, `state`, `photos` (or a related `venue_photos` table)

### `courts` → `venues`
- `venue_id`, `name` (e.g. "Court 1"), `sport`, `is_active`

### `session_templates` → `courts`
- `court_id`, `day_of_week` (0–6), `start_time`, `end_time`, `price`, `is_active`
- Defines the **weekly recurring availability** (e.g. "every Monday, 9–11am, RM40").

### `blocked_dates` → `courts`
- `court_id`, `date`, `reason`
- Days an owner closes a court.

### `bookings` → `users` (customer) + `courts`
- `customer_id`, `court_id`, `session_template_id` (reference, nullable)
- `booking_date`, `start_time`, `end_time`, `price`
- `status` — `pending` | `confirmed` | `cancelled` | `expired`
  - `pending` = slot held while paying; `confirmed` = paid; `cancelled`/`expired` = freed.
- `payment_status` — `unpaid` | `paid` | `refunded`
- `stripe_payment_intent_id`
- `hold_expires_at` (when an unpaid `pending` hold is released)
- `processed_at` (idempotency — set when its webhook has been handled)
- **Active-scoped uniqueness** on `(court_id, booking_date, start_time)` for active rows only (see §7 for the per-database technique).

### `subscriptions` (+ `subscription_items`) → `users` (owner)
- Managed by **Laravel Cashier** automatically.

### `payments` (optional log)
- `booking_id`, `amount`, `owner_account_id`, stripe ids, timestamp — a readable record (Stripe remains source of truth).

**Core idea:** `session_templates` define the repeating weekly availability; a `booking` consumes one specific date+time slot and locks it while paid.

---

## 7. Booking & availability logic

### Availability calculation (for a chosen court + date)
```
available sessions =
    session_templates for that weekday (is_active)
  − slots with an active booking (pending or confirmed) for that exact date
  − sessions on blocked dates
  − past times (cannot book the past)
```

### Reserve-then-pay sequence (prevents double-booking AND charging for an unavailable slot)
1. **Reserve (hold):** in a `DB::transaction()`, `lockForUpdate()` the relevant court/slot rows, re-check availability, then `INSERT` a **`pending`** booking with `hold_expires_at = now()+10min`. If a duplicate-key error is caught, the slot was just taken → return "slot unavailable" cleanly.
2. **Create the Stripe PaymentIntent** (destination charge to the owner) and return its `client_secret` to the browser. Slot is now held but not confirmed — we reserved **before** charging.
3. **Customer pays** on the client (Stripe Payment Element). Do **not** confirm from the browser redirect.
4. **Confirm via webhook (source of truth):** on `payment_intent.succeeded`, flip the booking `pending → confirmed`, `payment_status = paid`. Handler is **idempotent** (guard with `processed_at` / event id).
5. **Release/refund on failure:** on `payment_intent.payment_failed`/`canceled`, set `cancelled`/`expired` (frees the slot). A **scheduled job** (every minute) expires `pending` holds past `hold_expires_at`. If a rare race ever charges for a lost slot, refund via the Stripe API.

### No-double-booking — defense in depth (three layers)
1. **Unique index** = the unbreakable backstop (DB physically refuses a 2nd active booking for the same slot).
   - **MySQL (our DB):** no partial indexes — use a generated column: `active_flag = 1` for `pending/confirmed` else `NULL`, then `UNIQUE (court_id, booking_date, start_time, active_flag)` (MySQL allows multiple NULLs, so cancelled/expired rows don't block re-booking).
   - *(PostgreSQL alternative: a partial unique index — `UNIQUE (court_id, booking_date, start_time) WHERE status IN ('pending','confirmed')`.)*
2. **`lockForUpdate()` in a transaction** = clean serialized checks + friendly "unavailable" message.
3. **Webhook-confirm-after-pay** = reserve before charging, finalize only on real payment success.

---

## 8. Payments architecture

Two independent flows, both in Stripe **test mode** during development. **Malaysia-domestic only** (platform + all owners registered in Malaysia; MYR).

### Flow 1 — Owner subscription (platform revenue)
- Owner → pays platform **monthly** via **Laravel Cashier** (Stripe Billing), **by card** (FPX/GrabPay don't support recurring).
- `Billable` trait on the owner/User model; `newSubscription('default', $priceId)->create()`.
- If the subscription lapses → owner's courts are **hidden / not bookable**; already-paid bookings still stand.

### Flow 2 — Booking payment (0% to platform)
- Customer → pays → **Stripe Connect destination charge** → **100% to the owner's connected account** (`application_fee_amount = 0`).
- Payment methods: **cards + FPX + GrabPay** (MYR).
- Built with `stripe-php` directly (the version Cashier ships). Keep a `ConnectService` separate from a Cashier-based `SubscriptionService`.

### Owner onboarding gate
Before an owner's courts can be booked, the owner must have **both**:
1. An **active subscription**, and
2. A **completed Connect onboarding** (connected payout account, including **BRN** for FPX).

### Connect model note (Malaysia)
Only the Connect model where **Stripe collects fees and owns loss liability** is generally available in Malaysia (the platform-liable model is preview-only). **Destination charges** fit the available model — confirm in the Stripe Dashboard when enabling Connect.

### Webhooks
The app verifies signatures (raw body) and is **idempotent** (never double-confirms):
- **Billing events** → Cashier's auto-registered webhook controller (subscription renewed / payment failed / customer updates).
- **Connect / payment events** (`payment_intent.succeeded`, `payment_intent.payment_failed`, `account.updated`) → a **separate dedicated endpoint/handler** with its own signing secret (clearer for a beginner; Connect events come from connected accounts).

---

## 9. Error handling & edge cases

| Situation | Handling |
|-----------|----------|
| Two people grab the same slot | Unique index (backstop) + `lockForUpdate` + payment-first; loser told "just taken" (refunded if charged). |
| Card fails / customer abandons checkout | Booking stays `pending`, then a scheduled job expires it; slot frees. |
| Owner stops paying subscription | Courts hidden; existing paid bookings honored. |
| Owner hasn't completed Connect onboarding | Courts cannot go live. |
| Owner missing BRN | FPX unavailable for them; onboarding requires it. |
| Booking a blocked/past date | Prevented; those sessions don't show. |
| Owner enters bad schedule (end ≤ start, overlaps) | Form validation rejects it. |
| Customer accesses owner/admin pages | Authorization (policies/middleware) blocks it. |
| Stripe webhook received | Verify signature (raw body); idempotent via `processed_at`/event id. |
| Secrets (Stripe keys) | Stored in `.env`, never committed. |
| Non-Malaysian owner tries to onboard | Out of scope/blocked — Stripe cross-border rules forbid it. |

---

## 10. Testing approach

- **Test-driven** for the important logic, written as we build:
  - Availability calculation (templates − active bookings − blocked − past).
  - No-double-booking (concurrent attempts; unique-index + lock).
  - Reserve → pay → webhook-confirm flow, incl. **idempotency** and hold expiry.
  - Auth & authorization (role access).
- Tooling: **Pest/PHPUnit** (Laravel built-in).
- **Stripe test mode** with test cards + **Stripe CLI** (`stripe listen --forward-to`) to drive webhooks locally; test-mode **connected accounts** for Connect (Malaysia test payout bank details exist).
- A **manual pre-launch checklist**.

---

## 11. Future / later (not v1)
- Reviews & ratings.
- Customer-initiated cancellations + refund policy.
- Phone/OTP login.
- Multi-language.
- Production deployment (flip Stripe to live mode; managed Postgres/MySQL on Laravel Cloud/Railway).

---

## 12. Technical references (official docs)
- Stripe Connect availability — Malaysia: https://support.stripe.com/questions/connect-availability-for-businesses-located-in-malaysia
- Stripe Connect charges (destination/application fee): https://docs.stripe.com/connect/charges
- Stripe Connect testing (test mode, MY payout test details): https://docs.stripe.com/connect/testing
- Stripe FPX (MY, BRN, payout timing): https://docs.stripe.com/payments/fpx
- Stripe GrabPay: https://docs.stripe.com/payments/grabpay
- Stripe fulfillment / webhooks (confirm on webhook, idempotency): https://docs.stripe.com/checkout/fulfillment · https://docs.stripe.com/webhooks/handling-payment-events
- Laravel Cashier (Stripe billing): https://laravel.com/docs/13.x/billing
- Laravel starter kits (Livewire kit, replaces Breeze): https://laravel.com/docs/12.x/starter-kits
- Laravel Socialite (Google): https://laravel.com/docs/12.x/socialite
- Livewire (v4): https://livewire.laravel.com/docs/installation
- Laravel queries — pessimistic locking (`lockForUpdate`): https://laravel.com/docs/11.x/queries
- Laravel Herd for Windows: https://herd.laravel.com/docs/windows/getting-started/installation
