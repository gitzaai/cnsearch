<?php

namespace Gitzaai\Cnsearch\Console;

use Flarum\Console\AbstractCommand;
use Gitzaai\Cnsearch\Search\DiscussionIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SearchCommand extends AbstractCommand
{
    public function __construct(
        protected DiscussionIndexer $indexer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cnsearch:search')
            ->setDescription('Search the CN Search Meilisearch index and print matched discussion IDs.')
            ->addArgument('query', InputArgument::REQUIRED, 'Search query.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of discussion IDs to print.', 20);
    }

    protected function fire(): int
    {
        $query = (string) $this->input->getArgument('query');
        $limit = max(1, min(100, (int) $this->input->getOption('limit')));
        $ids = $this->indexer->searchDiscussionIds($query, $limit);

        if ($ids === []) {
            $this->warn('No Meilisearch matches.');

            return Command::SUCCESS;
        }

        $this->info('Matched discussion IDs: ' . implode(', ', $ids));

        return Command::SUCCESS;
    }
}
