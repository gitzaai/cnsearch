<?php

namespace Gitzaai\Cnsearch\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ConfigureCommand extends AbstractCommand
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cnsearch:configure')
            ->setDescription('Configure CN Search Meilisearch connection settings.')
            ->addArgument('host', InputArgument::REQUIRED, 'Meilisearch host URL.')
            ->addOption('key', null, InputOption::VALUE_REQUIRED, 'Meilisearch API key. Use an empty value to clear it.')
            ->addOption('index', null, InputOption::VALUE_REQUIRED, 'Meilisearch index name.', 'flarum_discussions');
    }

    protected function fire(): int
    {
        $host = trim((string) $this->input->getArgument('host'));
        $index = trim((string) $this->input->getOption('index'));

        if ($host === '') {
            $this->error('Meilisearch host cannot be empty.');

            return Command::INVALID;
        }

        if ($index === '') {
            $this->error('Meilisearch index cannot be empty.');

            return Command::INVALID;
        }

        $this->settings->set('cnsearch.meili.host', rtrim($host, '/'));
        $this->settings->set('cnsearch.meili.index', $index);

        if ($this->input->hasParameterOption('--key')) {
            $key = (string) $this->input->getOption('key');

            if ($key === '') {
                $this->settings->delete('cnsearch.meili.key');
            } else {
                $this->settings->set('cnsearch.meili.key', $key);
            }
        }

        $this->info('CN Search settings saved.');
        $this->info('Host: ' . rtrim($host, '/'));
        $this->info('Index: ' . $index);

        return Command::SUCCESS;
    }
}
