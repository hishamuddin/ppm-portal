# Deployment and operations

## Requirements

PHP 8.4+ and the extensions checked by Composer; Composer 2; Node.js 24 for building assets; SQLite for development or MySQL 8/PostgreSQL for deployment. The locked dependencies were exercised with PHP 8.4 and SQLite. Test your production database in staging before launch.

## Fresh installation

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan migrate --force
php artisan cms:admin admin@example.gov.my --name="Portal Administrator"
php artisan optimize
```

Configure `.env` BEFORE migrations and caching: `APP_ENV=production`, `APP_DEBUG=false`, the real HTTPS `APP_URL`, your database credentials, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, and `PORTAL_DEMO=false` only after approved content/branding are installed. Keep `APP_KEY` stable across deployments. Do not commit secrets. The initial administrator command asks for a hidden password; there is no default login. Use `php artisan cms:reset-password email@example.gov.my` from a trusted server console for recovery. Staff can change their password under My password.

Point Nginx/Apache document root to **public/**, never the repository root. Only `storage/` and `bootstrap/cache/` need application write access. Do not run `storage:link`: CMS media deliberately lives in `storage/app/private` and is served by an authorised controller. Images are displayed only when public (or to authorised staff); PDFs are downloads. Uploads need enough disk space. Configure PHP `upload_max_filesize=10M`, `post_max_size=12M`, and the matching reverse-proxy body limit. Never allow PHP execution in upload directories.

Example Nginx application locations (inside an existing HTTPS server block):

```nginx
root /var/www/ppm-portal/public;
index index.php;
client_max_body_size 12m;
location / { try_files $uri $uri/ /index.php?$query_string; }
location = /index.php {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
}
location ~ \.php$ { return 404; }
location ~ /\. { deny all; }
```

Use your actual PHP-FPM socket. Set HTTPS, HSTS and trusted proxy handling for your infrastructure; do not trust arbitrary forwarded headers. Access to `/admin` can additionally be restricted to VPN or staff IPs. Health check: `/up`.

## Content operations

1. Set portal titles, homepage copy, contact details and approved logo in Portal settings.
2. Create categories, then pages/news/speeches/statements/galleries in BM and English. Languages are separate records; the language switch leads to the other language's homepage, not an automatic translation.
3. Upload assets privately; an editor or administrator explicitly marks them public. Public files are reachable independently of their linked article. Unpublishing an article does not automatically unpublish its media. Remove media visibility separately if needed. Previously downloaded public files cannot be recalled.
4. Authors submit drafts for review. Editors/administrators can publish immediately or enter a future Malaysia time. Visibility is evaluated on every public query, so no scheduled job is required.
5. Set navigation paths in display order, separately for BM and English. Create policy pages and add links as required.
6. Revisions are snapshots after each save; restoration creates a draft. Trashed content can be recovered by editors. Slugs stay reserved while content is in trash.

## Launch review

This is a working initial CMS, not a WordPress plugin/theme runtime. Before public launch, complete content approval, real agency branding, accessibility (keyboard, screen reader, contrast, zoom), MYDS/SPLaSK assessment, security review, load testing, backup/restore testing and infrastructure configuration. It uses MYDS styles and Inter/Poppins fonts, but is not certified compliant. Demo content is visibly marked and is not copied from live PMO publications. DemoSeeder refuses production execution, but existing demo rows must still be removed before switching environments.

Not included: government SSO/MFA integration, email password reset, WordPress import, plugin marketplace, visual page builder, automatic translation, nested navigation, document malware scanning or full-text search indexing. Add an upload scanning/quarantine service before accepting files from untrusted contributors. Current uploads are staff-only.

## Maintenance

Back up the database, `storage/app/private`, and the application key in encrypted secret storage. Validate restoration together so media references match files. Never run `migrate:fresh` in production. For updates: back up, install locked dependencies, build, run additive migrations, refresh caches, restart PHP-FPM/queue workers as appropriate and smoke-test. Run dependency audits regularly. Audit logs cover core CMS mutations, not a complete security/SIEM event stream.

Database sessions and cache are the default. Session timeout defaults to 120 minutes. Password changes invalidate other staff sessions on their next authenticated request. No external telemetry, font CDN, third-party content iframe or default user password is required by this application.
