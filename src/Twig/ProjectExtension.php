<?php declare(strict_types=1);

namespace Unilend\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Unilend\Entity\{Bids, EcheanciersEmprunteur, Projects, ProjectsStatus};
use Unilend\Service\{AutoBidSettingsManager, ProjectManager};

class ProjectExtension extends AbstractExtension
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var ProjectManager */
    private $projectManager;

    /**
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
    public function getFunctions()
    {
        return array(
            new TwigFunction('projectFundingPercentage', [$this, 'getFundingPercentage']),
            new TwigFunction('projectAverageInterestRate', [$this, 'getAverageInterestRate']),
            new TwigFunction('projectEnded', [$this, 'getProjectEnded']),
            new TwigFunction('projectRepaymentScheduleAmount', [$this, 'getRepaymentScheduleAmount']),
            new TwigFunction('projectRemainingCapital', [$this, 'getRemainingCapital']),
            new TwigFunction('projectNextRepaymentScheduleAmount', [$this, 'getNextRepaymentScheduleAmount']),
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
            $totalBidAmount = $this->entityManager->getRepository(Bids::class)->getProjectTotalAmount($project);
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
    public function getAverageInterestRate(Projects $project): float
    {
        return round($this->entityManager->getRepository(Projects::class)->getAverageInterestRate($project), 2);
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
        $paymentScheduleRepository = $this->entityManager->getRepository(EcheanciersEmprunteur::class);

        if ($project->getStatus() !== ProjectsStatus::STATUS_FINISHED) {
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
        $paymentScheduleRepository = $this->entityManager->getRepository(EcheanciersEmprunteur::class);
        $nextScheduledDate         = new \DateTime();

        if ($project->getStatus() !== ProjectsStatus::STATUS_FINISHED) {
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
