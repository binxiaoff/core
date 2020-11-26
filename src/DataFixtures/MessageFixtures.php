<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\Message;
use Unilend\Entity\MessageThread;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\Staff;
use Unilend\Repository\StaffRepository;

class MessageFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @var StaffRepository
     */
    private StaffRepository $staffRepository;

    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

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
    private function getMessageThreadForProjectParticipation(ProjectParticipation $projectParticipation): MessageThread
    {
        if ($projectParticipation->getMessageThread() instanceof MessageThread) {
            return $projectParticipation->getMessageThread();
        }
        $messageThread = new MessageThread();
        $this->manager->persist($messageThread);
        $projectParticipation->setMessageThread($messageThread);
        $this->manager->flush();

        return $messageThread;
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
                $messageThread = $this->getMessageThreadForProjectParticipation($projectParticipation);
                $projectParticipationMembers = $projectParticipation->getProjectParticipationMembers()->toArray();

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
        $this->manager->flush();
    }
}

