<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\DataFixtures\Project;

use Doctrine\Persistence\ObjectManager;
use KLS\Syndication\Agency\Entity\BorrowerMember;
use KLS\Syndication\Agency\Entity\ParticipationMember;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Test\Core\DataFixtures\Companies\LoxCompanyFixtures;
use KLS\Test\Core\DataFixtures\Companies\QuxCompanyFixtures;

class PublishedProjectFixtures extends DraftProjectFixtures
{
    public function getDependencies(): array
    {
        return \array_merge(parent::getDependencies(), [
            QuxCompanyFixtures::class,
            LoxCompanyFixtures::class,
        ]);
    }

    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        /** @var Project $project */
        $project = $this->getReference(static::getReferenceName());

        $this->publishProject($project);

        $manager->persist($project);

        $quxParticipation    = $this->createTestPrimaryParticipation($project, $this->getReference('company:qux'));
        $participationMember = new ParticipationMember($quxParticipation, $this->getReference('user-b'));
        $quxParticipation->addMember($participationMember);
        $quxParticipation->archive();

        $loxParticipation    = $this->createTestSecondaryParticipation($project, $this->getReference('company:lox'));
        $participationMember = new ParticipationMember($loxParticipation, $this->getReference('user-b'));
        $loxParticipation->addMember($participationMember);
        $loxParticipation->archive();

        $borrower       = $this->createTestBorrower($project);
        $borrowerMember = new BorrowerMember($borrower, $this->getReference('user-@'));

        \array_map(
            [$manager, 'persist'],
            [
                $project,
                $quxParticipation,
                $loxParticipation,
                $participationMember,
                $borrower,
                $borrowerMember,
            ]
        );

        $manager->flush();
    }

    protected static function getName(): string
    {
        return 'published';
    }
}
