<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\Staff;
use Unilend\Entity\Project;
use Unilend\Entity\Message;
use Unilend\Entity\MessageStatus;
use Unilend\Entity\MessageThread;

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
        $projectsWithParticipations = $this->getReferences(ProjectFixtures::PROJECTS_WITH_PARTICIPATION);

        foreach ($projectsWithParticipations as $key => $projectWithParticipation) {
            if ($projectWithParticipation->getOrganizers()->count() > 0) {
                $projectOrganizerStaff = $projectWithParticipation->getOrganizers()->first()->getAddedBy();
                $projectParticipations = $projectWithParticipation->getProjectParticipations();
                // Create a projectOrganizer.staff message to each projectParticipationMember.staff
                $this->createMessagesByProjectParticipation($projectOrganizerStaff, $projectWithParticipation, $projectParticipations, $manager);
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
     * @param Staff         $projectOrganizerStaff
     * @param Project       $project
     * @param Collection    $projectParticipations
     * @param ObjectManager $manager
     */
    private function createMessagesByProjectParticipation(Staff $projectOrganizerStaff, Project $project, Collection $projectParticipations, ObjectManager $manager)
    {
        foreach ($projectParticipations as $i => $projectParticipation) {
            if ($projectParticipation->getProjectParticipationMembers()->count() > 0) {
                $messageThread = $this->createMessageThreadForProjectParticipation($projectParticipation, $manager);

                $message = (new Message($projectOrganizerStaff, $messageThread, sprintf(
                    'Message from project "%s" organizer "%s" to company "%s" member\'s',
                    $project->getTitle(),
                    $projectOrganizerStaff->getClient()->getEmail(),
                    $projectParticipation->getParticipant()->getDisplayName()
                )));
                $manager->persist($message);
                // Add an message status for each recipient projectParticipationMember
                foreach ($projectParticipation->getProjectParticipationMembers() as $projectParticipationMember) {
                    // Avoid project organisator
                    if ($projectParticipationMember->getStaff() !== $projectOrganizerStaff) {
                        $messageStatus = (new MessageStatus(MessageStatus::STATUS_UNREAD, $message, $projectParticipationMember->getStaff()));
                        $manager->persist($messageStatus);
                    }
                }
                $projectParticipation->setMessageThread($messageThread);
            }
        }
        $manager->flush();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param ObjectManager        $manager
     *
     * @return MessageThread
     */
    private function createMessageThreadForProjectParticipation(ProjectParticipation $projectParticipation, ObjectManager $manager)
    {
        $messageThread = (new MessageThread())->setPublicId();
        $manager->persist($messageThread);
        $manager->flush();

        return $messageThread;
    }
}
