<?php

namespace Gitzaai\Cnsearch\Api\Controller;

use Flarum\Http\RequestUtil;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReindexController implements RequestHandlerInterface
{
    public function __construct(
        protected DiscussionIndexer $indexer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        try {
            $body = (array) $request->getParsedBody();
            $batchSize = max(10, min(500, (int) ($body['batchSize'] ?? 100)));
            $count = $this->indexer->reindexAll($batchSize);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }

        return new JsonResponse([
            'data' => [
                'indexed' => $count,
            ],
        ]);
    }

    protected function errorResponse(\Throwable $e): JsonResponse
    {
        return new JsonResponse([
            'errors' => [
                [
                    'status' => '500',
                    'code' => 'cnsearch_reindex_failed',
                    'detail' => $e->getMessage(),
                ],
            ],
        ], 500);
    }
}
