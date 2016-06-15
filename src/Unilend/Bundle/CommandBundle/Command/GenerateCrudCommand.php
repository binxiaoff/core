<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
        /** @var EntityManager $entityManager */
        $entityManager      = $this->getContainer()->get('unilend.service.entity_manager');
        $generatedCruds      = [];
        $table              = $input->getArgument('table');
        if ($table) {
            if ($entityManager->getRepository($table)) {
                $generatedCruds[] = [$table, 'OK'];
            } else {
                $generatedCruds[] = [$table, 'Failed'];
            }
        } else {
            $dataDir = $this->getContainer()->getParameter('kernel.root_dir') . '/../data';
            $dataFiles = array_diff(scandir($dataDir), array('.', '..'));
            foreach ($dataFiles as $file) {
                if (false === is_dir($file) && 1 === preg_match('#(.+)\.data\.php$#', $file, $matches)) {
                    $table = $matches[1];
                    try {
                        if ($entityManager->getRepository($table)) {
                            $generatedCruds[] = [$table, 'OK'];
                        } else {
                            $generatedCruds[] = [$table, 'Failed'];
                        }
                    } catch (\Exception $exception) {
                        $generatedCruds[] = [$table, 'Failed'];
                    }
                }
            }
        }
        $io = new SymfonyStyle($input, $output);
        $io->title('Unilend Crud Generation');
        $headers = ['Table', 'Generated'];
        $io->table($headers, $generatedCruds);
    }
}
