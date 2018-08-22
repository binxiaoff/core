<?php declare(strict_types=1);

namespace Unilend\Bundle\FrontBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\{AutoBidSettingsManager, ProjectManager};

class ProjectExtension extends \Twig_Extension
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    private $projectManager;

    /**
     *
     * @param EntityManagerInterface $entityManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param ProjectManager         $projectManager
     */
    public function __construct(EntityManagerInterface $entityManager, AutoBidSettingsManager $autoBidSettingsManager, ProjectManager $projectManager)
    {
        $this->entityManager          = $entityManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->projectManager         = $projectManager;

    }

    /**
     * @inheritdoc
     */
    public
    function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('projectFundingPercentage', [$this, 'getFundingPercentage']),
            new \Twig_SimpleFunction('projectPredictedAutoBidPercentage', [$this, 'getPredictedAutoBidFundingPercentage']),
            new \Twig_SimpleFunction('projectAverageInterestRate', [$this, 'getAverageInterestRate']),
            new \Twig_SimpleFunction('projectEnded', [$this, 'getProjectEnded']),
            new \Twig_SimpleFunction('projectRepaymentScheduleAmount', [$this, 'getRepaymentScheduleAmount']),
            new \Twig_SimpleFunction('projectRemainingCapital', [$this, 'getRemainingCapital']),
            new \Twig_SimpleFunction('projectNextRepaymentScheduleAmount', [$this, 'getNextRepaymentScheduleAmount']),
        );
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getFundingPercentage(Projects $project): float
    {
        try {
            $totalBidAmount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->getProjectTotalAmount($project);
        } catch (\Exception $exception) {
            $totalBidAmount = 0;
        }

        return min(round(($totalBidAmount / $project->getAmount()) * 100, 1), 100);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getPredictedAutoBidFundingPercentage(Projects $project): float
    {
        $predictAmountAutoBid = 0;
        if (false === empty($project->getRisk()) && false === empty($project->getPeriod())) {
            $predictAmountAutoBid = $this->autoBidSettingsManager->predictAmount($project->getRisk(), $project->getPeriod());
        }

        return round(($predictAmountAutoBid / $project->getAmount()) * 100, 1);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getAverageInterestRate(Projects $project): float
    {
        return round($this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getAverageInterestRate($project), 2);
    }

    /**
     * @param Projects $project
     *
     * @return \DateTime
     */
    public function getProjectEnded(Projects $project): \DateTime
    {
        return $this->projectManager->getProjectEndDate($project);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getRepaymentScheduleAmount(Projects $project): float
    {
        $scheduledAmount           = 0;
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        if (false === in_array($project->getStatus(), [ProjectsStatus::REMBOURSEMENT_ANTICIPE, ProjectsStatus::REMBOURSE])) {
            $schedule = $paymentScheduleRepository->findOneBy(['idProject' => $project]);

            if ($schedule) {
                $scheduledAmount = round(bcdiv(strval($schedule->getCapital() + $schedule->getInterets() + $schedule->getCommission() + $schedule->getTva()), '100', 4), 2);
            }
        }

        return $scheduledAmount;
    }

    /**
     * @param Projects $project
     *
     * @return \DateTime
     */
    public function getNextRepaymentScheduleAmount(Projects $project): \DateTime
    {
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $nextScheduledDate         = new \DateTime();

        if (false === in_array($project->getStatus(), [ProjectsStatus::REMBOURSEMENT_ANTICIPE, ProjectsStatus::REMBOURSE])) {
            $nextRepayment = $paymentScheduleRepository->findOneBy(
                ['idProject' => $project, 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING],
                ['dateEcheanceEmprunteur' => 'ASC']
            );

            if ($nextRepayment) {
                $nextScheduledDate = $nextRepayment->getDateEcheanceEmprunteur();
            }
        }

        return $nextScheduledDate;
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getRemainingCapital(Projects $project): float
    {
        $amounts = $this->projectManager->getRemainingAmounts($project);

        return (float) $amounts['capital'];
    }
}
