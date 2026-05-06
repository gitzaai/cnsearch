# Security

Meilisearch API keys are sensitive and must not be committed to the repository,
published in frontend assets, pasted into public issue trackers, or included in
screenshots.

If a Meilisearch key has been exposed, rotate it immediately on the Meilisearch
server, update the Flarum setting with `php flarum cnsearch:configure`, clear
the Flarum cache, and reindex.

Report security issues privately to the project maintainer instead of opening a
public issue.
