<?php

declare(strict_types=1);

namespace KLS\Test\Agency\DataFixtures\Project;

use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Agency\Entity\BorrowerMember;
use KLS\Agency\Entity\ParticipationMember;
use KLS\Agency\Entity\Project;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Test\Core\DataFixtures\Companies\BarCompanyFixtures;
use KLS\Test\Core\DataFixtures\Companies\FooCompanyFixtures;
use KLS\Test\Core\DataFixtures\Companies\QuxCompanyFixtures;

class DraftProjectFixtures extends AbstractProjectFixtures
{
    public function getDependencies(): array
    {
        return [
            FooCompanyFixtures::class,
            BarCompanyFixtures::class,
            QuxCompanyFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $staff = $this->getReference('staff_company:foo_user-b');

        $this->loginStaff($staff);

        $project = new Project(
            $staff,
            static::getName(),
            'riskGroupName',
            new Money('EUR', '200000000'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $this->setPublicId($project, static::getName());

        $barParticipation = $this->createTestPrimaryParticipation($project, $this->getReference('company:bar'));
        $barParticipation->addMember(new ParticipationMember($barParticipation, $this->getReference('user-b')));

        $tuxParticipation = $this->createTestSecondaryParticipation($project, $this->getReference('company:tux'));
        $tuxParticipation->addMember(new ParticipationMember($tuxParticipation, $this->getReference('user-b')));

        $borrower = $this->createTestBorrower($project);

        \array_map(
            [$manager, 'persist'],
            [
                $project,
                $barParticipation,
                $tuxParticipation,
                $borrower,
                new BorrowerMember($borrower, $this->getReference('user-+')),
                new BorrowerMember($borrower, $this->getReference('user-d')),
                new ParticipationMember($project->getAgentParticipation(), $this->getReference('user-c')),
            ]
        );

        $this->setReference(static::getReferenceName(), $project);

        $manager->flush();
    }

    protected static function getName(): string
    {
        return 'draft';
    }

    protected static function getReferenceName(): string
    {
        return 'project:' . static::getName();
    }
}
