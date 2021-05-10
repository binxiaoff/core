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
    public function load(ObjectManager $manager)
    {
        $staff = $this->getReference('staff_company:foo_user:b');

        $this->loginStaff($staff);

        $project = new Project(
            $staff,
            $this->getName(),
            'riskGroupName',
            new Money('EUR', '200000000'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $this->setPublicId($project, $this->getName());

        $barParticipation = $this->createTestPrimaryParticipation($project, $this->getReference('company:bar'));
        $barParticipation->addMember(new ParticipationMember($barParticipation, $this->getReference('user:b')));

        $tuxParticipation = $this->createTestSecondaryParticipation($project, $this->getReference('company:tux'));
        $tuxParticipation->addMember(new ParticipationMember($tuxParticipation, $this->getReference('user:b')));

        $borrower = $this->createTestBorrower($project, $staff);

        $borrower->setReferent(new BorrowerMember($borrower, $this->getReference('user:+')));
        $borrower->setSignatory(new BorrowerMember($borrower, $this->getReference('user:d')));

        array_map(
            [$manager, 'persist'],
            [
                $project,
                $barParticipation,
                $tuxParticipation,
                $borrower,
                new ParticipationMember($project->getAgentParticipation(), $this->getReference('user:c')),
            ]
        );

        $this->setReference('project:' . $this->getName(), $project);

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

    protected function getName()
    {
        return 'draft';
    }
}
