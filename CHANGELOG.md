# Changelog

## v0.1.21 - 2026-05-16

- Make bootstrap class includes tolerate partially missing uploaded files so extension enablement is not blocked by optional API controller paths.

## v0.1.20 - 2026-05-16

- Require the bootstrap class files directly so missing or unreadable files fail during extension boot instead of surfacing later as missing route controllers.

## v0.1.19 - 2026-05-16

- Replace recursive source scanning with an explicit readable-file bootstrap list to avoid boot errors on hosts that deny directory listing.

## v0.1.18 - 2026-05-16

- Load all extension PHP classes from `extend.php` so manual ZIP installs do not 500 before Composer autoload is refreshed.

## v0.1.17 - 2026-05-09

- Add Flarum 2 API controllers for search, status, connection test, and reindex routes.
- Export the Flarum 2 admin extender correctly so Meilisearch settings appear in the admin panel.
- Avoid defaulting an empty Meilisearch host to localhost and treat an empty API key as unset.
- Return actionable JSON:API error details from admin connection and reindex actions.
- Add a direct controller include fallback in `extend.php` for manual upload and autoload refresh edge cases.

## v0.1.16 - 2026-05-06

- Add Flarum 2 native discussion full-text search integration backed by Meilisearch.
- Index visible discussion titles and all visible comment posts into one Meilisearch document per discussion.
- Add CJK n-gram search fields and query expansion for short Chinese keywords.
- Lower the forum search preview trigger length so one- and two-character Chinese searches show immediate results.
- Add admin status, connection test, reindex controls, and CLI commands for configure, status, search, and reindex.
- Restrict Meilisearch displayed attributes to discussion identifiers and remove unused admin code that could expose the stored API key.
- Fix admin button accessibility labels.
- Support `meilisearch/meilisearch-php` 1.x and 2.x beta clients.
