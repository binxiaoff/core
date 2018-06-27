<?php

namespace Unilend\Bundle\CommandBundle\Command\Dev;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;

class RousseauRepaymentRegularizationCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:rousseau_repayment:regularise')
            ->setDescription('Regularise repayment of 22/06/2008 for Rousseau');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');

        $earlyRepaymentType    = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_EARLY]);
        $repaymentToRegularise = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findBy(['idSubType' => $earlyRepaymentType, 'idProject' => 7454]);

        foreach ($repaymentToRegularise as $repayment) {
            $operationManager->regularize($repayment);
        }

        $output->writeln('done');
    }
}
