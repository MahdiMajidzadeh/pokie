# Pokie — Product Requirements

## 1. Summary

Pokie is a lightweight web app for tracking the money in a friendly poker game.
When a group plays poker with cash and chips, someone has to remember how much
each person put in, how much they cashed out, and who owes whom at the end of the
night. Pokie does that bookkeeping automatically and tells everyone the simplest
way to settle up.

There's nothing to install and no account to create. The host makes a table,
shares a link, and starts recording the game.

## 2. The Problem

Home poker nights run on memory and scraps of paper:

- People lose track of how many times someone "bought in" for more chips.
- At the end, working out who pays whom turns into messy mental math.
- Settling up often involves more handoffs of cash than necessary.
- Players want to see where they stand without being able to mess with the records.

Pokie replaces the paper and the arguments with a shared link.

## 3. Who It's For

- **The host** — runs the game, adds players, and records the money moving in and out.
- **The players** — want to watch their running balance and see the final tally.
- **An operator/admin** (optional) — someone who wants a bird's-eye view of every
  game that's been created.

## 4. How It Works (User Stories)

### Starting a game
- As a host, I can create a new table by giving it a name.
- When I create a table, I get two links: one to **manage** the game and one to
  **share** with players for viewing.

### Adding people and money
- As a host, I can add players to the table by name.
- As a host, I can record a **buy-in** whenever a player takes chips (puts money in).
- As a host, I can record a **payback** whenever a player returns chips (takes money out).
- As a host, I can record a **settlement** to log an actual cash payment between people.
- As a host, I can delete any buy-in, payback, or settlement I entered by mistake.
- As a host, I can see a running log of everything that's happened, newest first.

### Watching the game
- As a player with the view link, I can see every player and their current standing.
- As a player, I can see the suggested settlement at any time.
- As a player, I **cannot** add players or change any records — viewing only.

### Settling up
- As anyone viewing the table, I can see who is up, who is down, and by how much.
- As anyone, I can see the **fewest possible payments** needed so everyone ends
  even — Pokie figures out the simplest set of "X pays Y this much" transactions.

### Coming back later
- As a host or player, the tables I've recently visited are remembered so I can
  jump back into a game without hunting for the link.

### Overseeing all games (optional)
- As an operator, I can log in with a password to see a list of every table that
  has been created, most recent first.
- If no password is set up, this overview is simply turned off.

## 5. Key Concepts

- **Table** — one poker game/session. Everything is organized under a table.
- **Player** — a person at that table. Each player has a unique name within the game.
- **Buy-in** — money/chips a player takes from the bank to play with.
- **Payback** — money/chips a player returns to the bank.
- **Settlement** — a real cash payment recorded to balance things out.
- **Balance / standing** — how much each player is up or down right now.
- **Suggested settlement** — the minimal list of payments that makes everyone even.

## 6. Access & Sharing Rules

- Every table has two private links:
  - A **manage link** that allows adding players and recording/deleting money.
  - A **view link** that is read-only.
- Anyone with the view link can watch the game but cannot change anything.
- Only someone with the manage link can edit the game.
- The links are long and random, so a table stays private to whoever it's shared with.
- The optional admin overview is protected by a single password.

## 7. What's In Scope

- Creating and naming games.
- Adding players (no duplicate names within a game).
- Recording and deleting buy-ins, paybacks, and settlements.
- Showing live balances for each player.
- Calculating the minimum settlement.
- Separate manage vs. view access via shareable links.
- Remembering recently visited tables.
- An optional password-protected list of all tables.

## 8. What's Out of Scope

- User registration, profiles, or login for hosts and players.
- Real-money handling, payments, or payouts (Pokie only records who owes whom).
- In-game features like dealing cards, timers, or blinds.
- Editing past entries (records can be deleted and re-added, not edited in place).
- Notifications, chat, or messaging between players.

## 9. Success Criteria

- A host can set up a game and record the night's activity faster than with paper.
- At the end of a game, the suggested settlement is correct and uses the fewest
  payments possible.
- Players can check their standing from the shared link without being able to
  alter the records.
