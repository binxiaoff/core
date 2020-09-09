<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;
use Unilend\Entity\Tranche;

class ParticipationTrancheFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    /**
     * @param ObjectManager $manager
     *
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
                            $tranche->isSyndicated() ||
                            (
                                $tranche->getUnsyndicatedFunderType() === Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER &&
                                $participation->getParticipant() === $project->getSubmitterCompany()
                            )
                        ) {
                            $participationTranche = (new ProjectParticipationTranche($participation, $tranche, $staff));

                            $repliedStatuses =  [ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED, ProjectParticipationStatus::STATUS_COMMITTEE_PENDED];
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
            ParticipationFixtures::class,
        ];
    }
}
