# PMO sample content and Windows update guide

Four Malay sample entries are provided by `PmoSampleSeeder`: two speech summaries (AI Temu AI, 25 November 2026; Khatam Sama’ Hadis, 23 November 2026), one audience photo gallery (18 August 2026), and one illustrated MKI news entry (published 14 January 2026; event 13 January).

These are clearly labelled summaries, not full official transcripts or official translations. Each entry links to its original PMO publication. Exact source and image URLs, dates and photo credits are in [samples.json](../database/seeders/fixtures/pmo/samples.json), checked on 25 November 2026. Date-only publications use midnight Asia/Kuala_Lumpur; this does not assert an original posting time. The two JPEG fixtures are from PMO; rights remain with their original owners and are not covered by the application's MIT licence. The MKI photo is credited to Izzuddin Abd Radzak / Pejabat Perdana Menteri.

## Existing Laragon installation

Use the Laragon terminal (CMD). Adjust the project path if needed. PHP 8.4 or later is required; check `php -v` and `where php` first.

```bat
cd C:\laragon\www\ppm-portal
git status
```

If there are local changes, commit them or save them with `git stash push -u -m "Before PMO samples update"` before switching branches. Keep your existing `.env` and database. Then:

```bat
git fetch origin
git switch feat/laravel-cms
git pull --ff-only origin feat/laravel-cms
composer install
npm ci
npm run build
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=PmoSampleSeeder
php artisan serve
```

If the local branch does not exist and automatic tracking is disabled, use `git switch --track origin/feat/laravel-cms`. The implementation is on this feature branch while PR #1 remains open; pulling `main` alone will not retrieve it. If Git reports divergent history, stop and reconcile the commits instead of resetting or force-pulling.

Visit http://127.0.0.1:8000/ms, `/ms/search?type=speech`, and `/ms/search?type=gallery`. For a Laragon virtual host, keep its document root pointing to `public`. Restore stashed work with `git stash pop` after the update and resolve any conflicts.

No `migrate:fresh`, database deletion, new application key, or storage symlink is needed for an existing installation. These images are copied into private storage and served through the existing media controller.

## Seeding behaviour

- Explicit opt-in: normal `db:seed` adds no sample content. `DemoSeeder` remains available for generic bilingual fixtures; `PmoSampleSeeder` adds only the four PMO entries.
- Run with a local/development environment. Production is rejected before any database or file changes.
- Repeat runs skip existing sample slugs, including trashed ones, and preserve editorial changes and media visibility. Existing generic demo entries are not removed.
- The sample author is inactive and has a random password. No working login is supplied.
- Images are bundled fixtures, so seeding and subsequent display require no PMO network access.
- Keep `PORTAL_DEMO=true` during review. English samples are not fabricated; these entries appear only in BM.
