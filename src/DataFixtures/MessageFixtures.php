<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\Company;
use Unilend\Entity\Staff;
use Unilend\Entity\Project;
use Unilend\Entity\Message;
use Unilend\Entity\MessageStatus;
use Unilend\Entity\MessageThread;
use Unilend\Entity\ProjectParticipation;
use Unilend\Repository\StaffRepository;

class MessageFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @var StaffRepository
     */
    private $staffRepository;

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
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $projectsWithParticipations = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION);

        foreach ($projectsWithParticipations as $projectWithParticipation) {
            if ($projectWithParticipation->getArranger()) {
                $staffSender = $this->staffRepository->findOneByClientEmailAndCompany($projectWithParticipation->getSubmitterClient()->getEmail(), $projectWithParticipation->getArranger());

                // Create a projectOrganizer.staff message to each projectParticipationMember.staff
                $projectParticipationsWithMembers = $this->createMessagesForProjectParticipations($projectWithParticipation, $projectWithParticipation->getProjectParticipations(), $staffSender);

                // Create a random projectParticipation.member.staff message to each projectParticipationMember.staff
                $this->createMessagesForProjectParticipations($projectWithParticipation, $projectParticipationsWithMembers);
            }
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
     * @param Collection $projectParticipations
     * @param Staff|null $sender
     *
     * @return Collection
     */
    private function createMessagesForProjectParticipations(Project $project, Collection $projectParticipations, Staff $sender = null): Collection
    {
        $projectParticipationsWithMembers = new ArrayCollection();
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

                // Add an message status for each recipient projectParticipationMember
                foreach ($projectParticipation->getProjectParticipationMembers() as $projectParticipationMember) {
                    // Avoid to send to sender
                    if ($sender !== $projectParticipationMember->getStaff()) {
                        $messagePossibleStatuses = MessageStatus::getPossibleStatuses();
                        $messageStatus = (new MessageStatus($messagePossibleStatuses[array_rand($messagePossibleStatuses)], $message, $projectParticipationMember->getStaff()));
                        $this->manager->persist($messageStatus);
                    }
                }
                $projectParticipationsWithMembers->add($projectParticipation);
            }
        }
        $this->manager->flush();

        return $projectParticipationsWithMembers;
    }
}
