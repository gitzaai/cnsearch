<?php

namespace Gitzaai\Cnsearch\Search;

use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Meilisearch\Client as MeilisearchClient;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Exceptions\ApiException;

class DiscussionIndexer
{
    protected SettingsRepositoryInterface $settings;
    protected MeilisearchClient $client;
    protected string $indexName;
    protected ?Indexes $index = null;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->client = new MeilisearchClient($this->getHost(), $this->getApiKey());
        $this->indexName = $this->getIndexName();
    }

    protected function getHost(): string
    {
        $host = trim((string) $this->settings->get('cnsearch.meili.host', 'http://127.0.0.1:7700'));

        return $host !== '' ? $host : 'http://127.0.0.1:7700';
    }

    protected function getApiKey(): ?string
    {
        $key = (string) $this->settings->get('cnsearch.meili.key');

        return $key !== '' ? $key : null;
    }

    protected function getIndexName(): string
    {
        $indexName = trim((string) $this->settings->get('cnsearch.meili.index', 'flarum_discussions'));

        return $indexName !== '' ? $indexName : 'flarum_discussions';
    }

    protected function index(): Indexes
    {
        if ($this->index === null) {
            $this->index = $this->client->index($this->indexName);
        }

        return $this->index;
    }

    protected function ensureIndexExists(): void
    {
        try {
            $this->fetchIndexInfo();

            return;
        } catch (\Throwable $e) {
            if (! $this->isIndexNotFoundException($e)) {
                throw $e;
            }
        }

        $task = $this->client->createIndex($this->indexName, ['primaryKey' => 'id']);
        $this->waitForTask($task);
        $this->index = $this->client->index($this->indexName);
    }

    protected function fetchIndexInfo(): void
    {
        if (method_exists($this->index(), 'fetchInfo')) {
            $this->index()->fetchInfo();

            return;
        }

        if (method_exists($this->index(), 'show')) {
            $this->index()->show();

            return;
        }

        $this->client->getIndex($this->indexName);
    }

    protected function isIndexNotFoundException(\Throwable $e): bool
    {
        if ($e instanceof ApiException && property_exists($e, 'errorCode') && $e->errorCode === 'index_not_found') {
            return true;
        }

        $message = $e->getMessage();

        return str_contains($message, 'index_not_found')
            || str_contains($message, 'Index `' . $this->indexName . '` not found')
            || str_contains($message, 'Index ' . $this->indexName . ' not found');
    }

    protected function waitForTask(mixed $task): void
    {
        if (is_object($task) && method_exists($task, 'wait')) {
            $task->wait(30000, 100);

            return;
        }

        $taskUid = null;

        if (is_array($task)) {
            $taskUid = $task['taskUid'] ?? $task['uid'] ?? null;
        } elseif (is_object($task) && method_exists($task, 'getTaskUid')) {
            $taskUid = $task->getTaskUid();
        }

        if ($taskUid === null) {
            return;
        }

        if (method_exists($this->client, 'waitForTask')) {
            $this->client->waitForTask((int) $taskUid);

            return;
        }

        if (method_exists($this->client, 'getTask')) {
            $this->waitForTask($this->client->getTask((int) $taskUid));
        }
    }

    protected function updateIndexSettings(): void
    {
        $task = $this->index()->updateSettings([
            'searchableAttributes' => ['title', 'title_ngrams', 'content', 'content_ngrams'],
            'displayedAttributes' => ['id', 'discussion_id'],
            'rankingRules' => [
                'typo',
                'words',
                'proximity',
                'attribute',
                'exactness',
            ],
        ]);

        $this->waitForTask($task);
    }

    public function reindexAll(int $batchSize = 100): int
    {
        $processed = 0;
        $lastId = 0;

        $this->ensureIndexExists();
        $this->updateIndexSettings();
        $this->waitForTask($this->index()->deleteAllDocuments());

        while (true) {
            $ids = Capsule::table('discussions')
                ->whereNull('hidden_at')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $documents = [];

            foreach ($ids as $id) {
                $lastId = max($lastId, (int) $id);
                $discussion = Discussion::query()->where('id', $id)->first();

                if (! $discussion) {
                    continue;
                }

                $documents[] = $this->buildDocument($discussion);
            }

            if (count($documents) > 0) {
                $this->waitForTask($this->index()->addDocuments($documents));
            }

            $processed += count($documents);
        }

        $this->setLastReindexTime(time());

        return $processed;
    }

    public function getDocumentCount(): int
    {
        $errors = [];

        foreach (['stats', 'getStats', 'show'] as $method) {
            if (! method_exists($this->index(), $method)) {
                continue;
            }

            try {
                $stats = $this->index()->{$method}();
                $count = $this->extractDocumentCount($stats);

                if ($count !== null) {
                    return $count;
                }
            } catch (\Throwable $e) {
                if ($this->isIndexNotFoundException($e)) {
                    throw new \RuntimeException(
                        'Meilisearch index `' . $this->indexName . '` does not exist yet. Run php flarum cnsearch:reindex to create it.'
                    );
                }

                $errors[] = $e->getMessage();
            }
        }

        $message = 'Unable to retrieve Meilisearch document count.';

        if ($errors !== []) {
            $message .= ' ' . implode(' ', array_unique($errors));
        }

        throw new \RuntimeException($message);
    }

    protected function extractDocumentCount(mixed $stats): ?int
    {
        if (is_array($stats)) {
            return isset($stats['numberOfDocuments']) ? (int) $stats['numberOfDocuments'] : null;
        }

        if (is_object($stats)) {
            if (isset($stats->numberOfDocuments)) {
                return (int) $stats->numberOfDocuments;
            }

            if (method_exists($stats, 'toArray')) {
                return $this->extractDocumentCount($stats->toArray());
            }

            if ($stats instanceof \JsonSerializable) {
                return $this->extractDocumentCount($stats->jsonSerialize());
            }
        }

        return null;
    }

    public function getLastReindexTime(): ?int
    {
        $timestamp = $this->settings->get('cnsearch.last_reindex_at');

        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        return (int) $timestamp;
    }

    protected function setLastReindexTime(int $timestamp): void
    {
        $this->settings->set('cnsearch.last_reindex_at', $timestamp);
    }

    public function getSourceDiscussionCount(): int
    {
        return (int) Capsule::table('discussions')
            ->whereNull('hidden_at')
            ->count();
    }

    public function getSourcePostCount(): int
    {
        return (int) Capsule::table('posts')
            ->join('discussions', 'discussions.id', '=', 'posts.discussion_id')
            ->whereNull('discussions.hidden_at')
            ->whereNull('posts.hidden_at')
            ->where('posts.type', CommentPost::$type)
            ->count();
    }

    public function updateDiscussion(Discussion $discussion): int
    {
        if ($discussion->hidden_at !== null) {
            $this->deleteDiscussion((int) $discussion->id);

            return 0;
        }

        $this->ensureIndexExists();
        $this->waitForTask($this->index()->addDocuments([$this->buildDocument($discussion)]));

        return 1;
    }

    public function deleteDiscussion(int $discussionId): bool
    {
        try {
            $this->waitForTask($this->index()->deleteDocument($discussionId));

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function testConnection(): bool
    {
        if (method_exists($this->client, 'health')) {
            $this->client->health();

            return true;
        }

        $this->fetchIndexInfo();

        return true;
    }

    public function search(string $query, User $actor, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $ids = $this->searchVisibleDiscussionIds($query, $actor, $perPage, $offset);

        return [
            'data' => $ids,
            'meta' => [
                'total' => $this->countVisibleDiscussionIds($query, $actor),
                'page' => $page,
                'perPage' => $perPage,
            ],
        ];
    }

    public function searchDiscussionIds(string $query, int $limit = 1000, int $offset = 0): array
    {
        $query = $this->normalizeContent($query);
        $searchLimit = $offset + $limit;

        if ($query === '') {
            return [];
        }

        $hits = $this->searchHits($query, $searchLimit);

        if ($this->containsCjk($query)) {
            $expandedQuery = $this->buildExpandedQuery($query);

            if ($expandedQuery !== $query) {
                $hits = array_merge($hits, $this->searchHits($expandedQuery, $searchLimit));
            }
        }

        $ids = [];

        foreach ($hits as $hit) {
            $id = (int) ($hit['discussion_id'] ?? 0);

            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return array_slice($ids, $offset, $limit);
    }

    protected function searchVisibleDiscussionIds(string $query, User $actor, int $limit, int $offset = 0): array
    {
        $visibleIds = [];
        $searchOffset = 0;
        $searchLimit = max($limit + $offset, $limit, 20);

        while (count($visibleIds) < $offset + $limit) {
            $ids = $this->searchDiscussionIds($query, $searchLimit, $searchOffset);

            if ($ids === []) {
                break;
            }

            foreach ($this->filterVisibleDiscussionIds($ids, $actor) as $id) {
                if (! in_array($id, $visibleIds, true)) {
                    $visibleIds[] = $id;
                }
            }

            if (count($ids) < $searchLimit) {
                break;
            }

            $searchOffset += $searchLimit;
        }

        return array_slice($visibleIds, $offset, $limit);
    }

    protected function countVisibleDiscussionIds(string $query, User $actor): int
    {
        $count = 0;
        $searchOffset = 0;
        $searchLimit = 1000;

        while (true) {
            $ids = $this->searchDiscussionIds($query, $searchLimit, $searchOffset);

            if ($ids === []) {
                break;
            }

            $count += count($this->filterVisibleDiscussionIds($ids, $actor));

            if (count($ids) < $searchLimit) {
                break;
            }

            $searchOffset += $searchLimit;
        }

        return $count;
    }

    protected function searchHits(string $query, int $limit, int $offset = 0): array
    {
        $results = $this->index()->search($query, [
            'attributesToRetrieve' => ['discussion_id'],
            'offset' => $offset,
            'limit' => $limit,
        ]);

        return $results->getHits();
    }

    protected function filterVisibleDiscussionIds(array $ids, User $actor): array
    {
        if ($ids === []) {
            return [];
        }

        $visibleIds = Discussion::query()
            ->whereIn('id', $ids)
            ->whereVisibleTo($actor)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
        $visibleLookup = array_flip($visibleIds);

        return array_values(array_filter(
            $ids,
            static fn (int $id): bool => isset($visibleLookup[$id])
        ));
    }

    protected function buildDocument(Discussion $discussion): array
    {
        $content = $this->getDiscussionContent($discussion);

        return [
            'id' => $discussion->id,
            'discussion_id' => $discussion->id,
            'title' => $discussion->title,
            'title_ngrams' => $this->buildCjkSearchText((string) $discussion->title),
            'content' => $content,
            'content_ngrams' => $this->buildCjkSearchText($content),
            'comment_count' => (int) $discussion->comment_count,
            'indexed_post_count' => $this->getIndexedPostCount((int) $discussion->id),
        ];
    }

    protected function getDiscussionContent(Discussion $discussion): string
    {
        return Capsule::table('posts')
            ->where('discussion_id', $discussion->id)
            ->where('type', CommentPost::$type)
            ->whereNull('hidden_at')
            ->orderBy('number')
            ->pluck('content')
            ->map(fn ($content): string => $this->normalizeContent((string) $content))
            ->filter()
            ->implode("\n\n");
    }

    protected function getIndexedPostCount(int $discussionId): int
    {
        return (int) Capsule::table('posts')
            ->where('discussion_id', $discussionId)
            ->where('type', CommentPost::$type)
            ->whereNull('hidden_at')
            ->count();
    }

    protected function normalizeContent(string $content): string
    {
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\s+/u', ' ', $content) ?? $content;

        return trim($content);
    }

    protected function buildExpandedQuery(string $query): string
    {
        $ngrams = $this->buildCjkSearchText($query);

        if ($ngrams === '') {
            return $query;
        }

        return trim($query . ' ' . $ngrams);
    }

    protected function buildCjkSearchText(string $text): string
    {
        if (! $this->containsCjk($text)) {
            return '';
        }

        preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]+/u', $text, $matches);

        $tokens = [];

        foreach ($matches[0] as $sequence) {
            $chars = preg_split('//u', $sequence, -1, PREG_SPLIT_NO_EMPTY);

            if (! is_array($chars)) {
                continue;
            }

            $length = count($chars);

            if ($length === 1) {
                $tokens[] = $chars[0];

                continue;
            }

            for ($i = 0; $i < $length; $i++) {
                $tokens[] = $chars[$i];
            }

            foreach ([2, 3] as $size) {
                if ($length < $size) {
                    continue;
                }

                for ($i = 0; $i <= $length - $size; $i++) {
                    $tokens[] = implode('', array_slice($chars, $i, $size));
                }
            }
        }

        return implode(' ', array_values(array_unique($tokens)));
    }

    protected function containsCjk(string $text): bool
    {
        return preg_match('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $text) === 1;
    }
}
