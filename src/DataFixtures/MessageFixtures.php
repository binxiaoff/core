<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\{Message, MessageThread, Project, ProjectParticipation, Staff};
use Unilend\Repository\StaffRepository;


class MessageFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /** @var StaffRepository */
    private StaffRepository $staffRepository;

    /** @var ObjectManager */
    private ObjectManager $manager;

    /** @var array */
    private array $messageThreads = [];

    /**
     * MessageFixtures constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param StaffRepository       $staffRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, StaffRepository $staffRepository)
    {
        parent::__construct($tokenStorage);
        $this->staffRepository = $staffRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        /** @var Project[] $projectsWithParticipations */
        $projectsWithParticipations = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION);

        foreach ($projectsWithParticipations as $projectWithParticipation) {
            $staffSender = $this->staffRepository->findOneBy([
                'client' => $projectWithParticipation->getSubmitterClient(),
                'company' => $projectWithParticipation->getSubmitterCompany(),
            ]);

            // Create a projectOrganizer.staff message to each projectParticipationMember.staff
            $this->createMessagesForProjectParticipations($projectWithParticipation, $staffSender);

            // Create a random projectParticipation.member.staff message to each projectParticipationMember.staff
            $this->createMessagesForProjectParticipations($projectWithParticipation);
        }
    }

    /**
     * @return string[]Project::getArranger()
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
            ProjectFixtures::class,
            ProjectParticipationFixtures::class,
        ];
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return MessageThread
     */
    private function getProjectParticipationMessageThread(ProjectParticipation $projectParticipation): MessageThread
    {
        if (false === array_key_exists($projectParticipation->getId(), $this->messageThreads)) {
            $messageThread = (new MessageThread())->setProjectParticipation($projectParticipation);
            $this->manager->persist($messageThread);
            $this->manager->flush();
            $this->messageThreads[$projectParticipation->getId()] = $messageThread;
        }

        return $this->messageThreads[$projectParticipation->getId()];
    }

    /**
     * @param Project    $project
     * @param Staff|null $sender
     */
    private function createMessagesForProjectParticipations(Project $project, Staff $sender = null): void
    {
        $projectParticipations = $project->getProjectParticipations();
        foreach ($projectParticipations as $projectParticipation) {
            if ($projectParticipation->getProjectParticipationMembers()->count() > 0) {
                $messageThread = $this->getProjectParticipationMessageThread($projectParticipation);
                $projectParticipationMembers = $projectParticipation->getProjectParticipationMembers()->toArray();

                if ($this->isProjectOrganisatorNotAProjectParticipationMember($projectParticipationMembers, $project->getOrganizers())) {
                    // If sender not set, pick one of projectParticipationMembers as a message sender
                    $sender = $sender ?: $projectParticipationMembers[array_rand($projectParticipationMembers, 1)]->getStaff();
                    $message = (new Message($sender, $messageThread, sprintf(
                        'Message from project "%s" organizer "%s" to company "%s" member\'s',
                        $project->getTitle(),
                        $sender->getClient()->getEmail(),
                        $projectParticipation->getParticipant()->getDisplayName()
                    )));
                    $this->manager->persist($message);
                }
            }
        }
        $this->manager->flush();
    }

    /**
     * @param array      $projectParticipationMembers
     * @param Collection $projectOrganizers
     *
     * @return bool
     */
    private function isProjectOrganisatorNotAProjectParticipationMember(array $projectParticipationMembers, Collection $projectOrganizers): bool
    {
        $membersStaffIds = [];
        foreach ($projectParticipationMembers as $projectParticipationMember) {
            $membersStaffIds[] = $projectParticipationMember->getStaff()->getId();
        }

        $organizersStaffIds = [];
        foreach ($projectOrganizers as $organizer) {
                $organizersStaffIds[] = $organizer->getAddedBy()->getId();
            }

        return empty(array_intersect($membersStaffIds, $organizersStaffIds));
    }
}
