<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Entity\Company;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;

class ProjectParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    public static int $id = 0; // Auto increment public ids

    /**
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var Project[] $projects */
        $projectsWithParticipations = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION);
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);

        /** @var Project $project */
        foreach ($projectsWithParticipations as $reference => $project) {
            // Updates the participation for the arranger
            foreach ($project->getProjectParticipations() as $participation) {
                $participation->setInvitationRequest($this->createOfferWithFee(1000000));
            }

            foreach ($companies as $company) {
                $participation = $this->createParticipation($project, $company, $staff);
                $project->addProjectParticipation($participation);
                $manager->persist($participation);
            }
        }

        $manager->flush();


        /** @var Project $project */
        foreach ($projectsWithParticipations as $reference => $project) {
            foreach ($project->getProjectParticipations() as $index => $participation) {
                $statusCode = $this->getParticipationStatus($project, $reference);
                $participationStatus = new ProjectParticipationStatus($participation, $statusCode, $staff);
                $this->forcePublicId($participationStatus, 'pps-' . uniqid() . '-' . $statusCode);
                if (ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $statusCode) {
                    $participation->setCommitteeDeadline(new DateTimeImmutable());
                }
                $participation->setCurrentStatus($participationStatus);
                $manager->persist($participation);
                $manager->persist($participationStatus);
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
            CompanyFixtures::class,
            ProjectFixtures::class,
        ];
    }

    /**
     * @param Project $project
     * @param Company $company
     * @param Staff   $staff
     *
     * @return ProjectParticipation
     *
     * @throws Exception
     */
    private function createParticipation(
        Project $project,
        Company $company,
        Staff $staff
    ): ProjectParticipation {
        self::$id++;
        $publicId = "p-{$project->getPublicId()}-" . self::$id;
        $participation = (new ProjectParticipation($company, $project, $staff))
            ->setInterestRequest($this->createRangedOffer(1000000, 2000000))
            ->setInterestReply($this->createOffer(2000000))
            ->setInvitationRequest($this->createOfferWithFee(1000000))
            ->setInvitationReplyMode(ProjectParticipation::INVITATION_REPLY_MODE_PRO_RATA)
            ->setAllocationFeeRate((string) $this->faker->randomDigit);
        $this->forcePublicId($participation, $publicId);

        return $participation;
    }

    /**
     * @param Project $project
     * @param string  $reference
     *
     * @return int
     */
    private function getParticipationStatus(Project $project, string $reference): int
    {
        if (ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus()) {
            return ProjectParticipationStatus::STATUS_CREATED;
        }

        if (ProjectFixtures::PROJECT_REPLY === $reference) {
            return ProjectParticipationStatus::STATUS_CREATED;
        }

        if (ProjectFixtures::PROJECT_REPLY_COMMITTEE_REFUSED === $reference) {
            return ProjectParticipationStatus::STATUS_COMMITTEE_REJECTED;
        }

        if (ProjectFixtures::PROJECT_REPLY_COMMITTEE_PENDING === $reference) {
            return ProjectParticipationStatus::STATUS_COMMITTEE_PENDED;
        }

        return ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED;
    }
}
