<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Staff;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramStatus;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;
use Unilend\Test\Core\DataFixtures\CompanyGroups\FooCompanyGroupFixtures;

class ProgramFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const REFERENCE_COMMERCIALIZED = 'program:commercialized';

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            FooCompanyGroupFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadData() as $reference => $programData) {
            $program = $this->buildProgram($programData);

            $this->setPublicId($program, $reference);
            $this->addReference($reference, $program);

            $manager->persist($program);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        yield self::REFERENCE_COMMERCIALIZED => [
            'name'                 => 'Programme commercialisée',
            'addedBy'              => 'staff_company:foo_user-a',
            'companyGroupTag'      => 'companyGroup:foo_tag:agriculture',
            'funds'                => ['currency' => 'EUR', 'amount' => '300000000'],
            'currentStatus'        => ProgramStatus::STATUS_DISTRIBUTED,
            'cappedAt'             => random_int(10, 40) / 100,
            'description'          => 'La description pour le programme en distribution',
            'distributionDeadline' => new \DateTimeImmutable(),
            'distributionProcess'  => [
                'Création d’un dossier emprunteur',
                'Vérification de l’éligibilité',
                'Réservation validée par FIN BO',
                'Edition de l’offre de prêt et de ses annexes',
                'Signature du client et contractualisation',
                'Renseignement du N° de prêt et montant des réalisations',
            ],
            'guarantyDuration'    => 240,
            'guarantyCoverage'    => '0.07',
            'guarantyCost'        => ['currency' => 'EUR', 'amount' => '1000'],
            'reservationDuration' => 2,
        ];
    }

    private function buildProgram(array $programData): Program
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference($programData['addedBy']);
        /** @var CompanyGroupTag $companyGroupTag */
        $companyGroupTag = $this->getReference($programData['companyGroupTag']);

        $program = new Program($programData['name'], $companyGroupTag, new Money($programData['funds']['currency'], $programData['funds']['amount']), $addedBy);

        if (false === empty($programData['cappedAt'])) {
            $program->setCappedAt((string) $programData['cappedAt']);
        }

        if (false === empty($programData['description'])) {
            $program->setDescription($programData['description']);
        }

        if (false === empty($programData['distributionDeadline'])) {
            $program->setDistributionDeadline($programData['distributionDeadline']);
        }

        if (false === empty($programData['distributionProcess'])) {
            $program->setDistributionProcess($programData['distributionProcess']);
        }

        if (false === empty($programData['guarantyDuration'])) {
            $program->setGuarantyDuration($programData['guarantyDuration']);
        }

        if (false === empty($programData['guarantyCoverage'])) {
            $program->setGuarantyCoverage($programData['guarantyCoverage']);
        }

        if (false === empty($programData['guarantyCost'])) {
            $program->setGuarantyCost(new NullableMoney($programData['guarantyCost']['currency'], $programData['guarantyCost']['amount']));
        }

        if (false === empty($programData['reservationDuration'])) {
            $program->setReservationDuration($programData['reservationDuration']);
        }

        $cARatingType = CARatingType::getConstList();
        $program->setRatingType($cARatingType[array_rand($cARatingType)]);

        return $program;
    }
}
