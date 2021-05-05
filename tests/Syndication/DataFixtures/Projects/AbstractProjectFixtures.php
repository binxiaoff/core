<?php

declare(strict_types=1);

namespace Unilend\Test\Syndication\DataFixtures\Projects;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Provider as Faker;
use ReflectionException;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Listener\Doctrine\Lifecycle\StatusCreatedListener;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Syndication\Entity\ProjectParticipationTranche;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Entity\Tranche;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

abstract class AbstractProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     * @throws Exception
     */
    final public function load(ObjectManager $manager)
    {
        $this->removeEventListenerByClass($manager, 'prePersist', StatusCreatedListener::class);

        $project = new Project(
            $this->getSubmitterStaff(),
            $this->getRiskGroupName(),
            $this->getGlobalFundingMoney()
        );
        $project->setTitle(static::getName());
        $projectReference = 'project/' . static::getName();

        $statuses = $this->getProjectStatuses($project, $this->getStatus(), $this->getSubmitterStaff());

        foreach ($statuses as $status) {
            $manager->persist($status);
            $project->setCurrentStatus($status);
        }

        $this->setPublicId($project, $projectReference);


        $tranches = $this->getTranches($project);
        foreach ($tranches as $tranche) {
            $manager->persist($tranche);
        }

        $projectParticipations = [...$this->getAdditionalProjectParticipations($project), $project->getArrangerProjectParticipation()];

        foreach ($projectParticipations as $projectParticipation) {
            foreach ($this->getProjectParticipationMembers($projectParticipation) as $projectParticipationMember) {
                $manager->persist($projectParticipationMember);
            }

            $manager->persist($projectParticipation);
        }

        foreach ($projectParticipations as $projectParticipation) {
            foreach ($tranches as $tranche) {
                $projectParticipationTranche = $this->getProjectParticipationTranche($projectParticipation, $tranche);
                if ($projectParticipationTranche) {
                    $manager->persist($projectParticipationTranche);
                }
            }
        }

        $manager->persist($project);
        $manager->flush();
    }

    /**
     * @return Money
     */
    abstract protected function getGlobalFundingMoney(): Money;

    /**
     * @return string
     */
    abstract protected static function getName(): string;

    /**
     * @return Staff
     */
    abstract protected function getSubmitterStaff(): Staff;

    /**
     * @return string
     */
    protected function getRiskGroupName(): string
    {
        return Faker\Company::asciify();
    }

    /**
     * @param Project $project
     *
     * @return iterable|ProjectParticipation[]
     */
    protected function getAdditionalProjectParticipations(Project $project)
    {
        return [];
    }

    /**
     * @param Project $project
     *
     * @return iterable|Tranche[]
     */
    protected function getTranches(Project $project): iterable
    {
        return [];
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return iterable|ProjectParticipationMember[]
     */
    protected function getProjectParticipationMembers(ProjectParticipation $projectParticipation): iterable
    {
        return [];
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Tranche              $tranche
     *
     * @return ?ProjectParticipationTranche
     */
    protected function getProjectParticipationTranche(ProjectParticipation $projectParticipation, Tranche $tranche): ?ProjectParticipationTranche
    {
        return null;
    }

    /**
     * @return int
     */
    protected function getStatus()
    {
        return ProjectStatus::STATUS_DRAFT;
    }

    /**
     * @param Project $project
     * @param int     $getStatus
     * @param Staff   $adder
     *
     * @return ProjectStatus[]
     *
     * @throws Exception
     */
    private function getProjectStatuses(Project $project, int $getStatus, Staff $adder)
    {
        $possibleStatuses = ProjectStatus::getPossibleStatuses();

        sort($possibleStatuses);

        $statuses = [];
        switch ($getStatus) {
            case ProjectStatus::STATUS_SYNDICATION_CANCELLED:
                $statuses = [ProjectStatus::STATUS_SYNDICATION_CANCELLED];
                break;
            case ProjectStatus::STATUS_DRAFT:
                $statuses = [];
                break;
            case ProjectStatus::STATUS_INTEREST_EXPRESSION:
                $statuses = [ProjectStatus::STATUS_INTEREST_EXPRESSION];
                break;
            case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                $statuses = [ProjectStatus::STATUS_INTEREST_EXPRESSION, ProjectStatus::STATUS_PARTICIPANT_REPLY];
                break;
            case ProjectStatus::STATUS_ALLOCATION:
                $statuses = [ProjectStatus::STATUS_INTEREST_EXPRESSION, ProjectStatus::STATUS_PARTICIPANT_REPLY, ProjectStatus::STATUS_ALLOCATION];
                break;
            case ProjectStatus::STATUS_SYNDICATION_FINISHED:
                $statuses = [
                    ProjectStatus::STATUS_INTEREST_EXPRESSION,
                    ProjectStatus::STATUS_PARTICIPANT_REPLY,
                    ProjectStatus::STATUS_ALLOCATION,
                    ProjectStatus::STATUS_SYNDICATION_FINISHED,
                ];
                break;
        }

        return array_map(static function ($status) use ($project, $adder) {
            return new ProjectStatus($project, $status, $adder);
        }, $statuses);
    }
}
