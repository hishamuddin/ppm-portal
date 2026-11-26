# Instagram activity highlights

The BM and English homepages show eight curated @malaysiapmo cards with local thumbnails, reel/carousel markers, a decorative building illustration and a profile follow button. Desktop uses four columns; narrow mobile uses two. Cards open the original post/reel in a new tab; videos play on Instagram.

The source profile is https://www.instagram.com/malaysiapmo/. The eight post URLs and corresponding thumbnails were retrieved from the account's feed as displayed on https://www.pmo.gov.my/ms/ on 26 November 2026. Instagram's profile could not be fetched directly in the development environment. Titles in config/instagram.php are brief editorial labels, not verbatim captions or official translations. Thumbnails remain credited to PMO / @malaysiapmo and are not covered by the application's MIT licence.

This is a curated snapshot, not an automatically refreshing feed. There are no Instagram scripts, third-party iframes, API tokens or network requests on page load. Existing Content Security Policy remains unchanged. Instagram may require visitors to sign in, and posts may subsequently become unavailable.

## Update your local installation

From Laragon Terminal, after committing or stashing any local changes:

```bat
cd C:\laragon\www\ppm-portal
git fetch origin
git switch feat/laravel-cms
git pull --ff-only origin feat/laravel-cms
npm ci
npm run build
php artisan optimize:clear
php artisan serve
```

Visit /ms or /en. This feature requires no database migration or seeder. Existing .env files default to showing the section; set `PORTAL_INSTAGRAM_ENABLED=false` and clear cached configuration to hide it.

## Change the selected posts

Edit `config/instagram.php`: replace each post's canonical Instagram URL, `title_ms`, `title_en`, `type` (`reel`, `carousel`, `image`) and local image path. Keep at most eight cards. Add the corresponding approved JPEG thumbnail to `public/images/instagram`; do not use expiring Instagram CDN URLs. Keep the original image's content and match it to the linked post. Publish the code changes and run `php artisan optimize:clear` (or rebuild your configuration cache during deployment). Content editors' uploaded media remains private under the existing media controller; these images are explicitly public static assets.

For automatic updates, a separate authenticated Instagram feed integration would be needed. This implementation does not claim to fetch the latest posts.
