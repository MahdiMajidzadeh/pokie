# Deploying pTable

pTable deploys as a **single Dokploy Application**: one container, one image, one
webhook. This doc is the setup guide for that Application and the release pipeline
that feeds it.

This is pTable's **first-ever deployment setup** — there is no prior Dokploy
Application, no existing compose stack, and no data to migrate. (If you're looking for
a "how we converged from an old setup" story: there isn't one for this repo. pTable
adopts the same deployment pattern as its sibling repos — fila, pm-feed, tv-time,
shorter-is-better — from a clean slate.)

## How a release works

```
git tag v0.1.0 && git push origin v0.1.0
        │
        ▼
.github/workflows/release.yml builds the image (target: runtime)
        │
        ▼
image pushed to ghcr.io/mahdimajidzadeh/pokie:0.1.0 / :0.1 / :latest / :sha-<short>
        │
        ▼
Dokploy webhook is called (POST, no payload needed)
        │
        ▼
Dokploy pulls the new image and redeploys the one Application
```

A pull request builds the image (`target: runtime`, no push) so a broken Dockerfile is
caught before merge, not at release time.

**To release:**

```bash
git tag v0.1.0
git push origin v0.1.0
```

or, since a bare tag push has not always reliably produced a GitHub `push` event on
every account/network combination:

```bash
gh release create v0.1.0 --generate-notes
```

Both are handled by `.github/workflows/release.yml`; there's also a manual
`workflow_dispatch` trigger (GitHub UI → Actions → Release → Run workflow) to rebuild
and redeploy an existing tag without pushing a new one.

**Tag format:** `v[0-9]*.[0-9]*.[0-9]*` — e.g. `v0.1.0`, `v1.2.3`, `v1.2.3-rc1`. A
digit must immediately follow the `v`; this is deliberately strict; see the comment in
`release.yml` above the `on.push.tags` block.

## Process model

The container runs [supervisord](docker/supervisord.conf) as PID 1. As of this setup,
it runs **one program: `web`** (FrankenPHP/Caddy serving the app on `:8080`).

**Why no `worker` or `scheduler` program:** audited directly against the code —

| Check | Finding |
|---|---|
| `app/Jobs/`, any `ShouldQueue` implementor | none exist |
| Queued mail / notifications (`->queue()`, `shouldQueue()`) | none found |
| `QUEUE_CONNECTION` in `.env.example` | `database` — Laravel's installer default, never actually dispatched against |
| `routes/console.php` | only the stock `inspire` Artisan command |
| `bootstrap/app.php` → `withSchedule()` | never called |

So there is nothing for `queue:work` or `schedule:work` to do. supervisord still owns
PID 1 rather than a bare `frankenphp run` `CMD`, on purpose: every repo in this family
(fila, pm-feed, tv-time, shorter-is-better) deploys with the same process model, so
adding a worker later — if pTable ever grows a background job — is a few lines in
`docker/supervisord.conf`, not a rebuild of the container's whole design. See the
comment block at the top of that file for exactly what a new program needs
(`autorestart=true`, stdout/stderr logging with `_maxbytes=0`, a `stopwaitsecs` sized
to the job, and a matching bump to the Dokploy stop timeout).

**Checking liveness:** the container healthcheck only probes `/up` (the web server) —
deliberately, so a database hiccup can never turn into a restart loop. That means a
crashed background program would not currently show up as an unhealthy container, but
since there is no background program today, this is a documented non-issue rather than
an active risk. If a worker is added later, check it with:

```bash
docker exec <container> supervisorctl status
```

## Release flow build-time notes

- **Registry cache, not `type=gha`:** this workflow triggers on tag pushes, and
  GitHub's `type=gha` cache is scoped per ref — consecutive releases (different tags)
  would almost never see each other's cache, making every release a cold rebuild. The
  workflow instead pushes a `:buildcache` tag to GHCR (`type=registry`), which is
  shared across every ref/event, so a release only rebuilds the layers that actually
  changed. Pull requests read that cache but don't have GHCR credentials to write it.
- **`platforms: linux/amd64` only:** the Dokploy host is x86; cross-building PHP
  extensions under QEMU for arm64 adds real minutes for no benefit here.
- **`provenance: false`, `npm ci --ignore-scripts`:** both trim real build time/noise
  with no functional cost for this app.
- **Layer ordering in the Dockerfile:** extensions are installed on the `base` image
  line, `composer install` is keyed only on `composer.json`/`composer.lock`, and
  `npm ci` only on `package-lock.json` — `COPY . .` happens last, in the `runtime`
  stage, so an app-code-only change reuses every expensive layer above it.
- **`vendor/` is copied into the `assets` stage** because Tailwind v4's `@source`
  directives in `resources/css/app.css` point into it (the Majid DS kit's own Blade
  views, plus the framework's pagination views) — without it those utility classes
  never generate and Flux/Majid DS components silently lose their styling. This is a
  real dependency, not a leftover: if you ever remove it, `npm run build` will still
  succeed, but the built CSS will be missing classes the DS components need.
- **The asset build reaches the network:** `vite.config.js`'s `bunny()` font provider
  fetches Instrument Sans from `fonts.bunny.net` during `npm run build`. This is *why*
  the image is built in CI rather than on the Dokploy host — a server-side build would
  need that same outbound access, which the panel host shouldn't need to have.
- **Not done in this pass, noted as a future option:** a shared prebuilt base image
  (`ghcr.io/mahdimajidzadeh/php-base:8.4` = FrankenPHP + bash + the union of all four
  repos' PHP extensions), rebuilt weekly by its own small repo/workflow. Every sibling
  repo's Dockerfile would then `FROM` it and skip extension compilation entirely, even
  on a fully cold cache. Worth doing once the pattern has stabilized across all four
  repos, not before.

## One-time Dokploy setup

These are dashboard actions — nothing in this repo configures them, so they're
recorded here for whoever sets up the Application.

1. **Create the Application** (Dokploy → Project → + Application → Docker Image
   provider) pointing at `ghcr.io/mahdimajidzadeh/pokie:latest`. Pin a specific
   `x.y.z` tag instead of `latest` once you want deploys to be a deliberate choice
   rather than "whatever GHCR currently has tagged latest."
2. **Port:** the container listens on **`8080`** (FrankenPHP/Caddy — see
   `docker/Caddyfile`). Point the Application's domain at that port.
3. **Environment tab:** define every variable in the table below. This is the single
   source of truth for runtime config — there is no `.env` anywhere (not in the repo,
   not in the image). Dokploy injects these directly into the container process
   environment; the entrypoint's `config:cache` (etc.) freezes them into Laravel's
   cached config at every boot, so a change to a value here takes effect on the next
   redeploy/restart, not before.
4. **Volume mount:** mount a persistent volume at **`/app/storage`** so uploads and
   logs survive a redeploy (the entrypoint recreates the required subdirectories under
   it if they're missing, but a fresh volume means a fresh, empty `storage/app`).
5. **Stop timeout:** the default is fine today (only `web` runs, and FrankenPHP shuts
   down promptly). If a `worker` program is ever added, raise this to match its
   `stopwaitsecs` in `docker/supervisord.conf` — otherwise Docker SIGKILLs supervisord
   (and whatever job the worker was mid-way through) before it finishes.
6. **Create the database** (Dokploy → Project → + Database → MySQL, or point at an
   existing external server — see "Database" below). Copy its internal hostname into
   `DB_HOST` in step 3.
7. **Webhook:** Dokploy Application → Deployments tab → copy the Webhook URL → add it
   as the `DOKPLOY_WEBHOOK_URL` secret in this repo's GitHub Settings → Secrets and
   variables → Actions. That's the only secret `release.yml` needs beyond the
   automatically-provided `GITHUB_TOKEN`.

### Database

**MySQL/MariaDB never runs inside this repo's container or any compose file** — not in
production, not by accident. It's either a Dokploy panel-managed database (step 6
above) or an existing external MySQL server; either way the app reaches it over
`dokploy-network` via the `DB_*` env vars in the table below. Since this is pTable's
first deployment, there is no existing self-hosted database and therefore no data
migration to perform. (If that ever changes — e.g. a future local dev compose stack
grows real data someone cares about before the panel database exists — the safe order
is: dump the old data → restore into the panel database → point `DB_HOST` at it →
verify the app → *only then* stop the old service, never in the same step as the
dump.)

### Environment variables (Dokploy panel)

Every key here needs a value in the Application's Environment tab. Grouped as they
appear in `.env.example`; blank cells in "Value" are things you choose per-environment.

| Variable | Purpose | Example / notes |
|---|---|---|
| `APP_NAME` | Display name | `pTable` |
| `APP_ENV` | Environment name | `production` (also set as an image default, but the panel value wins) |
| `APP_KEY` | Encryption key | Generate once with `php artisan key:generate --show`, paste the output; never regenerate on an existing deployment or every encrypted session/cookie breaks |
| `APP_DEBUG` | Debug page | `false` — never `true` in production |
| `APP_URL` | Canonical app URL | `https://pokie.example.com` |
| `APP_LOCALE` | Locale | `en` |
| `APP_FALLBACK_LOCALE` | Fallback locale | `en` |
| `APP_FAKER_LOCALE` | Faker locale (dev/test only, harmless in prod) | `en_US` |
| `APP_TIMEZONE` | App timezone | `Asia/Tehran` |
| `APP_MAINTENANCE_DRIVER` | Maintenance mode store | `file` |
| `BCRYPT_ROUNDS` | Password hashing cost | `12` |
| `PTABLE_ADMIN_USERNAME` | Super admin username (§4.4) | Leave **both** admin vars blank to disable `/admin/*` entirely (404) |
| `PTABLE_ADMIN_PASSWORD` | Super admin password | Plaintext in the panel, same trust model as `DB_PASSWORD` — see `config/ptable.php` |
| `LOG_CHANNEL` | Log driver | `stderr` (already an image default) — supervisord's `web` program then carries it to `docker logs` |
| `LOG_STACK` | Stack channels (if `LOG_CHANNEL=stack`) | not needed when `LOG_CHANNEL=stderr` |
| `LOG_DEPRECATIONS_CHANNEL` | Deprecation log channel | `null` |
| `LOG_LEVEL` | Log verbosity | `error` or `warning` in production (the example default `debug` is for local dev) |
| `DB_CONNECTION` | Driver | `mysql` |
| `DB_HOST` | Database host | The panel-managed database's internal `dokploy-network` hostname, or an external server's address |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `ptable` |
| `DB_USERNAME` | Database user | — |
| `DB_PASSWORD` | Database password | — |
| `SESSION_DRIVER` | Session store | `database` |
| `SESSION_LIFETIME` | Session lifetime (minutes) | `10080` (7 days — intentionally long; the super admin's own 2h inactivity timeout, `config/ptable.php`, is enforced independently on top of this) |
| `SESSION_ENCRYPT` | Encrypt session payload | `false` |
| `SESSION_PATH` | Cookie path | `/` |
| `SESSION_DOMAIN` | Cookie domain | `null` unless serving multiple subdomains |
| `BROADCAST_CONNECTION` | Broadcast driver | `log` (broadcasting isn't used) |
| `FILESYSTEM_DISK` | Default filesystem disk | `local` |
| `QUEUE_CONNECTION` | Queue driver | `database` — present for parity with `.env.example`; no jobs are ever dispatched, see "Process model" above |
| `CACHE_STORE` | Cache driver | `database` |
| `MAIL_MAILER` | Mail driver | `log` unless the app starts sending real mail |
| `MAIL_SCHEME` / `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP details | only matter once `MAIL_MAILER` is a real transport |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | From header | — |
| `VITE_APP_NAME` | Exposed to the client build | Not actually read anywhere in `resources/js` today; harmless to keep for parity with `.env.example` |

Not included above: `MEMCACHED_HOST`, `REDIS_*`, `AWS_*` — these are Laravel's stock
skeleton defaults for drivers this app doesn't use (no Redis, no S3). Leave them unset
unless that changes.

### Retiring old resources

Nothing to retire yet — this is the first deployment. If a future refactor ever
introduces a second Dokploy Application (e.g. a dedicated worker, mirroring what fila
briefly had before this pattern), this section is where its removal — the Application
itself and its separate webhook secret — would be documented.

## Rollback

Point the Dokploy Application at a specific previous tag (e.g. `0.1.0` instead of
`latest`) and redeploy from the panel. Since every release is pushed under its own
immutable semver tag (see `release.yml`'s `docker/metadata-action` step), any past
release is always pullable by tag.

## Local development

pTable's local dev does **not** use Docker or Compose — the app runs directly via
[Laravel Herd](https://herd.laravel.com) (PHP 8.4) against a native MySQL install, the
same way it always has (`composer dev`, see `composer.json`). Docker is used only to
build the production image described above. There is no `compose.yaml` in this repo —
production reads no compose file at all (Dokploy pulls the built image directly), and
there was never a self-hosted local compose stack to convert or delete.

If local Docker-based development is ever wanted, add a `compose.local.yaml` (not
`compose.yaml` — that name is reserved to mean "the production stack," which pTable
does not have) with a MySQL service and the app's own `.env` (gitignored, never
committed) — that combination, a database container plus a local `.env` file, is the
only place either is allowed in this repo.

## Verifying a deployment

```bash
curl -sI https://pokie.example.com/up          # expect 200, no DB query
docker exec <container> supervisorctl status   # expect: web  RUNNING
docker logs <container> --tail 50              # web/Caddy access + app log lines
```
