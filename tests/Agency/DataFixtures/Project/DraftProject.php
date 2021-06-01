<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\DataFixtures\Project;

use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Test\Core\DataFixtures\Companies\BarCompanyFixtures;
use Unilend\Test\Core\DataFixtures\Companies\FooCompanyFixtures;
use Unilend\Test\Core\DataFixtures\Companies\QuxCompanyFixtures;

class DraftProject extends AbstractProjectFixtures
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $staff = $this->getReference('staff_company:foo_user:b');

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
        $barParticipation->addMember(new ParticipationMember($barParticipation, $this->getReference('user:b')));

        $tuxParticipation = $this->createTestSecondaryParticipation($project, $this->getReference('company:tux'));
        $tuxParticipation->addMember(new ParticipationMember($tuxParticipation, $this->getReference('user:b')));

        $borrower = $this->createTestBorrower($project);

        array_map(
            [$manager, 'persist'],
            [
                $project,
                $barParticipation,
                $tuxParticipation,
                $borrower,
                new BorrowerMember($borrower, $this->getReference('user:+')),
                new BorrowerMember($borrower, $this->getReference('user:d')),
                new ParticipationMember($project->getAgentParticipation(), $this->getReference('user:c')),
            ]
        );

        $this->setReference(static::getReferenceName(), $project);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            FooCompanyFixtures::class,
            BarCompanyFixtures::class,
            QuxCompanyFixtures::class,
        ];
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
