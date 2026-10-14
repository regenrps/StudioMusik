# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Studio Musik ITERA: a Laravel 13 (PHP 8.4+, PHPUnit 13) + MySQL 8.0+ web app for booking a campus music studio and ordering music services (jingle, aransemen, minus one). UI is server-rendered Blade components + Tailwind v4 + Alpine.js via Vite. All UI text, domain names (models, routes, variables), and commit messages are in **Indonesian**; keep that convention.

## Commands

```bash
composer install && npm install
npm run dev            # Vite dev server (HMR)
npm run build          # production assets → public/build (must rebuild after adding Tailwind classes)
php artisan serve

# Tests need a MySQL database named *_test (phpunit.xml uses studio_musik_test).
php artisan config:clear            # REQUIRED first: cached config makes PHPUnit ignore phpunit.xml
php artisan test                    # full suite (~70 tests)
php artisan test --filter=KetersediaanTest
php artisan test --filter=test_slot_yang_sama_tidak_dapat_dipesan_dua_kali

php artisan migrate
php artisan db:seed                       # first admin only (ADMIN_* env), idempotent
php artisan db:seed --class=DemoSeeder    # demo data; accounts *@demo.test / Studio123 (refuses in production)
php artisan studio:sinkron-status         # expire/no-show/reminders (scheduled every 5 min)
php artisan studio:bersihkan-jaminan      # delete KTM/KTP photos past retention (daily)

docker compose up -d --build              # full stack: app :8088, mailpit :8025, mysql :3307
docker compose exec app php artisan test
```

`tests/CreatesApplication.php` throws if the DB name doesn't end in `_test`. This is deliberate: `RefreshDatabase` would otherwise wipe the real DB when config is cached.

## Architecture

**Legacy table names, new model names.** Models map to the original thesis schema (see the ERD), so table and primary-key names differ from class names. Always check `$table` and `$primaryKey`:
`Peminjaman`→`pesanan_jadwal_studio` (pk `id_pesanan_jadwal_studio`, route key `kode`), `Ruangan`→`data_ruangan` (route key `slug`), `JasaMusik`→`master_jasa_musik` (`slug`), `FieldJasaMusik`→`form_jasa_musik`, `PesananJasa`→`pesanan_jasa_musik` (`kode`), `User` pk `id_user`. The `2026_10_12_*` migrations upgraded the legacy schema in place, with backfill and working `down()`.

**Status is an enum, transitions live in services.** `StatusPeminjaman` / `StatusPesananJasa` (app/Enums) hold labels, badge tones, icons, and which statuses block a slot. Never update `status` directly in controllers. Go through `PeminjamanService` / `PesananJasaService`. Each transition there does three things together: validates the business rule (throws `AturanBisnisException`, which renders as a flash error / 422), writes `LogAktivitas::catat()`, and sends notifications via `Notifier`.

**`KetersediaanService` is the single source of scheduling truth.** It covers operating days and hours, duration and step rules, closures (`JadwalTutup`), and overlap checks. Intervals are half-open `[mulai, selesai)`, so back-to-back bookings are allowed. Booking creation re-checks the slot inside a DB transaction with `lockForUpdate()` on the room and the user rows. The wizard's client-side slot computation (`resources/js/modules/wizard-peminjaman.js`) is UX only. Public JSON (`/jadwal/data`, `/ketersediaan/*`) must stay anonymized; a test asserts no usernames leak.

**Runtime settings.** `PengaturanService` merges `config('studio.default')` with rows in the `pengaturan` table (admin-editable, cached). Read values via the `pengaturan('key')` helper, not `config()`, for anything listed under `studio.default`.

**Roles & authorization.** `Role` enum: `admin`, `k3l` (monitoring + incident reports), `user` (borrower).
- Route groups use the `role:` middleware (`EnsureRole`): `/akun/*` is borrower-only and `/admin/*` is admin + K3L, with an inner `role:admin` group.
- Record ownership is checked with policies (`PeminjamanPolicy`, `PesananJasaPolicy`) plus the gates `admin`, `staf`, `peminjam`.

**Files.** Two disks (config/filesystems.php):
- `public`: its root is `public/storage` itself, not symlinked, so the app works on shared hosting.
- `private`: `storage/app/private`. Holds KTM/KTP, condition photos, and music-service attachments and results.

Always store via `Berkas::simpan()`. It generates random names and only allows whitelisted extensions (`EKSTENSI_AMAN`), which prevents polyglot `.php` uploads. Serve private files only through `BerkasController`, which authorizes via policy and logs to the `keamanan` channel. Build public file URLs with `asset('storage/'.$path)`, not `Storage::url()`, because `APP_URL` is often wrong in local/tunnel setups.

**Notifications.** Every notification extends `NotifikasiDasar`, which defines one source for both the database (in-app bell) and mail channels, and is `ShouldQueue` + `afterCommit`. Send through `Notifier::kirim()` / `Notifier::staf()`. These swallow and report transport errors so SMTP failures never roll back user actions. Notifications that depend on status snapshot the status in their constructor.

**Logging.**
- `LogAktivitas`: DB audit trail shown in admin "Log Aktivitas", pruned via `model:prune`.
- Log channels (config/logging.php):
  - `stack` (`LOG_STACK`, default `daily`);
  - `keamanan`: JSON; auth events via `App\Listeners\CatatKejadianKeamanan`, 403/419/429 via `App\Exceptions\Handler`, private-file access;
  - `akses`: JSON; one line per request from the `CatatAkses` middleware, which also sets `X-Request-Id` and shares `request_id` with all logs.

**Security middleware** (`app/Http/Kernel.php`):
- `SecurityHeaders`: nonce-based CSP via `Vite::useCspNonce()`, `no-store` on authenticated pages. Any new inline `<script>` needs `nonce="{{ Vite::cspNonce() }}"`. `'unsafe-eval'` stays because standard Alpine needs it.
- `TrustHosts` and `TrustProxies` (`TRUSTED_PROXIES` env).
- `EnsureAccountActive`.
- `SinkronStatusOtomatis`: poor-man's cron, disabled via `STUDIO_SINKRON_VIA_REQUEST=false` when a real scheduler runs.

Redirect targets built from user input must go through the `tujuan_aman()` helper. User/admin rich text must render through `konten()`, never raw `{!! !!}`.

## Frontend gotchas

- Design system lives in `resources/views/components` (`x-button`, `x-badge`, `x-status`, `x-input`, `x-file-input`, `x-modal`, `x-card`, layouts under `components/layouts`). Open modals with `$dispatch('buka-modal', 'name')`.
- Tailwind only sees **literal** class names. Never build classes like `'bg-'.$tone.'-500'`; map tones to full class strings in a PHP array (see `x-badge`).
- Responsive grids need an explicit `grid-cols-1` base, otherwise `truncate` content widens the implicit column and causes horizontal overflow on phones.
- `class="hidden sm:inline-flex"` on `x-button` / `x-badge` works only because those components strip their own `inline-flex` when `hidden` is passed. Do the same in new components with a base display class.
- `@class` doesn't work on component tags; use `:class="..."`.
- Alpine data components are registered in `resources/js/app.js` (`wizardPeminjaman`, `jadwalMingguan`, `unggahBerkas`, `notifikasi`, `fieldBuilder`, `grafikDashboard`, `hitungMundur`). Chart.js is lazy-loaded via dynamic import.
- Global form behaviour (`resources/js/modules/form.js`): double-submit prevention and `data-konfirmasi="..."` confirm prompts.

## Environment notes

- Windows + Git Bash: pass `MSYS_NO_PATHCONV=1` when giving URL paths like `/jadwal` to CLI tools, or they get rewritten to Windows paths.
- Upgraded 10 → 13 keeping the Laravel 10-style structure (`app/Http/Kernel.php`, `app/Console/Kernel.php`, `App\Exceptions\Handler`, `RouteServiceProvider`, full `config/*`), as the official upgrade guide recommends. Register middleware/schedules/exception handling there, not in `bootstrap/app.php`. Env names follow the old config files (`CACHE_DRIVER`, not `CACHE_STORE`).
- Laravel 13 hardening is on: `cache.serializable_classes = false` and `session.serialization = json`. Cache and session values must be arrays/scalars. Objects (Carbon, models, collections) come back broken from a file/DB cache hit, and tests won't catch it because they use the array store.
- The CSRF middleware is `App\Http\Middleware\PreventRequestForgery` (Laravel 13 rename of `VerifyCsrfToken`). It also checks `Sec-Fetch-Site`.
- Carbon 3: `diffIn*()` returns signed floats. Pass the earlier date as the receiver, or wrap in `abs()`/`(int)`.
- The repo lives in OneDrive. `composer install` can fail with "Could not delete … antivirus or the Windows Search Indexer" because of file locks. Retry, or install into a temp dir outside OneDrive and move `vendor/` in. Laragon's PHP 8.5 is at `C:\laragon\bin\php\php-8.5.11-Win32-vs17-x64\php.exe`. Composer < 2.8 breaks on PHP 8.5.
- Local `.env` may point MAIL to real Gmail SMTP. Use `MAIL_MAILER=log` (or Docker's Mailpit) when testing flows that notify.
