# Pokie — Design Specification

A design brief for Claude (or any designer) to design every page of Pokie.
Pokie is a no-login web app for tracking the money in a home poker game: a host
creates a table, adds players, records buy-ins and paybacks, and gets the
simplest way for everyone to settle up. See `requirement.md` for product scope.

---

## 1. Design Principles

- **Calm and effortless.** A host is using this mid-game, distracted, often on a
  phone. Every screen should be glanceable and need no instructions.
- **Numbers are the hero.** Balances and amounts are the most important content.
  Make them large, aligned, and instantly scannable.
- **Two clear modes.** It should always be obvious whether you're *viewing* or
  *managing* a table. Management actions never clutter the read-only view.
- **Mobile-first.** Design for a narrow phone screen first; scale up gracefully.
- **Friendly, not flashy.** Clean cards, soft shadows, generous spacing. No
  casino kitsch, no heavy gradients, no noise.

---

## 2. Brand & Tone

- **Personality:** approachable, trustworthy, quietly fun — like a good host.
- **Voice:** short, plain, encouraging ("Player added.", "Buy-in recorded.").
- **Name treatment:** "Pokie" in semibold, optionally with a small chip/spade
  glyph. Keep it understated.

---

## 3. Visual System

### Colors (already defined as CSS variables in `resources/css/app.css`)

| Token            | Value     | Use                                          |
| ---------------- | --------- | -------------------------------------------- |
| Background       | `#fafafa` | Page background                              |
| Card / surface   | `#ffffff` | Cards, panels, inputs sit on this            |
| Foreground       | `#111827` | Primary text / numbers                       |
| Muted foreground | `#6b7280` | Secondary text, labels, timestamps           |
| Primary (accent) | `#2563eb` | Buttons, links, active state, brand          |
| Success          | `#22c55e` | Positive balances, confirmations             |
| Destructive      | `#dc2626` | Negative balances, delete actions, errors    |
| Border           | `#e5e7eb` | Card borders, dividers, input outlines       |

**Semantic rule for money:** positive / up = success green, negative / down =
destructive red, zero / settled = muted gray. Apply this consistently everywhere
a balance appears.

### Typography

- **Font:** Instrument Sans (already loaded via Bunny Fonts).
- **Numbers / amounts:** consider the mono stack (`--font-mono`) or tabular
  figures so columns of money align cleanly.
- Scale: page title ~`display-6`/1.75rem bold, section headings ~1.1rem
  semibold, body ~0.95rem, labels/meta ~0.8rem.

### Shape, spacing, depth

- **Radius:** `0.75rem` on cards, inputs, and buttons (`--radius`).
- **Shadows:** soft and subtle (`--shadow-sm` for cards). Avoid harsh drop shadows.
- **Layout width:** centered column, max ~720px, comfortable padding (`py-8 px-4`).
- **Rhythm:** consistent 8px-based spacing; let cards breathe.

### Iconography

- **Bootstrap Icons** (already loaded). Suggested mapping:
  - Buy-in: `bi-arrow-down-circle` · Payback: `bi-arrow-up-circle`
  - Settlement: `bi-cash-coin` · Player: `bi-person`
  - Manager/edit: `bi-key` · Delete: `bi-trash` · Share: `bi-share`
  - Success: `bi-check-circle-fill` · Error: `bi-exclamation-circle-fill`

### Tech constraints

- Built with **Tailwind CSS 4 + Webpixels CSS (Bootstrap-based utilities) +
  Bootstrap Icons**, rendered in Blade templates. Reuse existing utility classes
  and the CSS variables above rather than introducing a new framework.

---

## 4. Shared Components

Design these once; reuse on every page.

- **App shell** (`layouts/app.blade.php`): centered column on `#fafafa`, flash
  message slot at the top.
- **Flash banners:** success (green tint) and error (red tint), rounded,
  dismissible, with a leading icon. Auto-styled, full width of the column.
- **Card:** white surface, subtle border/shadow, rounded `0.75rem`, generous
  inner padding. The primary building block of every page.
- **Primary button:** solid blue, rounded, large tap target. **Secondary
  button:** light gray fill, dark text. **Destructive button:** red/ghost-red
  for deletes, ideally with a confirm step.
- **Form field:** label (small, muted), input with `1px` border, focus ring in
  primary blue, inline validation error in red beneath.
- **Amount display:** a reusable treatment — large tabular number, colored by
  sign, with a leading +/− and currency-agnostic formatting.
- **Empty state:** friendly icon + one line of guidance + the primary action
  (e.g. "No players yet — add your first player").

---

## 5. Pages to Design

There are five pages. Design each for mobile first, then desktop.

### 5.1 Home / Create Table — `home.blade.php`
**Purpose:** the front door; create a new game or jump back into a recent one.

- **Hero:** one-line value prop ("Simple poker tracking for everyone").
- **Create card:** short helper text + "Table name" field + prominent
  "Create table" button. This is the primary action on the page.
- **Recent tables card** (only if the cookie has entries): a list of recently
  visited tables, each with the table name and quick "View" + (if known)
  "Manager" buttons. Show a tasteful empty/omitted state when there are none.
- **Feel:** welcoming and minimal — a first-time visitor should understand it in
  three seconds.

### 5.2 Table — View & Manage — `table/show.blade.php`
The most important screen. One template serves **two modes** (`$isManager`).

**Shared (both modes):**
- **Header:** table name, plus a clear mode indicator (e.g. a "View only" or
  "Manager" badge). Include a **Share** affordance for the view link.
- **Players & standings:** the centerpiece. Each player shows their name and
  current standing as a large amount, colored by sign (green up / red down /
  gray even). Easy to scan top to bottom; aligned numbers.
- **Suggested settlement:** a distinct card listing the minimum payments —
  "A pays B $X" rows, clearly readable. Show a settled/empty state when everyone
  is even ("All settled up 🎉").

**Manager mode adds:**
- **Add player** control (inline field or small form).
- **Record actions:** clearly separated buy-in, payback, and settlement inputs
  (pick a player + amount). Make buy-in vs. payback visually distinct using the
  green/red semantics.
- **Activity log:** a reverse-chronological feed of every buy-in, payback, and
  settlement, each row showing player, type (with icon), amount, time, and a
  **delete** control. Deletes should ask for confirmation.

**Layout guidance:** on mobile, stack as Header → Standings → Settlement →
(manager) Actions → Activity log. Keep the most-used actions within thumb reach.

### 5.3 Superadmin Login — `superadmin/login.blade.php`
**Purpose:** password gate for the global overview.

- Single centered card: short heading, password field, primary submit button.
- If superadmin isn't configured, show a quiet, non-alarming note that it's
  unavailable instead of a broken form.
- Surface invalid-password and rate-limit errors via the standard error banner.

### 5.4 Superadmin Dashboard — `superadmin/dashboard.blade.php`
**Purpose:** operator's read-only list of all tables.

- Heading + a logout button.
- A clean, paginated list/table of tables: name, created date, and a link to
  open each. Keep it utilitarian and dense but readable.
- Standard pagination controls at the bottom.

### 5.5 Welcome / Fallback — `welcome.blade.php`
The default Laravel page. Either redesign it to match the Pokie shell or remove
it from the user-facing flow. If kept, it should simply route people to Home.

---

## 6. States to Cover (don't skip these)

For every page, design the non-happy paths:

- **Empty:** no recent tables, no players yet, no transactions, nobody to settle.
- **Validation errors:** duplicate player name, missing/invalid amount, etc.
- **Loading/submission:** forms are server round-trips — buttons can show a
  pressed/disabled state on submit.
- **Errors & access:** invalid manager link (redirect + error banner), wrong
  superadmin password, rate-limited login.
- **Long content:** many players, long names, large activity logs — ensure
  wrapping, truncation, and scroll behave.

---

## 7. Accessibility

- Never rely on color alone for meaning — pair green/red balances with a +/−
  sign or label.
- Maintain AA contrast (notably muted text and colored amounts on white).
- All inputs have associated labels; all icon-only buttons have accessible names.
- Comfortable tap targets (≥44px) for the on-phone host.
- Logical focus order and visible focus rings (primary blue).

---

## 8. Deliverables for "design all pages"

For each of the five pages, produce:
1. A mobile layout and a desktop layout.
2. The key states from §6 (at minimum: default + empty + error).
3. Consistent use of the components in §4 and the tokens in §3.

Keep it cohesive: the whole app should feel like one calm, friendly product.
