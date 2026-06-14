# CourtGo — Build Roadmap

> Companion to the design doc: `docs/superpowers/specs/2026-06-14-courtgo-design.md`.
> The app is built in **6 phases**. Each phase produces working, testable software and gets its own detailed plan file. Plan + build one phase at a time.

**Tech stack (from the spec):** PHP 8.3+ / Laravel 13 · Livewire 4 + Tailwind 4 + Flux UI · MySQL 8 · Laravel Cashier 16 (subscriptions) · Stripe Connect destination charges (payouts) · Socialite (Google) · Pest tests · Laravel Herd (Windows). Stripe in **test mode** throughout. Malaysia / MYR.

---

## Phase order & dependencies

```
1 Foundation & Auth
      │
2 Owner: Venues & Courts
      │
3 Owner: Schedule & Blocked Dates  ──► Availability service
      │
4 Subscriptions (Cashier) & Connect onboarding ──► "go live" gate
      │
5 Customer Browse + Booking + Payment (reserve→pay→webhook)
      │
6 Platform Admin
      │
(later) Polish & Go-Live: UI polish, deploy to Laravel Cloud/Railway, flip Stripe to live
```

Each phase depends on the previous one. Build in order.

---

## Phase summaries

### Phase 1 — Foundation & Authentication  *(plan: `…-phase-1-foundation-auth.md`)*
Scaffold the Laravel app with the official Livewire starter kit; configure MySQL; add a `role` column (customer/owner/admin) + role-based access; add "Sign in with Google" (Socialite); seed an admin account.
**Done when:** app runs locally, email + Google login work, a protected route rejects the wrong role, all tests pass.

### Phase 2 — Owner: Venues & Courts
Owner dashboard shell; `venues` CRUD; `courts` CRUD (with sport) under a venue; authorization so an owner only touches their own.
**Done when:** an owner can create/edit/delete venues and courts; another owner cannot; tests pass.

### Phase 3 — Owner: Schedule & Blocked Dates
`session_templates` CRUD (weekly recurring sessions per court, with price); `blocked_dates` CRUD; validation (end > start, no overlaps); the **AvailabilityService** that computes free sessions for a court+date (templates − active bookings − blocked − past).
**Done when:** owners define schedules; AvailabilityService is unit-tested for all the rules.

### Phase 4 — Subscriptions & Connect Onboarding
Install Cashier; `Billable` on the owner model; monthly subscription (card, test mode); Stripe Connect onboarding (Account + AccountLink), store `stripe_connect_account_id` + BRN; the **go-live gate** (active subscription AND connect onboarded → courts bookable); Cashier billing webhooks + a separate Connect webhook (`account.updated`).
**Done when:** an owner can subscribe + finish Connect onboarding in test mode, and the gate hides/shows their courts correctly; tests pass.

### Phase 5 — Customer Browse + Booking + Payment
Public browse/search (sport, area, date); court detail showing availability; the **reserve-then-pay** flow: hold a `pending` booking (with `hold_expires_at`) inside a `lockForUpdate` transaction → create a Stripe **destination-charge** PaymentIntent → confirm on the `payment_intent.succeeded` webhook (idempotent) → release/refund on failure; the **unique-index** double-booking backstop; a scheduled job to expire stale holds; "My bookings".
**Done when:** a customer completes a booking + test payment end-to-end, money is routed to the owner's connected account, and concurrent/duplicate attempts are provably blocked; tests pass.

### Phase 6 — Platform Admin
Admin dashboard (counts: owners, bookings, subscription revenue); manage owners (view/approve/suspend); set the subscription price; oversight of all venues/bookings.
**Done when:** the admin can manage the marketplace; tests pass.

### Later — Polish & Go-Live
Responsive/visual polish, friendly error pages, a manual pre-launch checklist, deploy to Laravel Cloud or Railway, and flip Stripe from test to live mode.

---

## Conventions used in every phase plan
- **TDD:** write a failing test → run it (red) → minimal code → run it (green) → commit.
- **Small commits**, one logical change each.
- **Exact file paths & commands** in every step.
- Secrets (Stripe keys, Google client secret) live only in `.env` (never committed).
