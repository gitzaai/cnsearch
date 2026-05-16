<?php

namespace Gitzaai\Cnsearch\Api\Controller;

use Flarum\Http\RequestUtil;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SearchController implements RequestHandlerInterface
{
    public function __construct(
        protected DiscussionIndexer $indexer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $queryParams = $request->getQueryParams();
        $query = trim((string) ($queryParams['q'] ?? $queryParams['query'] ?? ''));
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($queryParams['perPage'] ?? 20)));

        return new JsonResponse($this->indexer->search($query, $actor, $page, $perPage));
    }
}
