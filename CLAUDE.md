# pTable

A no-login web app for settling a home poker night: a manager records buy-ins and
cash-outs per player, and the app computes the provably minimal set of payments at
the end. Full spec: [requirement.md](requirement.md).

## Stack

Laravel 13 · Livewire 4 (class components in `app/Livewire`, views in
`resources/views/livewire`) · Flux UI (free tier) + **Majid DS**
(`mahdimajidzadeh/ds`) · Tailwind v4 (CSS-first, no `tailwind.config.js`) · MySQL.

**Read [vendor/mahdimajidzadeh/ds/llms.txt](vendor/mahdimajidzadeh/ds/llms.txt)
before writing or editing any Blade view.** It is the complete, current API
reference for every `<mds:*>` component, directive, and helper — more reliable
than guessing from a component's name. `<flux:*>` components are documented at
https://fluxui.dev/components (free tier only — this app has no Flux Pro licence,
see llms.txt "Rules of thumb for generated code" for the free-vs-Pro list and the
`mds:*` open alternatives to the Pro-only ones).

## Commands

```bash
composer dev          # serve + queue:listen + pail + vite, concurrently
php artisan test       # Pest suite (MySQL ptable_test, see phpunit.xml)
vendor/bin/pint        # format
vendor/bin/pint --test # check formatting without writing
```

## Invariants — do not violate these while editing

- **No `users` table, no `Auth` facade, no login.** A request's role (viewer /
  manager / super admin) is derived entirely from the URL hash(es) it arrives on
  plus the admin session flag. See requirement.md §4.3.
- **Money is unsigned integers only, never floats.** Direction is the entry
  `type` (`buy_in`/`cash_out`), never a sign. Totals are always computed from
  `entries`/`payments` at read time — never from a cached/denormalised column
  (NFR-4).
- **`manager_hash` must never reach a viewer.** Not in the rendered HTML, not in
  a `<meta>` tag, not in a redirect a viewer could see. Compare hashes with
  `hash_equals()`, not `==`.
- **An unknown or wrong hash is a 404, never a distinguishable error.** Don't let
  a manager-hash mismatch return a different status/message than a table that
  doesn't exist (NFR-3).
- **The settlement solver (`App\Settlement\Settler`) is pure and deterministic.**
  No DB access inside it; same input always produces the same transfer list
  (fixed player ordering, ascending submask enumeration, index tie-breaks — see
  requirement.md §8.4). Don't "simplify" it into a greedy-only solver — the
  §8.2 counterexample is a regression test (T-1) precisely because greedy is
  provably suboptimal in some real cases.
- **`<x-mds::…>`/`<x-flux::…>` spelling for anything with `wire:key`.** The
  `<mds:…>`/`<flux:…>` tag syntax fails to compile with `wire:key` on the
  opening tag (Livewire's precompiler rewrites it before the namespaced
  compiler runs) — see llms.txt setup contract item 5.
