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
        } catch (\Exception $exception) {
            $this->getContainer()->get(
                'monolog.logger.console')->err('Could not update the "CSG" tax rate ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        $prelevementsObligatoires = $taxTypeRepository->find(TaxType::TYPE_STATUTORY_CONTRIBUTIONS);
        try {
            $prelevementsObligatoires->setRate(12.80);
            $entityManager->flush($prelevementsObligatoires);
        } catch (\Exception $exception) {
            $this->getContainer()->get(
                'monolog.logger.console')->err('Could not update the "Prélèvements obligatoires" tax rate ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }
}