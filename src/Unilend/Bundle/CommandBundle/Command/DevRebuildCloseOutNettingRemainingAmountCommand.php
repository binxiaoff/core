<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\CloseOutNettingRepayment;
use Unilend\Bundle\CoreBusinessBundle\Entity\CloseoutNettingPayment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Doctrine\ORM\Query\Expr\Join;

class DevRebuildCloseOutNettingRemainingAmountCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('unilend:dev_tools:con_remaining_amount:rebuild')
            ->setDescription('Rebuild the remaining amount for the close out netting projects');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager                 = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectCloseOutNettingManager = $this->getContainer()->get('unilend.service.project_close_out_netting_manager');
        $loanManager                   = $this->getContainer()->get('unilend.service.loan_manager');

        $closeOutNettingPaymentRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment');
        $closeOutNettingRepaymentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment');
        $loanRepository                     = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
        $projectRepaymentTaskRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask');

        /** @var Projects[] $projectsToRebuild */
        $projectsToRebuild = $entityManager->createQueryBuilder()
            ->select('p')
            ->from('UnilendCoreBusinessBundle:Projects', 'p')
            ->leftJoin('UnilendCoreBusinessBundle:CloseOutNettingPayment', 'conp', Join::WITH, 'p.idProject = conp.idProject')
            ->where('p.closeOutNettingDate IS NOT NULL AND p.closeOutNettingDate != \'0000-00-00 00:00:00\'')
            ->andWhere('conp.id IS NULL')
            ->setMaxResults(26)->getQuery()->getResult();

        foreach ($projectsToRebuild as $project) {
            $output->writeln('Rebuilding the close out repayment for project id ' . $project->getIdProject() . '...');

            $projectCloseOutNettingManager->decline($project, $project->getCloseOutNettingDate(), true);

            /** @var ProjectRepaymentTask[] $closeOutNettingRepaymentTasks */
            $closeOutNettingRepaymentTasks = $projectRepaymentTaskRepository->findBy([
                'idProject' => $project,
                'type'      => ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING,
                'status'    => ProjectRepaymentTask::STATUS_REPAID
            ]);

            /** @var CloseoutNettingPayment $closeOutNettingPayment */
            $closeOutNettingPayment = $closeOutNettingPaymentRepository->findOneBy(['idProject' => $project]);

            $closeOutNettingPayment->setNotified(true);
            $entityManager->flush($closeOutNettingPayment);

            $totalTaskRepaidAmount = 0;
            $repaidCapital         = 0;

            if (0 === count($closeOutNettingRepaymentTasks)) {
                $output->writeln('No repayment after decline date. Rebuild done for project id ' . $project->getIdProject());
            } else {
                $output->writeln('Rebuilding the close out remaining amount for project id ' . $project->getIdProject() . '...');

                /** @var Loans[] $loans */
                $loans = $loanRepository->findBy(['idProject' => $project]);

                foreach ($loans as $loan) {
                    /** @var CloseOutNettingRepayment $closeOutNettingRepayment */
                    $closeOutNettingRepayment = $closeOutNettingRepaymentRepository->findOneBy(['idLoan' => $loan]);
                    if (1 === bccomp($closeOutNettingRepayment->getRepaidCapital(), 0, 2)) {
                        continue; // already rebuilt.
                    }

                    $lenderAmounts = [];

                    $clients = [$loan->getIdLender()->getIdClient()];

                    if (false === empty($loan->getIdTransfer())) {
                        $clients[] = $loanManager->getFirstOwner($loan);
                    }
                    $totalGrossDebtCollectionRepayment = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTotalGrossDebtCollectionRepayment($project, $clients);

                    if (1 === bccomp($totalGrossDebtCollectionRepayment, 0, 2)) {
                        /** @var Loans[] $allLenderLoans */
                        $allLenderLoans             = $loanRepository->findLoansByClients($project, $clients);
                        $totalLoans                 = $loanRepository->getLoansSumByClients($project, $clients);
                        $totalLenderRepaymentAmount = 0;

                        foreach ($allLenderLoans as $lenderLoan) {
                            $loanAmount                              = round(bcdiv($lenderLoan->getAmount(), 100, 4), 2);
                            $loanProportion                          = bcdiv($loanAmount, $totalLoans, 10);
                            $loanRepaymentAmount                     = round(bcmul($totalGrossDebtCollectionRepayment, $loanProportion, 4), 2);
                            $lenderAmounts[$lenderLoan->getIdLoan()] = $loanRepaymentAmount;
                            $totalLenderRepaymentAmount              = round(bcadd($loanRepaymentAmount, $totalLenderRepaymentAmount, 4), 2);
                        }

                        $roundDifference = round(bcsub($totalLenderRepaymentAmount, $totalGrossDebtCollectionRepayment, 4), 2);

                        if (0 !== bccomp($roundDifference, 0, 2)) {
                            $maxAmountLoanId                 = array_search(max($lenderAmounts), $lenderAmounts);
                            $lenderAmounts[$maxAmountLoanId] = round(bcsub($lenderAmounts[$maxAmountLoanId], $roundDifference, 4), 2);
                        }

                        foreach ($lenderAmounts as $loanId => $repaidAmount) {
                            /** @var CloseOutNettingRepayment $closeOutNettingRepayment */
                            $closeOutNettingRepayment = $closeOutNettingRepaymentRepository->findOneBy(['idLoan' => $loanId]);
                            $closeOutNettingRepayment->setRepaidCapital($repaidAmount); // We have repaid only the capitals until now.

                            $entityManager->flush($closeOutNettingRepayment);
                        }
                    }
                }

                $notRepaidCapital = $closeOutNettingRepaymentRepository->getNotRepaidCapitalByProject($project);

                $repaidCapital = round(bcsub($closeOutNettingPayment->getCapital(), $notRepaidCapital, 4), 2);

                $closeOutNettingPayment->setPaidCapital($repaidCapital);
                $entityManager->flush($closeOutNettingPayment);

                foreach ($closeOutNettingRepaymentTasks as $repaymentTask) {
                    $taskRepaidAmount      = round(bcadd($repaymentTask->getCommissionUnilend(), bcadd($repaymentTask->getCapital(), $repaymentTask->getInterest(), 4), 4), 2);
                    $totalTaskRepaidAmount = round(bcadd($totalTaskRepaidAmount, $taskRepaidAmount, 4), 2);
                }
            }

            if (0 !== bccomp($totalTaskRepaidAmount, $repaidCapital, 2)) {
                $output->writeln('The task amount for project id ' . $project->getIdProject() . ' does not equal to the repaid amount in close_out_netting_repayment table.');
            } else {
                $output->writeln('Rebuild done for project id ' . $project->getIdProject());
            }
        }
    }
}
