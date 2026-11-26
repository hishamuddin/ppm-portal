# PPM Portal development

- Laravel 13 / PHP 8.4, Blade, Vite, MYDS styles and Tiptap.
- Keep staff authorisation on the server. Authors never publish or change other authors' content; disabled accounts have no staff access.
- Sanitize HTML on save and render. Do not make upload storage directly web-accessible.
- Public queries must use Content::published() and locale restrictions.
- Malaysia is the editorial timezone. Do not seed credentials or official-looking fabricated publications.
- Keep secrets, databases, uploads, vendor, node_modules and compiled assets out of git.
- Run php artisan test, vendor/bin/pint --test and npm run build for changes. Browser tests use a separate testing database via tests/browser/setup.php; never run that fixture on production data.
- See README.md and docs/DEPLOYMENT.md for setup and scope.
