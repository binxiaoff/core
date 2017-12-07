<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;

class DevModifyTaxRatesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('dev:tax:modify-rate')
            ->setDescription('Modify some tax rates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $taxTypeRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType');

        $csg = $taxTypeRepository->find(TaxType::TYPE_CSG);
        try {
            $csg->setRate(9.90);
            $entityManager->flush($csg);
            $csgMessage = ':white_check_mark: *CSG* rate was successfully updated. New rate value: ' . $csg->getRate() . ' %.';
        } catch (\Exception $exception) {
            $csgMessage = ':warning: Could not update the *CSG* tax rate: ' . $exception->getMessage();
            $this->getContainer()->get(
                'monolog.logger.console')->error($csgMessage, ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }

        $prelevementsObligatoires = $taxTypeRepository->find(TaxType::TYPE_STATUTORY_CONTRIBUTIONS);
        try {
            $prelevementsObligatoires->setRate(12.80);
            $entityManager->flush($prelevementsObligatoires);
            $POMessage = ' :white_check_mark: *Prélèvements Obligatoire* rate was successfully updated. New rate value: ' . $prelevementsObligatoires->getRate() . ' %.';
        } catch (\Exception $exception) {
            $POMessage = ' :warning: Could not update the *Prélèvements obligatoires* tax rate: ' . $exception->getMessage();
            $this->getContainer()->get(
                'monolog.logger.console')->error($POMessage, ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }

        $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
        $slackManager->sendMessage($csgMessage . $POMessage, '#itprivate');
    }
}
