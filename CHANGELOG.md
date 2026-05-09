# Changelog

## v0.1.18 - 2026-05-09

- Fix the Flarum 2 admin settings page registration so the extension settings are visible in the backend.
- Remove the invalid admin `generalItems` registration and keep the dedicated extension settings page.
- Improve Meilisearch default handling and visible-result pagination/counting during search.

## v0.1.17 - 2026-05-09

- Add a dedicated admin settings page for Meilisearch host, API key, and index management.
- Add admin-side connection test and reindex controls for quicker setup verification.
- Update the installation guide to document installing the `dev-main` branch from the repository.

## v0.1.16 - 2026-05-06

- Add Flarum 2 native discussion full-text search integration backed by Meilisearch.
- Index visible discussion titles and all visible comment posts into one Meilisearch document per discussion.
- Add CJK n-gram search fields and query expansion for short Chinese keywords.
- Lower the forum search preview trigger length so one- and two-character Chinese searches show immediate results.
- Add admin status, connection test, reindex controls, and CLI commands for configure, status, search, and reindex.
- Restrict Meilisearch displayed attributes to discussion identifiers and remove unused admin code that could expose the stored API key.
- Fix admin button accessibility labels.
- Support `meilisearch/meilisearch-php` 1.x and 2.x beta clients.
