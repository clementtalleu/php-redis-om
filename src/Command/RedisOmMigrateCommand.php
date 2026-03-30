<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Console\Runner;

#[AsCommand(name: 'redis-om:migrate')]
class RedisOmMigrateCommand extends Command
{
    public function __construct(
        private ?RedisClientInterface $redisClient = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run Redis OM migrations')
            ->addArgument('dir', InputArgument::OPTIONAL, 'The directory to scan', 'src');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dirPath = $input->getArgument('dir');
        Runner::generateSchema($dirPath, $this->redisClient);
        $output->writeln('<info>Redis migrations executed successfully.</info>');

        return Command::SUCCESS;
    }
}
