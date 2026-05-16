<?php

namespace Gitzaai\Cnsearch\Api\Controller;

use Flarum\Http\RequestUtil;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StatusController implements RequestHandlerInterface
{
    public function __construct(
        protected DiscussionIndexer $indexer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        try {
            $this->indexer->testConnection();

            $count = $this->indexer->getDocumentCount();
            $status = 'connected';
            $error = null;
        } catch (\Throwable $e) {
            $count = null;
            $status = 'error';
            $error = $e->getMessage();
        }

        return new JsonResponse([
            'data' => [
                'status' => $status,
                'error' => $error,
                'count' => $count,
                'sourceDiscussions' => $this->indexer->getSourceDiscussionCount(),
                'sourcePosts' => $this->indexer->getSourcePostCount(),
                'lastReindexAt' => $this->indexer->getLastReindexTime(),
            ],
        ]);
    }
}
