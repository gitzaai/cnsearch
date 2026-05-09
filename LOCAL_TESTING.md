# Local testing

Use this workflow when testing the extension from this checkout instead of installing it from GitHub releases.

## 1. Build the admin/forum assets

Run this inside the extension repository:

```bash
cd E:/github/cnsearch/js
npm install
npm run build
```

For active frontend work, use the watcher instead:

```bash
cd E:/github/cnsearch/js
npm run dev
```

## 2. Install the local extension into a Flarum app

Run these commands from your local Flarum root, the directory that contains `flarum` and `composer.json`:

```bash
composer config repositories.cnsearch path E:/github/cnsearch
composer require gitzaai/cnsearch:*@dev -W --no-audit
php flarum extension:enable gitzaai-cnsearch
php flarum assets:publish
php flarum cache:clear
```

If Composer keeps an older package source, remove and require it again:

```bash
composer remove gitzaai/cnsearch --no-audit
composer clear-cache
composer require gitzaai/cnsearch:*@dev -W --no-audit
php flarum assets:publish
php flarum cache:clear
```

## 3. Configure Meilisearch

Use the admin settings panel, or run:

```bash
php flarum cnsearch:configure http://127.0.0.1:7700 --index=flarum_discussions
php flarum cache:clear
```

If your Meilisearch instance requires a key:

```bash
php flarum cnsearch:configure http://127.0.0.1:7700 --key=YOUR_MEILISEARCH_API_KEY --index=flarum_discussions
php flarum cache:clear
```

## 4. Verify the extension

```bash
php flarum cnsearch:status
php flarum cnsearch:reindex
php flarum cnsearch:search test-keyword
```

Open the Flarum admin panel, enable **CN Search**, then check the extension card settings. The Meilisearch host, API key, index, status, test connection, and reindex controls should be visible there.

## 5. After each local code change

For PHP-only changes:

```bash
composer dump-autoload -o
php flarum cache:clear
```

For frontend changes:

```bash
cd E:/github/cnsearch/js
npm run build
cd YOUR_FLARUM_ROOT
php flarum assets:publish
php flarum cache:clear
```
