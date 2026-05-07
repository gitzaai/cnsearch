<?php

use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Renamed as DiscussionRenamed;
use Flarum\Discussion\Event\Restored as DiscussionRestored;
use Flarum\Discussion\Event\Started as DiscussionStarted;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Http\RequestUtil;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Posted as PostPosted;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Post\Event\Revised as PostRevised;
use Flarum\Search\Database\DatabaseSearchDriver;
use Gitzaai\Cnsearch\Console\ConfigureCommand;
use Gitzaai\Cnsearch\Console\ReindexCommand;
use Gitzaai\Cnsearch\Console\SearchCommand;
use Gitzaai\Cnsearch\Console\StatusCommand;
use Gitzaai\Cnsearch\Listener\DiscussionIndexSync;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Gitzaai\Cnsearch\Search\MeilisearchDiscussionFulltextFilter;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

return [
    new Extend\Locales(__DIR__ . '/locale'),
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),
    (new Extend\Console)
        ->command(ConfigureCommand::class)
        ->command(ReindexCommand::class)
        ->command(SearchCommand::class)
        ->command(StatusCommand::class),
    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->setFulltext(DiscussionSearcher::class, MeilisearchDiscussionFulltextFilter::class),
    (new Extend\Settings)
        ->default('cnsearch.meili.host', '')
        ->default('cnsearch.meili.index', '')
        ->default('cnsearch.meili.key', ''),
    (new Extend\Routes('api'))
        ->get('/cnsearch/search', 'cnsearch.search', function (DiscussionIndexer $indexer) {
            return new class ($indexer) implements RequestHandlerInterface {
                public function __construct(
                    protected DiscussionIndexer $indexer
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $actor = RequestUtil::getActor($request);
                    $queryParams = $request->getQueryParams();
                    $query = trim((string) ($queryParams['q'] ?? ''));
                    $page = isset($queryParams['page']) ? max(1, (int) $queryParams['page']) : 1;
                    $perPage = isset($queryParams['perPage']) ? max(1, min(100, (int) $queryParams['perPage'])) : 20;

                    if ($query === '') {
                        return new JsonResponse(['data' => [], 'meta' => ['total' => 0]]);
                    }

                    return new JsonResponse($this->indexer->search($query, $actor, $page, $perPage));
                }
            };
        })
        ->post('/cnsearch/reindex', 'cnsearch.reindex', function (DiscussionIndexer $indexer) {
            return new class ($indexer) implements RequestHandlerInterface {
                public function __construct(
                    protected DiscussionIndexer $indexer
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    RequestUtil::getActor($request)->assertAdmin();

                    $payload = $request->getParsedBody();
                    $payload = is_array($payload) ? $payload : [];
                    $batchSize = isset($payload['batchSize']) ? max(10, min(500, (int) $payload['batchSize'])) : 100;
                    $count = $this->indexer->reindexAll($batchSize);

                    return new JsonResponse(['data' => ['reindexed' => $count]]);
                }
            };
        })
        ->get('/cnsearch/test-connection', 'cnsearch.test-connection', function (DiscussionIndexer $indexer) {
            return new class ($indexer) implements RequestHandlerInterface {
                public function __construct(
                    protected DiscussionIndexer $indexer
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    RequestUtil::getActor($request)->assertAdmin();

                    try {
                        $this->indexer->testConnection();

                        return new JsonResponse(['data' => ['ok' => true]]);
                    } catch (\Throwable $e) {
                        return new JsonResponse([
                            'errors' => [[
                                'status' => '500',
                                'title' => 'Meilisearch connection failed',
                                'detail' => $e->getMessage(),
                            ]],
                        ], 500);
                    }
                }
            };
        })
        ->get('/cnsearch/status', 'cnsearch.status', function (DiscussionIndexer $indexer) {
            return new class ($indexer) implements RequestHandlerInterface {
                public function __construct(
                    protected DiscussionIndexer $indexer
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    RequestUtil::getActor($request)->assertAdmin();

                    try {
                        return new JsonResponse(['data' => [
                            'count' => $this->indexer->getDocumentCount(),
                            'sourceDiscussions' => $this->indexer->getSourceDiscussionCount(),
                            'sourcePosts' => $this->indexer->getSourcePostCount(),
                            'lastReindexAt' => $this->indexer->getLastReindexTime(),
                        ]]);
                    } catch (\Throwable $e) {
                        return new JsonResponse([
                            'errors' => [[
                                'status' => '500',
                                'title' => 'Failed to retrieve index status',
                                'detail' => $e->getMessage(),
                            ]],
                        ], 500);
                    }
                }
            };
        }),
    (new \Flarum\Extend\Event())
        ->listen(PostPosted::class, DiscussionIndexSync::class)
        ->listen(PostRevised::class, DiscussionIndexSync::class)
        ->listen(PostHidden::class, DiscussionIndexSync::class)
        ->listen(PostRestored::class, DiscussionIndexSync::class)
        ->listen(PostDeleted::class, DiscussionIndexSync::class)
        ->listen(DiscussionStarted::class, DiscussionIndexSync::class)
        ->listen(DiscussionRenamed::class, DiscussionIndexSync::class)
        ->listen(DiscussionHidden::class, DiscussionIndexSync::class)
        ->listen(DiscussionRestored::class, DiscussionIndexSync::class)
        ->listen(DiscussionDeleted::class, DiscussionIndexSync::class),
];
