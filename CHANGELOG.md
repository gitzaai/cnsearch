# Changelog

## v0.1.16 - 2026-05-06

- Add Flarum 2 native discussion full-text search integration backed by Meilisearch.
- Index visible discussion titles and all visible comment posts into one Meilisearch document per discussion.
- Add CJK n-gram search fields and query expansion for short Chinese keywords.
- Lower the forum search preview trigger length so one- and two-character Chinese searches show immediate results.
- Add admin status, connection test, reindex controls, and CLI commands for configure, status, search, and reindex.
- Restrict Meilisearch displayed attributes to discussion identifiers and remove unused admin code that could expose the stored API key.
- Fix admin button accessibility labels.
- Support `meilisearch/meilisearch-php` 1.x and 2.x beta clients.
