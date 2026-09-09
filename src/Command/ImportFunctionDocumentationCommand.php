<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ManualService;
use App\Service\MaterialStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Импортирует документацию PHP-функций в базу учебных материалов. */
#[AsCommand(name: 'app:import-function-documentation', description: 'Загружает документацию функций из PHP Manual в SQLite')]
final class ImportFunctionDocumentationCommand extends Command
{
    public function __construct(
        private readonly MaterialStore $materials,
        private readonly ManualService $manual,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('functions', InputArgument::IS_ARRAY, 'Имена отдельных функций')
            ->addOption('refresh', null, InputOption::VALUE_NONE, 'Обновить уже загруженную документацию');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $requested = $input->getArgument('functions');
        $functions = $requested === [] ? $this->materials->functionNames() : array_values(array_unique($requested));
        $refresh = (bool) $input->getOption('refresh');
        $imported = 0;
        $skipped = 0;
        $failed = [];

        foreach ($functions as $function) {
            if (!$this->materials->containsFunction($function)) {
                $failed[] = sprintf('%s: функция отсутствует в каталоге', $function);
                continue;
            }
            if (!$refresh && $this->materials->functionDocumentation($function) !== null) {
                ++$skipped;
                continue;
            }

            try {
                $documentation = $this->manual->fetch($function);
                $this->materials->saveFunctionDocumentation(
                    $function,
                    $documentation['definition'],
                    $documentation['short_description'],
                    $documentation['full_description'],
                    $documentation['source_url'],
                );
                ++$imported;
                $io->writeln(sprintf('<info>Импортирована:</info> %s', $function));
            } catch (\Throwable $exception) {
                $failed[] = sprintf('%s: %s', $function, $exception->getMessage());
            }
        }

        $io->success(sprintf('Импортировано: %d; пропущено: %d; ошибок: %d.', $imported, $skipped, count($failed)));
        if ($failed !== []) {
            $io->listing($failed);
        }

        return $failed === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
