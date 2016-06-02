<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Service\IRRManager;


class IRRUnilendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('IRR:unilend')
            ->setDescription('Calculate the IRR of the whole platform');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(1000);

        /** @var IRRManager $oIRRManager */
        $oIRRManager = $this->getContainer()->get('unilend.service.irr_manager');
        /** @var Logger $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $oIRRManager->setLogger($logger);

        $sYesterday = date('Y-m-d', strtotime('-1 day'));

        $this->fillProjectLastStatusMaterialized();

        if ($oIRRManager->IRRUnilendNeedsToBeRecalculated($sYesterday)) {
            try {
                $oIRRManager->updateIRRUnilend();
            } catch (\Exception $e) {
                $logger->error('Could not update Unilend IRR: -ExceptionMessage: ' . $e->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
        }
        $this->emptyProjectLastStatusMaterialized();
    }

    /**
     * @param Connection $bdd
     */
    private function fillProjectLastStatusMaterialized()
    {
        /** @var Connection $bdd */
        $bdd = $this->getContainer()->get('doctrine.dbal.default_connection');

        $bdd->query('TRUNCATE projects_last_status_history_materialized');
        $bdd->query('INSERT INTO projects_last_status_history_materialized
                                    SELECT MAX(id_project_status_history) AS id_project_status_history, id_project
                                    FROM projects_status_history
                                    GROUP BY id_project');
        $bdd->query('OPTIMIZE TABLE projects_last_status_history_materialized');
    }

    /**
     * @param Connection $bdd
     */
    private function emptyProjectLastStatusMaterialized()
    {
        /** @var Connection $bdd */
        $bdd = $this->getContainer()->get('doctrine.dbal.default_connection');
        $bdd->query('TRUNCATE projects_last_status_history_materialized');
    }

}
