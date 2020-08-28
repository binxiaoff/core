<?php

namespace Unilend\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Entity\Company;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
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
            $projectParticipationStatus = $this->getParticipationStatus($project, $reference);

            foreach ($companies as $company) {
                $participation = $this->createParticipation($project, $company, $staff, $projectParticipationStatus);
                $correctStatus = $participation->getCurrentStatus();
                // Needed because we do not record the current status in the statuses array and this array is not persisted
                if (ProjectParticipationStatus::STATUS_CREATED !== $projectParticipationStatus) {
                    $manager->persist(new ProjectParticipationStatus($participation, ProjectParticipationStatus::STATUS_CREATED, $staff));
                }
                $manager->persist($participation);

                // Need to repersist the correct status because of listener  Unilend\Listener\Doctrine\Lifecycle\StatusCreatedListener
                $manager->persist($correctStatus);
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
     * @param Project  $project
     * @param Company  $company
     * @param Staff    $staff
     * @param int|null $status
     *
     * @return ProjectParticipation
     *
     * @throws Exception
     */
    private function createParticipation(
        Project $project,
        Company $company,
        Staff $staff,
        int $status
    ): ProjectParticipation {
        self::$id++;
        $publicId = "p-{$project->getPublicId()}-" . self::$id;
        $participation = (new ProjectParticipation($company, $project, $staff))
            ->setInterestRequest($this->createRangedOffer(1000000, 2000000))
            ->setInterestReply($this->createOffer(2000000))
            ->setInvitationRequest($this->createOfferWithFee(1000000))
            ->setInvitationReplyMode('pro-rata')
            ->setAllocationFeeRate($this->faker->randomDigit);
        $this->forcePublicId($participation, "p-{$project->getPublicId()}-" . uniqid());
        $participationStatus = new ProjectParticipationStatus($participation, $status, $staff);
        $this->forcePublicId($participationStatus, "pps-$publicId");
        if (ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $status) {
            $participation->setCommitteeDeadline(new DateTimeImmutable());
        }
        $participation->setCurrentStatus($participationStatus);

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
