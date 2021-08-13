<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\DataFixtures\Projects;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Provider as Faker;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Core\Listener\Doctrine\Lifecycle\StatusCreatedListener;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationTranche;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Entity\Tranche;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use ReflectionException;

abstract class AbstractProjectFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
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

    abstract protected function getGlobalFundingMoney(): Money;

    abstract protected static function getName(): string;

    abstract protected function getSubmitterStaff(): Staff;

    protected function getRiskGroupName(): string
    {
        return Faker\Company::asciify();
    }

    /**
     * @return iterable|ProjectParticipation[]
     */
    protected function getAdditionalProjectParticipations(Project $project)
    {
        return [];
    }

    /**
     * @return iterable|Tranche[]
     */
    protected function getTranches(Project $project): iterable
    {
        return [];
    }

    /**
     * @return iterable|ProjectParticipationMember[]
     */
    protected function getProjectParticipationMembers(ProjectParticipation $projectParticipation): iterable
    {
        return [];
    }

    /**
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
     * @throws Exception
     *
     * @return ProjectStatus[]
     */
    private function getProjectStatuses(Project $project, int $getStatus, Staff $adder)
    {
        $possibleStatuses = ProjectStatus::getPossibleStatuses();

        \sort($possibleStatuses);

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

        return \array_map(static function ($status) use ($project, $adder) {
            return new ProjectStatus($project, $status, $adder);
        }, $statuses);
    }
}
