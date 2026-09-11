# NEUST Wayfinding — Audit & What Changed (2026-09-09)

## What I actually did in this pass

1. **Traced every `include`/`require`** across the codebase to find out
   which files are live. Confirmed `login.php`, `dashboard.php`, `map.php`,
   `crud/*`, `functions/*`, `libraries/*` are the real app. `alternative.php`,
   `alternative2.php`, `alt2.php`, `trial.php`/`.js`/`.css`, `lol.php`, and
   `tmp_extracted.js` are **not referenced anywhere** — moved to
   `/deprecated` instead of deleted (see `deprecated/README.md`).
2. **Added `db/schema_additions.sql`** — the missing wayfinding data model
   (`buildings`, `floors`, `rooms`, `services`, `service_requirements`,
   `service_steps`, `navigation_history`, `favorites`, `announcements`),
   built additively with `CREATE TABLE IF NOT EXISTS` and real foreign keys
   checked against your actual `offices`/`users` column types. Nothing in
   the existing schema is touched. Not yet wired into any PHP — see "Next"
   below.
3. **Dashboard**: added a "Where do you need to go?" search hero and six
   quick-action cards (Find Office / Room / Building, Get a Document, View
   Procedures, Campus Map) to the Home tab, styled with the brand
   variables (`--primary-color`, `--gold`, `--orange`) that were already
   defined in the page — nothing new invented, no clashing palette. The
   cards currently scroll to the existing map section at the bottom of the
   page (`#map-container`); they don't yet run real search queries (that
   needs the schema wired up first — see Next).
4. **Login page copy**: dropped "Portal" language, changed the tagline to
   "Student Navigation & Wayfinding System" / "Find your way. Know what to
   do." — visual design was untouched (it already used the right blue/gold
   palette).

## What I deliberately did NOT touch

- **`map.php`, `map.js`, `mapf.js`, `poi-search.js`** — this is the actual
  campus-navigation engine (2,500+ lines of JS handling floor rendering,
  routing, POI search) and it's already using the correct brand colors.
  Rewriting 2,500 lines of working navigation JS blind, without a running
  environment to test against, risks breaking it for a cosmetic win. This
  needs to happen with you testing each change against a live DB, not in
  one unattended pass.
- **CRUD/auth files** — didn't touch `functions/do_login.php`,
  `functions/login_auth.php`, or the `crud/` folder. `db.php` already uses
  PDO with prepared statements and `ERRMODE_EXCEPTION`, which is the right
  foundation; I didn't find an obvious SQL-injection point in the files I
  read, but I did not read every query in `map.php`/`alternative*.php`
  line by line.

## Things worth your attention (not fixed)

- **`db/capstone.sql` contains real bcrypt password hashes for what look
  like seeded/demo accounts** (emails like `CictKevin@gmail.com`,
  `RegistrarElla@gmail.com`). If this file ever goes into a public repo,
  those hashes go with it — rotate/regenerate before pushing publicly,
  and consider `.gitignore`-ing SQL dumps that contain real data.
- **`users.office_id` is `0` for several rows**, but `offices` starts at
  `office_id = 1` — `0` isn't a valid office. Decide if that should be
  `NULL`.
- Floor-plan asset filenames have typos worth cleaning up before you wire
  them into `plan_svg_path`: `1s_floor.svg` (missing "t"), `perst Plor.pdf`
  (should probably be "1st Floor").
- `functions/pusher_auth.php` and the Pusher key are used client-side in
  `dashboard.php` (`new Pusher('ee626886154497d0f929', ...)`) — that's
  normal for Pusher (app keys are public by design), but double check
  `pusher_auth.php` actually validates the channel subscription
  server-side rather than trusting the client.

## Recommended next steps, in order

1. **Import `db/schema_additions.sql`** against a copy of your DB, then
   seed a handful of real services (Registrar/TOR, Good Moral, etc.) and
   rooms so there's real data to build against.
2. **Wire the dashboard search bar to a real query** — a single endpoint
   that searches `services.name`, `services.short_code`, and `rooms.name`
   and returns destination + requirements + steps.
3. **Only then** touch `map.php`'s routing/floor logic, since it'll have
   real `rooms`/`floors` data to point at instead of whatever's hardcoded
   there now.
4. Delete `/deprecated` once you've confirmed nothing in it is needed.

I kept this pass scoped to what I could verify by reading the code (no
live DB/PHP runtime here), rather than rewriting large working files
blind. Happy to go deeper on any one of the "Next steps" above.
