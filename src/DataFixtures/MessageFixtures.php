<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Unilend\Entity\Staff;
use Unilend\Entity\Project;
use Unilend\Entity\Message;
use Unilend\Entity\MessageStatus;
use Unilend\Entity\MessageThread;
use Unilend\Entity\ProjectParticipation;

class MessageFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $projectsWithParticipations = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION);

        foreach ($projectsWithParticipations as $key => $projectWithParticipation) {
            if ($projectWithParticipation->getOrganizers()->count() > 0) {
                // Create a projectOrganizer.staff message to each projectParticipationMember.staff
                $projectParticipationsWithMembers = $this->createMessagesForProjectParticipations($projectWithParticipation, $projectWithParticipation->getProjectParticipations(), $projectWithParticipation->getOrganizers()->first()->getAddedBy());

                // Create a random projectParticipation.member.staff message to each projectParticipationMember.staff
                $this->createMessagesForProjectParticipations($projectWithParticipation, $projectParticipationsWithMembers);
            }
        }
    }

    /**
     * @return string[]
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
    private function getMessageThreadForProjectParticipation(ProjectParticipation $projectParticipation)
    {
        if ($projectParticipation->getMessageThread() instanceof MessageThread) {
            return $projectParticipation->getMessageThread();
        }
        $messageThread = (new MessageThread())->setPublicId();
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
     * @return ArrayCollection
     */
    private function createMessagesForProjectParticipations(Project $project, Collection $projectParticipations, Staff $sender = null)
    {
        $projectParticipationsWithMembers = new ArrayCollection();
        foreach ($projectParticipations as $i => $projectParticipation) {
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

