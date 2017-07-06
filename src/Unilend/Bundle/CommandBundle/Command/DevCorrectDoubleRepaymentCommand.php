<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;

class DevCorrectDoubleRepaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:double_repayment:correct')
            ->setDescription('correct the double repayment of project 29101');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');

        $sql = 'SELECT id
                FROM operation 
                WHERE id_project = 29101 
                AND date(added) = \'2017-07-06\' 
                AND id_type in (24,30,33,36,42,48,54,60,66,72) 
                AND id > 48474413 AND id <= 48483759';

/*
        $sql = 'SELECT id
                FROM operation
                WHERE id_project = 23035
                  AND date(added) = \'2017-05-28\'
                  AND id_type in (24,30,33,36,42,48,54,60,66,72)
                  AND id > 45424907 AND id <= 45444950';
*/
        $impactedOperationId = $entityManager->getConnection()->executeQuery($sql)->fetchAll();
        $entityManager->getConnection()->beginTransaction();
        try {
            foreach ($impactedOperationId as $operationId) {
                /** @var Operation $operation */
                $operation = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->find($operationId['id']);
                $operationManager->regularization($operation);
            }
            $entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            $output->writeln('Operations rollback. Error : ' . $exception->getMessage());
        }
    }
}
