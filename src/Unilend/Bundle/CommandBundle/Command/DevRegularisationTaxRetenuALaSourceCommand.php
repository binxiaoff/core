<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;

class DevRegularisationTaxRetenuALaSourceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:retenue_a_la_source:regularise')
            ->setDescription('TMA-2686 regulariser la taxe retenu à la source pour les personnes physiques');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager            = $this->getContainer()->get('unilend.service.operation_manager');
        $projectRepaymentTaskManager = $this->getContainer()->get('unilend.service_repayment.project_repayment_task_manager');

        $operationRepository                = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $projectRepaymentTaskLogRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTaskLog');
        $closeOutNettingRepaymentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment');

        $repaymentLogs = $operationRepository->getRetenuALaSourceTaxForPerson(true);

        foreach ($repaymentLogs as $repaymentLog) {
            /** @var ProjectRepaymentTaskLog $originalRepaymentLog */
            $originalRepaymentLog = $projectRepaymentTaskLogRepository->find($repaymentLog['idRepaymentTaskLog']);

            $repaidAmount = 0;
            $repaidCount  = 0;

            $regularisationRepaymentLog = $projectRepaymentTaskManager->start($originalRepaymentLog->getIdTask());
            $newRepaymentLog            = $projectRepaymentTaskManager->start($originalRepaymentLog->getIdTask());

            $taxOperationsToRegularize = $operationRepository->getRetenuALaSourceTaxForPerson(false, $repaymentLog['idRepaymentTaskLog']);

            foreach ($taxOperationsToRegularize as $taxOperation) {
                $entityManager->getConnection()->beginTransaction();
                try {
                    /** @var Operation[] $repaymentOperations */
                    $repaymentOperations = $operationRepository->findBy(['idRepaymentTaskLog' => $taxOperation['idRepaymentTaskLog'], 'idLoan' => $taxOperation['idLoan']]);

                    if ($operationRepository->findOneBy(['idRepaymentTaskLog' => $taxOperation['idRepaymentTaskLog'], 'idLoan' => $taxOperation['idLoan'], 'idType' => [107, 110, 131]])) {
                        $output->writeln('Regularisation already exists for id loan : ' . $taxOperation['idLoan'] . ' in repayment log id:' . $taxOperation['idRepaymentTaskLog']);
                        $entityManager->getConnection()->commit();
                        continue;
                    }
                    $capital           = 0;
                    $interest          = 0;
                    $repaymentSchedule = null;

                    foreach ($repaymentOperations as $repaymentOperation) {
                        if (OperationType::GROSS_INTEREST_REPAYMENT === $repaymentOperation->getType()->getLabel()) {
                            $interest  = $repaymentOperation->getAmount();
                            $taxBefore = round(bcmul($interest, 0.15, 4), 2);
                            $taxAfter  = round(bcmul($interest, 0.128, 4), 2);

                            if (0 === bccomp($taxBefore, $taxAfter, 2)) {
                                $output->writeln('No need to regularise for id loan : ' . $taxOperation['idLoan'] . ' in repayment log id:' . $taxOperation['idRepaymentTaskLog']);
                                $entityManager->getConnection()->commit();
                                continue 2;
                            }
                        }
                    }

                    foreach ($repaymentOperations as $repaymentOperation) {
                        if (null === $repaymentSchedule && $repaymentOperation->getRepaymentSchedule()) {
                            $repaymentSchedule = $repaymentOperation->getRepaymentSchedule();
                        }

                        if (OperationType::CAPITAL_REPAYMENT === $repaymentOperation->getType()->getLabel()) {
                            $capital = $repaymentOperation->getAmount();
                        }

                        if (OperationType::GROSS_INTEREST_REPAYMENT === $repaymentOperation->getType()->getLabel()) {
                            $interest = $repaymentOperation->getAmount();
                        }

                        $operationManager->regularize($repaymentOperation, null, $regularisationRepaymentLog);
                    }

                    $repaidAmount = round(bcadd($capital, $interest, 4), 2);
                    $repaidCount++;

                    switch ($originalRepaymentLog->getIdTask()->getType()) {
                        case ProjectRepaymentTask::TYPE_REGULAR:
                        case ProjectRepaymentTask::TYPE_LATE:
                            $operationManager->repayment($capital, $interest, $repaymentSchedule, $newRepaymentLog);
                            break;
                        case ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING:
                            $closeOutNettingRepayment = $closeOutNettingRepaymentRepository->findOneBy(['idLoan' => $taxOperation['idLoan']]);
                            $operationManager->closeOutNettingRepayment($capital, $interest, $closeOutNettingRepayment, $newRepaymentLog);
                            break;
                        default:
                            $output->writeln('unsupported repayment found');
                            break;
                    }

                    $output->writeln('Regularisation done for id loan : ' . $taxOperation['idLoan'] . ' in repayment log id:' . $taxOperation['idRepaymentTaskLog']);
                    $entityManager->getConnection()->commit();
                } catch (\Exception $exception) {
                    $entityManager->getConnection()->rollBack();
                    if ($repaidCount > 0) {
                        $projectRepaymentTaskManager->log($regularisationRepaymentLog, -$repaidAmount, $repaidCount);
                        $projectRepaymentTaskManager->end($regularisationRepaymentLog, ProjectRepaymentTask::STATUS_ERROR);
                        $projectRepaymentTaskManager->log($newRepaymentLog, $repaidAmount, $repaidCount);
                        $projectRepaymentTaskManager->end($newRepaymentLog, ProjectRepaymentTask::STATUS_ERROR);
                    } else {
                        $entityManager->remove($regularisationRepaymentLog);
                        $entityManager->remove($newRepaymentLog);
                    }

                    throw $exception;
                }
            }

            if ($repaidCount > 0) {
                $projectRepaymentTaskManager->log($regularisationRepaymentLog, -$repaidAmount, $repaidCount);
                $projectRepaymentTaskManager->end($regularisationRepaymentLog, ProjectRepaymentTask::STATUS_REPAID);
                $projectRepaymentTaskManager->log($newRepaymentLog, $repaidAmount, $repaidCount);
                $projectRepaymentTaskManager->end($newRepaymentLog, ProjectRepaymentTask::STATUS_REPAID);
            } else {
                $entityManager->remove($regularisationRepaymentLog);
                $entityManager->remove($newRepaymentLog);
            }

            $output->writeln('Regularisation done for id repayment log : ' . $originalRepaymentLog->getId());
        }

        $output->writeln('Regularisation done for all repayments');
    }
}
