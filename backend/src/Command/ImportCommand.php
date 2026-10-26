<?php

namespace App\Command;

use App\Service\Import\ImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import', description: 'Импорт заказов из JSON-файла маркетплейса')]
final class ImportCommand extends Command
{
    public function __construct(
        private readonly ImportService $importService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Путь к JSON с ключом orders');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('file');
        if (!is_string($path) || !is_readable($path)) {
            $output->writeln('Файл не найден или недоступен');

            return Command::FAILURE;
        }

        $payload = json_decode(file_get_contents($path), true);
        $report = $this->importService->import($payload);
        $output->writeln(json_encode($report, JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
