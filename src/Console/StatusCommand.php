<?php

namespace Gitzaai\Cnsearch\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Settings\SettingsRepositoryInterface;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Symfony\Component\Console\Command\Command;

class StatusCommand extends AbstractCommand
{
    public function __construct(
        protected DiscussionIndexer $indexer,
        protected SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cnsearch:status')
            ->setDescription('Show CN Search Meilisearch connection and index status.');
    }

    protected function fire(): int
    {
        $host = trim((string) $this->settings->get('cnsearch.meili.host', ''));
        $index = trim((string) $this->settings->get('cnsearch.meili.index', ''));

        $this->info('Host: ' . ($host === '' ? 'not configured' : rtrim($host, '/')));
        $this->info('Index: ' . ($index === '' ? 'flarum_discussions' : $index));

        try {
            $this->indexer->testConnection();
            $this->info('Connection: OK');
        } catch (\Throwable $e) {
            $this->error('Connection failed: ' . $e->getMessage());

            return Command::FAILURE;
        }

        try {
            $this->info('Documents: ' . $this->indexer->getDocumentCount());
            $this->info('Source discussions: ' . $this->indexer->getSourceDiscussionCount());
            $this->info('Source posts: ' . $this->indexer->getSourcePostCount());

            $lastReindexAt = $this->indexer->getLastReindexTime();
            $this->info('Last reindex: ' . ($lastReindexAt ? date('c', $lastReindexAt) : 'never'));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Index status failed: ' . $e->getMessage());
            $this->info('Run php flarum cnsearch:reindex to create and populate the index.');

            return Command::FAILURE;
        }
    }
}
