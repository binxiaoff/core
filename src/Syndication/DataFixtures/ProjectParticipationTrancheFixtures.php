<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipationStatus;
use Unilend\Syndication\Entity\ProjectParticipationTranche;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Entity\Tranche;

class ProjectParticipationTrancheFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION_TRANCHES);

        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);

        foreach ($projects as $project) {
            if ($project->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION)) {
                foreach ($project->getProjectParticipations() as $participation) {
                    foreach ($project->getTranches() as $tranche) {
                        if (
                            $tranche->isSyndicated()
                            || (
                                Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER === $tranche->getUnsyndicatedFunderType()
                                && $participation->getParticipant() === $project->getSubmitterCompany()
                            )
                        ) {
                            $participationTranche = (new ProjectParticipationTranche($participation, $tranche, $staff));

                            $repliedStatuses = [ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED, ProjectParticipationStatus::STATUS_COMMITTEE_PENDED];
                            if (\in_array($participation->getCurrentStatus()->getStatus(), $repliedStatuses, true)) {
                                $participationTranche->setInvitationReply($this->createOffer(1000000));
                                if ($project === $this->getReference(ProjectFixtures::PROJECT_ALLOCATION)) {
                                    $participationTranche->setAllocation($this->createOffer(1000000));
                                }
                            }

                            $manager->persist($participationTranche);
                        }
                    }
                }
            }
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            TrancheFixtures::class,
            ProjectParticipationFixtures::class,
        ];
    }
}
