<?php

namespace Gitzaai\Cnsearch\Api\Controller;

use Flarum\Http\RequestUtil;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestConnectionController implements RequestHandlerInterface
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
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }

        return new JsonResponse([
            'data' => [
                'ok' => true,
            ],
        ]);
    }

    protected function errorResponse(\Throwable $e): JsonResponse
    {
        return new JsonResponse([
            'errors' => [
                [
                    'status' => '500',
                    'code' => 'cnsearch_connection_failed',
                    'detail' => $e->getMessage(),
                ],
            ],
        ], 500);
    }
}
