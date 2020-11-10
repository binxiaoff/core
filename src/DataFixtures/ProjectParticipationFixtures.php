<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use ReflectionException;
use Unilend\Entity\Company;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;

class ProjectParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

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
                $project->isInInterestCollectionStep()
                    ? $participation->setInterestReply($this->createOfferWithFee(1000000))
                    : $participation->setInvitationRequest($this->createOfferWithFee(1000000));
            }

            foreach ($companies as $company) {
                $manager->persist($this->createParticipation($project, $company, $staff));
            }
        }

        $manager->flush();

        foreach ($projectsWithParticipations as $reference => $project) {
            $projectParticipationStatus = $this->getParticipationStatus($project, $reference);
            foreach ($project->getProjectParticipations() as $participation) {
                if (ProjectParticipationStatus::STATUS_CREATED !== $projectParticipationStatus) {
                    $this->applyProjectParticipationStatus($participation, $projectParticipationStatus);
                    $manager->persist($participation);
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
        $participation = (new ProjectParticipation($company, $project, $staff))
            ->setInvitationReplyMode(ProjectParticipation::INVITATION_REPLY_MODE_PRO_RATA)
            ->setAllocationFeeRate((string) $this->faker->randomDigit);
        $this->forcePublicId($participation, "p-{$project->getPublicId()}-" . uniqid());

        $participation->getProject()->isInInterestCollectionStep()
            ? $participation->setInterestRequest($this->createRangedOffer(1000000, 2000000))->setInterestReply($this->createOffer(2000000))
            : $participation->setInvitationRequest($this->createOfferWithFee(1000000));

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

        switch ($reference) {
            case ProjectFixtures::PROJECT_INTEREST:
            case ProjectFixtures::PROJECT_REPLY:
                return ProjectParticipationStatus::STATUS_CREATED;
            case ProjectFixtures::PROJECT_REPLY_COMMITTEE_REFUSED:
                return ProjectParticipationStatus::STATUS_COMMITTEE_REJECTED;
            case ProjectFixtures::PROJECT_REPLY_COMMITTEE_PENDING:
                return ProjectParticipationStatus::STATUS_COMMITTEE_PENDED;
            default:
                return ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED;
        }
    }

    /**
     * @param ProjectParticipation $participation
     * @param int                  $status
     *
     * @throws ReflectionException
     * @throws Exception
     */
    private function applyProjectParticipationStatus(ProjectParticipation $participation, int $status): void
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference(StaffFixtures::ADMIN);

        $participationStatus = new ProjectParticipationStatus($participation, $status, $addedBy);
        $id = uniqid();
        $this->forcePublicId($participationStatus, "pps-{$id}-" . $status);

        if (ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $status) {
            $participation->setCommitteeDeadline(new DateTimeImmutable());
        }

        $participation->setCurrentStatus($participationStatus);
    }
}
