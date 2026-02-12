# Pokie

A Laravel application for tracking poker table sessions: buy-ins, paybacks, settlements, and player balances.

## Features

- **Create poker tables** – Each table gets a public link (view-only) and a manager link (add players, record transactions)
- **Track buy-ins** – Record when players add chips to the table
- **Track paybacks** – Record when players return chips to the bank
- **Settlements** – Record cash transactions between players
- **Minimum settlement transactions** – Algorithm computes the fewest transactions needed to settle all balances
- **Superadmin dashboard** – View all tables (requires `SUPERADMIN_PASSWORD` in `.env`)

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm (for assets)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Configuration

- **Database**: Configure in `.env` (SQLite by default)
- **Superadmin**: Set `SUPERADMIN_PASSWORD` in `.env` to enable the superadmin dashboard at `/superadmin`

## Development

```bash
composer run dev
```

Runs the web server, queue worker, logs, and Vite in parallel.

## Testing

```bash
composer test
```

## License

MIT
