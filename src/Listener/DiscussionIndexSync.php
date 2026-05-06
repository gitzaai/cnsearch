<?php

namespace Gitzaai\Cnsearch\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Renamed as DiscussionRenamed;
use Flarum\Discussion\Event\Restored as DiscussionRestored;
use Flarum\Discussion\Event\Started as DiscussionStarted;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Posted as PostPosted;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Post\Event\Revised as PostRevised;
use Flarum\Post\Post;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;

class DiscussionIndexSync
{
    protected DiscussionIndexer $indexer;

    public function __construct(DiscussionIndexer $indexer)
    {
        $this->indexer = $indexer;
    }

    public function handle(object $event): void
    {
        if (
            $event instanceof PostPosted
            || $event instanceof PostRevised
            || $event instanceof PostHidden
            || $event instanceof PostRestored
            || $event instanceof PostDeleted
        ) {
            $this->syncPostDiscussion($event->post);

            return;
        }

        if (
            $event instanceof DiscussionStarted
            || $event instanceof DiscussionRenamed
            || $event instanceof DiscussionHidden
            || $event instanceof DiscussionRestored
        ) {
            $this->indexer->updateDiscussion($event->discussion);

            return;
        }

        if ($event instanceof DiscussionDeleted) {
            $this->whenDiscussionDeleted($event);
        }
    }

    protected function syncPostDiscussion(Post $post): void
    {
        if ($post->type !== CommentPost::$type) {
            return;
        }

        if (! $post->relationLoaded('discussion')) {
            $post->load('discussion');
        }

        $discussion = $post->discussion;

        if ($discussion instanceof Discussion) {
            $this->indexer->updateDiscussion($discussion);
        }
    }

    public function whenDiscussionDeleted(DiscussionDeleted $event): void
    {
        $this->indexer->deleteDiscussion((int) $event->discussion->id);
    }
}
