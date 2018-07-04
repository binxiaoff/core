<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputArgument, Input\InputInterface, Output\OutputInterface, Style\SymfonyStyle
};
use Unilend\core\Loader;

class GenerateCrudCommand extends ContainerAwareCommand
{
    /**
     * @see Command
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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generatedCruds = [];
        $table          = $input->getArgument('table');

        if ($table) {
            $generatedCruds[] = [$table, $this->checkCrud($table)];
        } else {
            $dataDir   = $this->getContainer()->getParameter('kernel.root_dir') . '/../data';
            $dataFiles = array_diff(scandir($dataDir), ['.', '..']);
            foreach ($dataFiles as $file) {
                if (false === is_dir($file) && 1 === preg_match('#(.+)\.data\.php$#', $file, $matches)) {
                    $table = $matches[1];
                    $generatedCruds[] = [$table, $this->checkCrud($table)];
                }
            }
        }
        $io = new SymfonyStyle($input, $output);
        $io->title('Unilend Crud Generation');
        $headers = ['Table', 'Generated'];
        $io->table($headers, $generatedCruds);
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
            $this->getContainer()->get('monolog.logger.console')->error('Cannot generate CRUD for ' . $table . '. Reason: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
            return 'Failed';
        }
    }
}
