# PPM Portal

A Laravel CMS and bilingual Malaysian government portal, using the Malaysia Government Design System (MYDS) style library. The information structure takes inspiration from [the PMO portal](https://www.pmo.gov.my/); this repository is a new implementation, not a copy or replacement of the live site.

## Included

- Responsive BM/English public homepage, search/filtering, publication pages, galleries and contact footer.
- Staff dashboard; pages, news, speeches, statements and galleries; rich-text editor; categories and tags; featured image and gallery images; SEO metadata.
- Draft → review → publication, future publication times in Asia/Kuala_Lumpur, private preview, revision snapshots and restoration, trash/recovery, stale-edit conflict detection.
- Private-by-default media library (JPEG/PNG/WebP/PDF, 10 MB), explicit public visibility and accessible descriptions.
- Ordered navigation per language, homepage/contact/logo settings, administrator/editor/author accounts, password changes and activity log.
- Session authentication, CSRF protection, login throttling, server-side authorisation, HTML sanitisation, upload validation and security headers.
- Self-hosted Inter/Poppins fonts and bundled assets; no runtime CDN dependency.

## Local development

Requirements: PHP 8.4+, Composer 2 and Node.js 24. SQLite is the default; a MySQL/PostgreSQL deployment is configurable through Laravel's standard database settings.

```bash
git clone https://github.com/hishamuddin/ppm-portal.git
cd ppm-portal
# While the initial implementation PR is open:
git switch feat/laravel-cms
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan cms:admin your-email@example.gov.my --name="Your Name"
# Optional local demonstration content; never seed this in production:
php artisan db:seed --class=DemoSeeder
php artisan serve
```

Open `http://localhost:8000/ms` (BM), `/en` (English), or `/login` (staff). `cms:admin` prompts for a hidden password; there is no seeded working login. For frontend development, run `npm run dev` in another terminal. Use `php artisan cms:reset-password email@example.gov.my` for trusted-console password recovery.

## Verification

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan view:cache
php artisan route:cache
composer audit
npm audit --omit=dev
```

Feature tests exercise role boundaries, private/draft/future content, sanitisation, revisions, conflicting edits, media privacy, executable upload rejection, unsafe links, login, trash and localisation. GitHub Actions repeats the checks on pushes and pull requests.

Browser smoke tests are also included. They cover the bilingual public portal at mobile width and an actual sign-in → rich-text draft → preview → publish → sign-out workflow. CI prepares an isolated testing database; do not run `tests/browser/setup.php` against your real database.

## Design and scope

Reference guidance: [MYDS](https://design.digital.gov.my/en), [Laravel integration](https://design.digital.gov.my/en/docs/develop/laravel), and [style configuration](https://design.digital.gov.my/en/docs/develop/style-configuration). This implementation uses Laravel Blade with `@govtechmy/myds-style`, a 12/8/4 responsive layout approach, accessible form labels, visible focus, skip navigation and reduced-motion support. The React component package is not required by this Blade implementation. The building illustration is a repository-native SVG; replace the PPM monogram with an approved logo using Portal settings.

This is a first working CMS release, not full WordPress compatibility or a certified MYDS/SPLaSK implementation. See [deployment and operations](docs/DEPLOYMENT.md) for setup, workflow, limits and launch requirements. The demonstration banner is controlled by `PORTAL_DEMO`; leave it enabled until authorised launch. Real PMO articles, personal data, government credentials and production keys are not bundled.
