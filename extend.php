<?php

use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Renamed as DiscussionRenamed;
use Flarum\Discussion\Event\Restored as DiscussionRestored;
use Flarum\Discussion\Event\Started as DiscussionStarted;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
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
use Gitzaai\Cnsearch\Api\Controller\ReindexController;
use Gitzaai\Cnsearch\Api\Controller\SearchController;
use Gitzaai\Cnsearch\Api\Controller\StatusController;
use Gitzaai\Cnsearch\Api\Controller\TestConnectionController;
use Gitzaai\Cnsearch\Listener\DiscussionIndexSync;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Gitzaai\Cnsearch\Search\MeilisearchDiscussionFulltextFilter;

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
        ->setFullText(DiscussionSearcher::class, MeilisearchDiscussionFulltextFilter::class),
    (new Extend\Settings)
        ->default('cnsearch.meili.host', '')
        ->default('cnsearch.meili.index', '')
        ->default('cnsearch.meili.key', ''),
    (new Extend\Routes('api'))
        ->get('/cnsearch/search', 'gitzaai.cnsearch.search', SearchController::class)
        ->post('/cnsearch/reindex', 'gitzaai.cnsearch.reindex', ReindexController::class)
        ->get('/cnsearch/test-connection', 'gitzaai.cnsearch.test-connection', TestConnectionController::class)
        ->get('/cnsearch/status', 'gitzaai.cnsearch.status', StatusController::class),
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
