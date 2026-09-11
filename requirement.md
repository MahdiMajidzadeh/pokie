# pTable — Requirements

**Version:** 0.1 (draft)
**Owner:** Majid
**Status:** For review

---

## 1. Overview

pTable is a small web app for settling a home poker game. One person (the *manager*) creates a table, adds players, records buy-ins and cash-outs during the night, and at the end the app calculates who owes whom and tracks which payments have been made.

There is no signup, no login, and no password. Access is controlled entirely by unguessable URLs.

**Primary user story:** *As the host of a poker night, I want to record every buy-in and cash-out on my phone so that at 2am I get an exact list of who pays whom, without arguments and without a spreadsheet.*

---

## 2. Goals and non-goals

### Goals
- Zero friction: a table can be created and the first player added in under 15 seconds.
- Accurate: the money on the table must always reconcile; the app refuses to settle an unbalanced table.
- Minimal payments: settle with the fewest possible transfers.
- Shareable: players can watch the table live from a read-only link.
- Mobile-first — it will be used on phones, standing at a table.

### Non-goals (v1)
- User accounts, profiles, or player history across tables.
- Tracking actual hands, blinds, chip stacks, or gameplay.
- Real money movement or payment gateway integration.
- Tournament structures, blind timers, or seat management.
- Multi-currency or currency conversion.

---

## 3. Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel |
| Interactivity | Livewire |
| UI | Majid DS |
| Database | MySQL |
| Language / direction | English only, LTR |

No SPA framework, no build-heavy frontend. Livewire handles all dynamic updates.

---

## 4. Access model

### 4.1 Hashes

Each table has **two independent 6-character hashes**:

| Hash | Purpose |
|---|---|
| `table_hash` | Identifies the table. Grants **read-only** access. |
| `manager_hash` | Second path segment. Grants **manage** access. |

- Alphabet: `23456789ABCDEFGHJKMNPQRSTUVWXYZ` (31 chars — digits `0`/`1` and letters `O`/`I`/`L` excluded to avoid transcription errors).
- Keyspace: 31^6 ≈ 887 million per hash. Combined manager keyspace ≈ 7.8 × 10^17.
- Generated with a cryptographically secure RNG, uniqueness enforced by a unique index with retry-on-collision.
- Lookup is case-insensitive; hashes are stored and displayed uppercase.
- The two hashes are unrelated — one cannot be derived from the other.

### 4.2 Routes

| Route | Access | Description |
|---|---|---|
| `GET /` | public | Landing page + "Create a table" |
| `POST /tables` | public | Creates a table, redirects to manager URL |
| `GET /{table_hash}` | viewer | Read-only table view |
| `GET /{table_hash}/{manager_hash}` | manager | Full management view |
| `GET /admin/login` | public | Super admin login form |
| `POST /admin/login` | public | Authenticates against env credentials |
| `POST /admin/logout` | admin | Ends the admin session |
| `GET /admin` | admin | List of all tables |

Rules:
- An invalid or unknown hash returns **404** (never "wrong password" — do not confirm that a table exists).
- The manager URL must **never** appear on the viewer page, in shared text, in `<meta>` tags, or in any server response rendered for a viewer.
- All table pages send `noindex, nofollow` and are excluded in `robots.txt`.
- Manager URL is shown once prominently after creation with a "Copy manager link" and a "Copy share link" button, and remains visible in the manager view's settings panel.

### 4.3 Roles

| Role | How it is established | Scope |
|---|---|---|
| Viewer | Knows `table_hash` | Read-only, one table |
| Manager | Knows `table_hash` + `manager_hash` | Full control, one table |
| Super admin | Logged in with env credentials | Manager-level control, **all** tables |

Roles are not stored anywhere. There is no `users` table, no roles table, and no per-table permission records. A request's role is derived entirely from the URL it arrives on plus the presence of a valid admin session.

### 4.4 Super admin

**Purpose:** operational support — fixing a broken table, recovering a lost manager link, moderating abuse, and inspecting data during development.

**Credentials**
- Stored in `.env`, never in the database, never in version control:
  ```
  PTABLE_ADMIN_USERNAME=majid
  PTABLE_ADMIN_PASSWORD=<plaintext password>
  ```
- **AR-1** The password is stored as plaintext in `.env`, the same way the database password and mail credentials already are. Comparison uses `hash_equals()` rather than `==`, so the check is constant-time and the password is never interpolated into a log line or an exception message.
- **AR-2** Both env vars are surfaced through `config/ptable.php` so config caching works. Never call `env()` outside config files.
- **AR-3** If either env var is missing or empty, the entire admin feature is disabled: `/admin/*` returns 404, not a login form. This is the safe default for any environment where the operator forgot to configure it.
- **AR-4** Changing the credentials requires editing `.env` and clearing the config cache. There is no in-app password change.

**Session**
- **AR-5** Successful login sets a session flag (e.g. `is_super_admin`). No `Auth` guard, no `User` model, no `remember me`.
- **AR-6** Session regenerates its ID on login and is fully invalidated on logout.
- **AR-7** Admin sessions expire after 2 hours of inactivity, shorter than the app's default session lifetime.
- **AR-8** Access is gated by a single middleware (`EnsureSuperAdmin`) applied to `/admin/*` and to the admin-elevation check on table routes.

**Powers**
- **AR-9** `GET /admin` lists every table: name, table hash, status, player count, total bought in, created at, last activity. Sortable, searchable by name or hash, paginated.
- **AR-10** From that list, the admin can open any table's **manager view** without knowing the `manager_hash`.
- **AR-11** While an admin session is active, requesting `/{table_hash}` renders the manager view instead of the viewer view, with a persistent banner: *"Viewing as super admin."* This keeps admin access working from any link that gets pasted to them.
- **AR-12** The admin can perform every manager action: add/edit/delete players and entries, settle, reopen, toggle payments.
- **AR-13** The admin can reveal a table's `manager_hash` (explicit "Show manager link" action) so a host who lost their link can be helped.
- **AR-14** The admin can delete a table entirely, with a typed-confirmation step. This is the only delete-a-table capability in the product.
- **AR-15** The admin list is the only place in the app where more than one table is visible. No other route may enumerate tables.

**Guardrails**
- **AR-16** Login is throttled: 5 attempts per 15 minutes per IP, then a 1-hour lockout. This is stricter than every other rate limit in the app because a single guessed password exposes every table.
- **AR-17** Failed and successful admin logins are written to the application log with IP, user agent, and timestamp.
- **AR-18** Every write action performed under an admin session is logged (table hash, action, before/after amount where applicable). Since there is no data model, this log is the only accountability record.
- **AR-19** `/admin/*` sends `noindex, nofollow` and is excluded in `robots.txt`.
- **AR-20** The admin login form must not reveal whether the username exists — one generic failure message for both wrong username and wrong password.
- **AR-21** Admin routes require HTTPS. Reject over plain HTTP in production rather than redirecting, since credentials are being posted.

**Accepted limitations of a credential-in-env design**
- One shared identity. If more than one person has the password, the log cannot tell them apart.
- No rotation without a deploy or a server-side `.env` edit plus cache clear.
- No MFA, no lockout of an individual, no recovery flow.
- The password is readable by anyone with server or `.env` read access, and by anyone who finds a misconfiguration that serves `.env` over HTTP. Acceptable only because the same person owns the app and the server (see NFR-10).

---

## 5. Domain model

### 5.1 Concepts

- **Table** — one poker session. Has a name, an optional default buy-in amount, and a status.
- **Player** — a name attached to a table. No account, no identity across tables.
- **Entry** — a single money movement for a player: either a `buy_in` (money onto the table) or a `cash_out` (money off the table). A player may have many of each.
- **Settlement** — the computed result once the table is closed: each player's net, plus the list of payments.
- **Payment** — one transfer, "A pays B amount X", with a paid/unpaid status.

### 5.2 Money handling

- All amounts stored as **unsigned integers in the smallest chosen unit** (e.g. Toman). No floats anywhere.
- Chips are not modelled. Everything is expressed directly in money.
- Only positive amounts are accepted; direction is expressed by entry type, not by sign.

### 5.3 Derived values

For each player:
```
total_in   = SUM(entries WHERE type = 'buy_in')
total_out  = SUM(entries WHERE type = 'cash_out')
net        = total_out - total_in     // positive = winner, negative = loser
```

For the table:
```
pot_in     = SUM(all buy_ins)
pot_out    = SUM(all cash_outs)
on_table   = pot_in - pot_out          // money still in play
imbalance  = SUM(all player nets)      // must be 0 to settle
```

---

## 6. Table lifecycle

```
open  ──(settle)──►  settled  ──(reopen)──►  open
```

| Status | Behaviour |
|---|---|
| `open` | Players and entries can be added/edited. Settlement is previewable but not final. |
| `settled` | Entries and players are locked. Payment list is frozen. Only payment paid/unpaid toggles are allowed. |

Reopening a settled table is allowed for the manager, clears the frozen payment list (with a confirmation warning that paid-marks will be lost), and returns the table to `open`.

---

## 7. Functional requirements

### 7.1 Create table (public)
- **FR-1** Anyone can create a table from `/` with a table name (optional, defaults to e.g. "Poker night — 12 Sep") and an optional default buy-in amount.
- **FR-2** On creation the app generates both hashes and redirects to `/{table_hash}/{manager_hash}`.
- **FR-3** The creation screen shows both links clearly with copy buttons and a warning: *"Save the manager link. It cannot be recovered."*
- **FR-4** Table creation is rate-limited per IP (see §11).

### 7.2 Manage players (manager)
- **FR-5** Manager can add a player by name only.
- **FR-6** Player names must be unique within a table (case-insensitive), so payment instructions are never ambiguous.
- **FR-7** Adding a player optionally records an immediate first buy-in using the table's default amount, in one tap.
- **FR-8** Manager can rename a player while the table is `open`.
- **FR-9** Manager can remove a player **only** if that player has no entries. Otherwise removal is blocked with an explanatory message.
- **FR-10** A player has an `active` / `left` state. Marking a player as *left* is a display concern only — it does not change the math.

### 7.3 Buy-ins and rebuys (manager)
- **FR-11** Manager can add a buy-in for any player at any time while the table is `open`. Unlimited rebuys.
- **FR-12** The buy-in form is prefilled with the table's default amount; the manager can override it.
- **FR-13** Every entry stores a timestamp and an optional short note.
- **FR-14** Manager can edit or delete any individual entry while the table is `open`.

### 7.4 Cash-outs and partial cash-outs (manager)
- **FR-15** Manager can record a cash-out for any player at any time while the table is `open` — this covers both a player leaving early and a player taking money off the table mid-session.
- **FR-16** Multiple cash-outs per player are allowed.
- **FR-17** A cash-out is **not** capped by that player's `total_in` — a winner cashes out more than they bought in. It is capped by `on_table` (see FR-18).
- **FR-18** A cash-out that would make `on_table` negative is rejected: *"Only {on_table} is left on the table."*
- **FR-19** Recording a cash-out offers a one-tap "and mark as left" option.

### 7.5 Live table view (manager and viewer)
- **FR-20** Both views show, per player: name, total in, total out, current net, and left/active state.
- **FR-21** Both views show table totals: total bought in, total cashed out, money still on the table, number of players.
- **FR-22** The viewer sees the same numbers but no action controls and no manager link.
- **FR-23** Views reflect changes without a full page reload (Livewire polling, ~5s, only while the table is `open`).

### 7.6 Settlement (manager)
- **FR-24** A "Settle table" action is available whenever the table is `open` and has at least 2 players with entries.
- **FR-25** Before settling, the app checks `imbalance == 0`. If not, settlement is blocked and the app shows the exact discrepancy:
  - `imbalance > 0` → more was cashed out than bought in ("You've cashed out {x} more than was bought in — check the entries.")
  - `imbalance < 0` → `{x}` is still unaccounted for on the table ("{x} is still on the table — cash out the remaining players.")
- **FR-26** A pre-settlement helper lists every player with a non-zero remaining stack expectation so the manager can quickly close them out.
- **FR-27** On settle, the app computes the payment list (§8), persists it, and moves the table to `settled`.

### 7.7 Payment list (manager and viewer)
- **FR-28** The settled view shows each player's final net, sorted winners first.
- **FR-29** Below it, the payment list: *"Ali pays Reza 250,000"*, one row per transfer.
- **FR-30** Each payment row has a paid/unpaid toggle, visible to the manager as a control and to the viewer as a status badge.
- **FR-31** Toggling records `paid_at`. Progress is summarised: *"3 of 5 payments settled."*
- **FR-32** The settled table can be shared via the viewer link so every player sees their own obligation.

---

## 8. Settlement algorithm

The settlement must produce the **provably minimum number of transfers**, not an approximation.

### 8.1 The objective

Given the non-zero nets, the minimum number of transfers is:

```
min_transfers = n - g
```

where `n` is the number of players with a non-zero net and `g` is the **maximum number of disjoint subsets that each sum to zero** that the set of nets can be partitioned into. Every zero-sum group of size `k` can always be settled in exactly `k - 1` transfers, and never fewer, so maximising the number of groups minimises the total.

### 8.2 Why a greedy match is not sufficient

Greedy largest-debtor-to-largest-creditor is close but not optimal. Concrete failure case:

| Player | Net |
|---|---|
| A | +6 |
| B | +4 |
| C | +1 |
| D | −5 |
| E | −3 |
| F | −3 |

- **Greedy:** D→A 5, E→B 3, then three leftover transfers of 1 each. **5 transfers.**
- **Optimal:** the set splits into `{A, E, F}` and `{B, C, D}`, both summing to zero. E→A 3, F→A 3, D→B 4, D→C 1. **4 transfers.**

This is not exotic — it happens whenever two players happen to lose amounts that exactly cover one winner. Greedy must therefore not be used as the primary algorithm.

### 8.3 Algorithm

**Step 1 — Normalise.**
Compute `net` for every player. Drop every player with `net == 0`; they neither pay nor receive. Let `n` be the number that remain.

**Step 2 — Cancel exact opposites.**
Any player pair `(+x, −x)` forms a zero-sum group of size 2 that is always part of some optimal partition. Emit that single transfer immediately and remove both players. This is a correctness-preserving reduction and it cuts `n` substantially on real tables, where identical buy-ins are common.

**Step 3 — Find the optimal partition (exact, bitmask DP).**

Over the remaining `n` players:

```
sum[mask]   = sum of nets of the players in mask          // precomputed, O(2^n)
g[0]        = 0
g[mask]     = max over submasks s ⊆ mask such that:
                 - s contains the lowest set bit of mask
                 - sum[s] == 0
              of ( 1 + g[mask \ s] )
```

`g[full]` is the maximum number of zero-sum groups. Reconstruct the partition by replaying the choices that produced each maximum.

- Complexity: `O(3^n)` time, `O(2^n)` memory. At `n = 15` that is ~14M operations and 32K memory slots — a few tens of milliseconds in PHP, computed once per settlement.
- The full set always sums to zero (guaranteed by FR-25), so a valid partition always exists; in the worst case `g = 1` and the answer is `n - 1`.

**Step 4 — Settle within each group.**
Inside a single zero-sum group, greedy largest-debtor-to-largest-creditor is provably optimal and yields exactly `size - 1` transfers. Apply it per group.

**Step 5 — Fallback.**
If `n > 15` after Step 2, fall back to plain greedy across the whole set and record that the result is approximate. A home poker table will not reach this; the guard exists so the app cannot hang.

### 8.4 Determinism

Multiple optimal partitions can exist with the same transfer count. The result must be identical on every recomputation:

- Players are indexed by `players.position`, then `players.id`.
- Submasks are enumerated in a fixed ascending order; the first partition achieving the maximum wins.
- Within a group, ties in amount are broken by player index.

### 8.5 Testing requirements

- **T-1** The §8.2 case must produce 4 transfers, not 5.
- **T-2** Randomised property test: for 1,000 random balanced tables of 3–12 players, the exact solver's transfer count must be ≤ the greedy count, and the sum of all payments received must equal each player's positive net exactly.
- **T-3** Every generated payment set must reconcile: for each player, `SUM(received) - SUM(paid) == net`.
- **T-4** All-pairs table (everyone wins or loses the same amount) must produce `n / 2` transfers.

**Sorting for display:** payments are grouped by payer, so each person sees their obligations together.

---

## 9. Data model

### `tables`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `table_hash` | char(6) | unique index |
| `manager_hash` | char(6) | index |
| `name` | varchar(80) | nullable |
| `default_buy_in` | bigint unsigned | nullable |
| `status` | enum(`open`,`settled`) | default `open` |
| `settled_at` | timestamp | nullable |
| `created_at` / `updated_at` | timestamps | |

Unique composite index on (`table_hash`, `manager_hash`).

### `players`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `table_id` | bigint FK | cascade delete |
| `name` | varchar(40) | unique per table (case-insensitive) |
| `has_left` | boolean | default false |
| `position` | smallint | display order |
| `created_at` / `updated_at` | timestamps | |

### `entries`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `table_id` | bigint FK | denormalised for fast totals |
| `player_id` | bigint FK | cascade delete |
| `type` | enum(`buy_in`,`cash_out`) | |
| `amount` | bigint unsigned | > 0 |
| `note` | varchar(120) | nullable |
| `created_at` / `updated_at` | timestamps | |

Index on (`table_id`, `player_id`, `type`).

### `payments`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `table_id` | bigint FK | cascade delete |
| `from_player_id` | bigint FK | |
| `to_player_id` | bigint FK | |
| `amount` | bigint unsigned | |
| `paid_at` | timestamp | nullable |
| `created_at` / `updated_at` | timestamps | |

Payments are generated only at settlement and wiped on reopen.

### No users table

There is deliberately **no `users`, `admins`, `roles`, or `sessions`-backed identity table**. Four tables is the entire schema. The super admin exists only as two env values and a session flag, so adding admin access requires no migration and leaves no identity data at rest.

---

## 10. Screens

### S1 — Landing (`/`)
One-line pitch, table name field, optional default buy-in, big "Create table" button. Short "how it works" strip below.

### S2 — Table created
Both links with copy buttons, manager link visually emphasised with the "cannot be recovered" warning. Continue button into the manager view.

### S3 — Manager view (`/{table_hash}/{manager_hash}`)
- Header: table name, status badge, share button.
- Summary strip: total in / total out / on table.
- Player list: each row shows name, in, out, net, with inline "+ Buy-in" and "Cash out" actions.
- Sticky bottom action: "Add player" and "Settle table".
- Entry history (collapsible), newest first, each editable/deletable.

### S4 — Viewer (`/{table_hash}`)
Same summary strip and player list, read-only, no manager link anywhere.

### S5 — Settlement
Blocked state shows the imbalance and the unclosed players. Success state shows final nets, then the payment list with paid toggles and progress.

**UI rules:** all screens built from Majid DS components, mobile-first, English LTR, amounts formatted with thousands separators, destructive actions confirmed.

---

## 11. Non-functional requirements

- **NFR-1** No authentication, no cookies required for core use. Optionally store recently visited manager links in `localStorage` for convenience only.
- **NFR-2** Rate limits: table creation 10/hour per IP; write actions 60/min per table hash.
- **NFR-3** Enumeration protection: unknown hashes 404 with a uniform response time; no listing endpoint of any kind.
- **NFR-4** All money mutations wrapped in DB transactions; totals computed from `entries` as the single source of truth (never from cached columns).
- **NFR-5** All table pages served over HTTPS with `noindex`.
- **NFR-6** Target: table page renders in under 500ms on a mid-range phone over 3G.
- **NFR-7** Input validation on every amount: integer, > 0, below a sane ceiling.
- **NFR-8** The admin session is the only cookie the app requires. Viewers and managers must remain fully functional with cookies blocked.
- **NFR-9** Admin actions must be indistinguishable from manager actions in the data — the table's history is the table's history. Attribution lives in the application log only.
- **NFR-10** `.env` must never be readable over HTTP; verify web root points at `public/`.
- **NFR-11** Settlement computation must complete in under 500ms for up to 15 non-zero players. It runs once per settle, inside the same transaction that persists the payments.

---

## 12. Edge cases

| Case | Behaviour |
|---|---|
| Player buys in but never cashes out | Blocks settlement; flagged in the pre-settlement helper. |
| Player cashes out more than the table holds | Rejected at entry time (FR-18). |
| Player with `net == 0` | Excluded from the payment list, still shown in the results table. |
| More than 15 non-zero players | Exact solver is skipped, greedy fallback used (§8.3 Step 5). Result may not be minimal. |
| Only one player has entries | Settle disabled. |
| Two managers editing simultaneously | Last write wins; Livewire refreshes totals. Acceptable for v1. |
| Manager loses the manager link | Unrecoverable by design. Stated explicitly at creation. |
| Table left open forever | See open question Q3. |
| Deleting an entry after settlement | Not possible; table must be reopened first. |
| Admin env vars not set | `/admin/*` returns 404; the rest of the app is unaffected. |
| Admin opens a table while the manager is editing it | Last write wins, same as two managers. Banner reminds the admin they are not the host. |
| Admin session active while the admin wants to see the viewer experience | Explicit "Exit admin view" toggle per table, or log out. |
| Manager link revealed to admin, then leaked | No rotation exists in v1 — see open question Q5. |

---

## 13. Out of scope for v1 (possible later)

- Per-player shareable link that highlights only that player's obligations.
- Export settlement as an image or text for messaging apps.
- Rake / house cut handling.
- Chip-denomination mode (chip counts converted to money at a fixed rate).
- Table templates and recurring groups.
- Persian / RTL localisation.

---

## 14. Open questions

1. **Currency unit** — Toman, Rial, or unit-agnostic (just numbers, no symbol)? Affects formatting and the input ceiling.
2. **Default buy-in** — should the table-level default be required, or is a free amount per entry enough?
3. **Data retention** — should tables be auto-purged after N days of inactivity (e.g. 90)? This matters for storage and for privacy posture, since anyone with a link keeps access forever.
4. **Viewer write access** — should a viewer be able to do anything at all (e.g. request a buy-in that the manager approves), or is strict read-only correct?
5. **Manager transfer** — should there be a way to rotate the manager hash if the link leaks?
6. **Admin audit depth** — is an application-log trail enough, or should admin writes be recorded in the database (which would mean adding one small table and breaking the "no data model" rule)?
7. **Admin table deletion** — hard delete or soft delete with a retention window? Hard delete is simpler but unrecoverable if the wrong row is picked.
8. **Second admin** — if anyone else ever needs access, the shared-password model stops being adequate. Worth deciding now whether that is ever likely.
