<?php

namespace Gitzaai\Cnsearch\Console;

use Flarum\Console\AbstractCommand;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class ReindexCommand extends AbstractCommand
{
    public function __construct(
        protected DiscussionIndexer $indexer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cnsearch:reindex')
            ->setDescription('Rebuild the CN Search Meilisearch discussion index.')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of discussions indexed per batch.', 100);
    }

    protected function fire(): int
    {
        $batchSize = max(10, min(500, (int) $this->input->getOption('batch-size')));
        $count = $this->indexer->reindexAll($batchSize);

        $this->info("CN Search reindexed $count discussions.");

        return Command::SUCCESS;
    }
}
