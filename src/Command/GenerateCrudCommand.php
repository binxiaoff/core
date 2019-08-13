<?php

declare(strict_types=1);

namespace Unilend\Command;

use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\{Command\Command, Input\InputArgument, Input\InputInterface, Output\OutputInterface, Style\SymfonyStyle};
use Unilend\core\Loader;

class GenerateCrudCommand extends Command
{
    /** @var string */
    private $projectDirectory;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string          $projectDirectory
     * @param LoggerInterface $consoleLogger
     */
    public function __construct(string $projectDirectory, LoggerInterface $consoleLogger)
    {
        $this->projectDirectory = $projectDirectory;
        $this->logger           = $consoleLogger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unilend:generate:crud')
            ->setDescription('Generate the crud file in data/crud/ if it does not exist')
            ->addArgument(
                'table',
                InputArgument::OPTIONAL,
                'Which table do you want to generate? if not set, it will generate for all tables that have a *.data.php in data/.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $input->getArgument('table');

        $io = new SymfonyStyle($input, $output);
        $io->title('Unilend Crud Generation');
        $headers = ['Table', 'Generated'];
        $io->table($headers, $this->getTables($table));
    }

    /**
     * @param string|null $table
     *
     * @return array
     */
    private function getTables(?string $table)
    {
        if ($table) {
            return [[$table, $this->checkCrud($table)]];
        }

        $tables    = [];
        $dataDir   = $this->projectDirectory . DIRECTORY_SEPARATOR . 'apps/data';
        $dataFiles = array_diff(scandir($dataDir), ['.', '..']);
        foreach ($dataFiles as $file) {
            if (false === is_dir($file) && 1 === preg_match('#(.+)\.data\.php$#', $file, $matches)) {
                $table    = $matches[1];
                $tables[] = [$table, $this->checkCrud($table)];
            }
        }

        return $tables;
    }

    /**
     * @param string $table
     *
     * @return string
     */
    private function checkCrud(string $table): string
    {
        if (Loader::crudExists($table)) {
            return 'OK';
        }

        try {
            Loader::generateCRUD($table);

            return 'OK';
        } catch (DBALException $exception) {
            $this->logger->error(sprintf('Cannot generate CRUD for %s. Error: %s', $table, $exception->getMessage()), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
            ]);
        }

        return 'Failed';
    }
}
