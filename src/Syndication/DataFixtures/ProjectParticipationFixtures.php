<?php

declare(strict_types=1);

namespace KLS\Syndication\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Entity\ProjectParticipation;
use KLS\Syndication\Entity\ProjectParticipationStatus;
use KLS\Syndication\Entity\ProjectStatus;
use ReflectionException;

class ProjectParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    private ObjectManager $manager;

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var Project[] $projectsWithParticipations */
        $projectsWithParticipations = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION);
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);

        foreach ($projectsWithParticipations as $reference => $project) {
            // Updates the participation for the arranger
            foreach ($project->getProjectParticipations() as $participation) {
                $project->isInInterestCollectionStep()
                    ? $participation->setInterestRequest($this->createRangedOffer(1000000, 2000000))
                    : $participation->setInvitationRequest($this->createOfferWithFee(1000000));
            }

            foreach ($companies as $company) {
                /** @var ProjectParticipation $participation */
                $participation              = $this->createParticipation($project, $company, $staff);
                $projectParticipationStatus = $this->getParticipationStatus($project, $reference);

                if (ProjectParticipationStatus::STATUS_CREATED !== $projectParticipationStatus) {
                    $this->applyProjectParticipationStatus($participation, $projectParticipationStatus);
                }

                $manager->persist($participation);
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
     * @throws Exception
     */
    private function createParticipation(
        Project $project,
        Company $company,
        Staff $staff
    ): ProjectParticipation {
        $participation = (new ProjectParticipation($company, $project, $staff))
            ->setInvitationReplyMode(ProjectParticipation::INVITATION_REPLY_MODE_PRO_RATA)
            ->setAllocationFeeRate((string) $this->faker->randomDigit)
        ;
        $this->forcePublicId($participation, "p-{$project->getPublicId()}-" . \uniqid());

        $participation->getProject()->isInInterestCollectionStep()
            ? $participation->setInterestRequest($this->createRangedOffer(1000000, 2000000))->setInterestReply($this->createOffer(2000000))
            : $participation->setInvitationRequest($this->createOfferWithFee(1000000));

        return $participation;
    }

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
     * @throws ReflectionException
     * @throws Exception
     */
    private function applyProjectParticipationStatus(ProjectParticipation $participation, int $status): void
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference(StaffFixtures::ADMIN);

        $participationStatus = new ProjectParticipationStatus($participation, $status, $addedBy);
        $id                  = \uniqid();
        $this->forcePublicId($participationStatus, "pps-{$id}-" . $status);

        if (ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $status) {
            $participation->setCommitteeDeadline(new DateTimeImmutable());
        }

        $participation->setCurrentStatus($participationStatus);
    }
}
