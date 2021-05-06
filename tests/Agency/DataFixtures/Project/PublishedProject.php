<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\DataFixtures\Project;

use Doctrine\Persistence\ObjectManager;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Agency\Entity\Project;

class PublishedProject extends DraftProject
{
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        /** @var Project $project */
        $project = $this->getReference('project:published');

        $this->publishProject($project);

        $manager->persist($project);

        $participation       = $this->createTestPrimaryParticipation($project, $this->getReference('company:qux'));
        $participationMember = new ParticipationMember($participation, $this->getReference('user:b'));
        $participation->addMember($participationMember);
        $participation->archive();

        $borrower       = $this->createTestBorrower($project, $project->getAddedBy());
        $borrowerMember = new BorrowerMember($borrower, $this->getReference('user:@'));
        $borrower->setSignatory($borrowerMember);
        $borrower->setReferent($borrowerMember);

        array_map(
            [$manager, 'persist'],
            [
                $project,
                $participation,
                $participationMember,
                $borrower,
                $borrowerMember,
            ]
        );

        $manager->flush();
    }

    protected function getName(): string
    {
        return 'published';
    }
}
