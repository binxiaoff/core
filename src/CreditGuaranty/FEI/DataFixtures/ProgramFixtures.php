<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyGroupFixture;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;

class ProgramFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const ALL_PROGRAMS = [
        self::REFERENCE_CANCELLED,
        self::REFERENCE_COMMERCIALIZED,
        self::REFERENCE_DRAFT,
        self::REFERENCE_PAUSED,
    ];

    private const REFERENCE_DRAFT          = 'draft_program';
    private const REFERENCE_CANCELLED      = 'cancelled_program';
    private const REFERENCE_COMMERCIALIZED = 'commercialized_program';
    private const REFERENCE_PAUSED         = 'paused_program';

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            CompanyGroupFixture::class,
            StaffFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $programData = [
            self::REFERENCE_DRAFT => [
                'name'                 => 'Programme en brouillon',
                'companyGroupTag'      => CompanyGroupFixture::CORPORATE,
                'funds'                => ['currency' => 'EUR', 'amount' => '100000000'],
                'addedBy'              => StaffFixtures::CASA,
                'currentStatus'        => ProgramStatus::STATUS_DRAFT,
                'cappedAt'             => \random_int(10, 40) / 100,
                'description'          => 'La description pour le programme en brouillon',
                'distributionDeadline' => new DateTimeImmutable(),
            ],
            self::REFERENCE_CANCELLED => [
                'name'            => 'Programme annulée',
                'companyGroupTag' => CompanyGroupFixture::AGRICULTURE,
                'funds'           => ['currency' => 'EUR', 'amount' => '200000000'],
                'addedBy'         => StaffFixtures::CASA,
                'currentStatus'   => ProgramStatus::STATUS_ARCHIVED,
                'cappedAt'        => \random_int(10, 40) / 100,
            ],
            self::REFERENCE_COMMERCIALIZED => [
                'name'                 => 'Programme commercialisée',
                'companyGroupTag'      => CompanyGroupFixture::AGRICULTURE,
                'funds'                => ['currency' => 'EUR', 'amount' => '300000000'],
                'addedBy'              => StaffFixtures::CASA,
                'currentStatus'        => ProgramStatus::STATUS_DISTRIBUTED,
                'cappedAt'             => \random_int(10, 40) / 100,
                'description'          => 'La description pour le programme en distribution',
                'distributionDeadline' => new DateTimeImmutable(),
                'distributionProcess'  => [
                    'Création d’un dossier emprunteur',
                    'Vérification de l’éligibilité',
                    'Réservation validée par FIN BO',
                    'Edition de l’offre de prêt et de ses annexes',
                    'Signature du client et contractualisation',
                    'Renseignement du N° de prêt et montant des réalisations',
                ],
                'guarantyDuration'        => 240,
                'guarantyCoverage'        => '0.07',
                'guarantyCost'            => ['currency' => 'EUR', 'amount' => '1000'],
                'maxFeiCredit'            => ['currency' => 'EUR', 'amount' => '20000'],
                'reservationDuration'     => 2,
                'esbCalculationActivated' => $this->faker->boolean,
                'loanReleasedOnInvoice'   => $this->faker->boolean,
            ],
            self::REFERENCE_PAUSED => [
                'name'                    => 'Programme en pause',
                'companyGroupTag'         => CompanyGroupFixture::CORPORATE,
                'funds'                   => ['currency' => 'EUR', 'amount' => '400000000'],
                'addedBy'                 => StaffFixtures::CASA,
                'currentStatus'           => ProgramStatus::STATUS_PAUSED,
                'esbCalculationActivated' => $this->faker->boolean,
                'loanReleasedOnInvoice'   => $this->faker->boolean,
            ],
        ];

        foreach ($programData as $reference => $programDatum) {
            $program = $this->buildProgram($programDatum);
            $manager->persist($program);

            /** @var Staff $addedBy */
            $addedBy = $this->getReference($programDatum['addedBy']);

            if (ProgramStatus::STATUS_PAUSED === $programDatum['currentStatus']) {
                $status = new ProgramStatus($program, ProgramStatus::STATUS_DISTRIBUTED, $addedBy);
                $manager->persist($status);
            }

            if (ProgramStatus::STATUS_DRAFT !== $programDatum['currentStatus']) {
                $status = new ProgramStatus($program, $programDatum['currentStatus'], $addedBy);
                $manager->persist($status);
            }

            $this->addReference($reference, $program);
        }

        $manager->flush();
    }

    private function buildProgram(array $programDatum): Program
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference($programDatum['addedBy']);
        /** @var CompanyGroupTag $companyGroupTag */
        $companyGroupTag = $this->getReference($programDatum['companyGroupTag']);

        $program = new Program($programDatum['name'], $companyGroupTag, new Money($programDatum['funds']['currency'], $programDatum['funds']['amount']), $addedBy);

        if (false === empty($programDatum['cappedAt'])) {
            $program->setCappedAt((string) $programDatum['cappedAt']);
        }

        if (false === empty($programDatum['description'])) {
            $program->setDescription($programDatum['description']);
        }

        if (false === empty($programDatum['distributionDeadline'])) {
            $program->setDistributionDeadline($programDatum['distributionDeadline']);
        }

        if (false === empty($programDatum['distributionProcess'])) {
            $program->setDistributionProcess($programDatum['distributionProcess']);
        }

        if (false === empty($programDatum['guarantyDuration'])) {
            $program->setGuarantyDuration($programDatum['guarantyDuration']);
        }

        if (false === empty($programDatum['guarantyCoverage'])) {
            $program->setGuarantyCoverage($programDatum['guarantyCoverage']);
        }

        if (false === empty($programDatum['guarantyCost'])) {
            $program->setGuarantyCost(new NullableMoney($programDatum['guarantyCost']['currency'], $programDatum['guarantyCost']['amount']));
        }

        if (false === empty($programDatum['maxFeiCredit'])) {
            $program->setMaxFeiCredit(new NullableMoney($programDatum['maxFeiCredit']['currency'], $programDatum['maxFeiCredit']['amount']));
        }

        if (false === empty($programDatum['reservationDuration'])) {
            $program->setReservationDuration($programDatum['reservationDuration']);
        }

        if (false === empty($programDatum['esbCalculationActivated'])) {
            $program->setEsbCalculationActivated($programDatum['esbCalculationActivated']);
        }

        if (false === empty($programDatum['loanReleasedOnInvoice'])) {
            $program->setLoanReleasedOnInvoice($programDatum['loanReleasedOnInvoice']);
        }

        $cARatingType = CARatingType::getConstList();
        $program->setRatingType($cARatingType[\array_rand($cARatingType)]);

        return $program;
    }
}
