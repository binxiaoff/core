<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyGroupFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;

class ProgramFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const PROGRAM_AGRICULTURE_DRAFT          = 'program:ad';
    public const PROGRAM_AGRICULTURE_COMMERCIALIZED = 'program:ac';
    public const PROGRAM_AGRICULTURE_PAUSED         = 'program:ap';
    public const PROGRAM_AGRICULTURE_ARCHIVED       = 'program:aa';
    public const PROGRAM_CORPORATE_DRAFT            = 'program:cd';
    public const PROGRAM_CORPORATE_COMMERCIALIZED   = 'program:cc';
    public const PROGRAM_CORPORATE_PAUSED           = 'program:cp';
    public const PROGRAM_CORPORATE_ARCHIVED         = 'program:ca';

    public const ALL_PROGRAMS = [
        self::PROGRAM_AGRICULTURE_DRAFT,
        self::PROGRAM_AGRICULTURE_COMMERCIALIZED,
        self::PROGRAM_AGRICULTURE_PAUSED,
        self::PROGRAM_AGRICULTURE_ARCHIVED,
        self::PROGRAM_CORPORATE_DRAFT,
        self::PROGRAM_CORPORATE_COMMERCIALIZED,
        self::PROGRAM_CORPORATE_PAUSED,
        self::PROGRAM_CORPORATE_ARCHIVED,
    ];

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            CompanyGroupFixtures::class,
            StaffFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadData() as $reference => $programData) {
            $program = $this->buildProgram($programData);
            $manager->persist($program);

            if (ProgramStatus::STATUS_PAUSED === $programData['currentStatus']) {
                $status = new ProgramStatus($program, ProgramStatus::STATUS_DISTRIBUTED, $programData['addedBy']);
                $manager->persist($status);
            }

            if (ProgramStatus::STATUS_DRAFT !== $programData['currentStatus']) {
                $status = new ProgramStatus($program, $programData['currentStatus'], $programData['addedBy']);
                $manager->persist($status);
            }

            $this->addReference($reference, $program);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        // we create a cancelled program for only this two companyGroupTags because these are valid
        // (cf Program::isCompanyGroupTagValid)
        $companyGroupTagReferences = [CompanyGroupFixtures::AGRICULTURE, CompanyGroupFixtures::CORPORATE];

        foreach ($companyGroupTagReferences as $companyGroupTagReference) {
            $companyGroupTagName = \mb_substr(
                $companyGroupTagReference,
                \mb_strrpos($companyGroupTagReference, '_') + 1
            );

            yield \sprintf('program:%sd', \mb_substr($companyGroupTagName, 0, 1)) => [
                'name' => \sprintf(
                    'Programme %sD',
                    \mb_strtoupper(\mb_substr($companyGroupTagName, 0, 1))
                ),
                'description'          => $this->faker->sentence,
                'addedBy'              => $this->getReference(StaffFixtures::CASA),
                'companyGroupTag'      => $this->getReference($companyGroupTagReference),
                'cappedAt'             => \random_int(10, 40) / 100,
                'funds'                => new Money('EUR', (string) 100000000),
                'distributionDeadline' => new DateTimeImmutable(),
                'currentStatus'        => ProgramStatus::STATUS_DRAFT,
            ];
            yield \sprintf('program:%sc', \mb_substr($companyGroupTagName, 0, 1)) => [
                'name' => \sprintf(
                    'Programme %sC',
                    \mb_strtoupper(\mb_substr($companyGroupTagName, 0, 1))
                ),
                'description'          => $this->faker->sentence,
                'addedBy'              => $this->getReference(StaffFixtures::CASA),
                'companyGroupTag'      => $this->getReference($companyGroupTagReference),
                'cappedAt'             => \random_int(10, 40) / 100,
                'funds'                => new Money('EUR', (string) 300000000),
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
                'guarantyCost'            => '0.10',
                'reservationDuration'     => 2,
                'maxFeiCredit'            => new NullableMoney('EUR', (string) 20000),
                'esbCalculationActivated' => $this->faker->boolean,
                'loanReleasedOnInvoice'   => $this->faker->boolean,
                'currentStatus'           => ProgramStatus::STATUS_DISTRIBUTED,
            ];
            yield \sprintf('program:%sp', \mb_substr($companyGroupTagName, 0, 1)) => [
                'name' => \sprintf(
                    'Programme %sP',
                    \mb_strtoupper(\mb_substr($companyGroupTagName, 0, 1))
                ),
                'description'          => $this->faker->sentence,
                'addedBy'              => $this->getReference(StaffFixtures::CASA),
                'companyGroupTag'      => $this->getReference($companyGroupTagReference),
                'cappedAt'             => \random_int(10, 40) / 100,
                'funds'                => new Money('EUR', (string) 400000000),
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
                'guarantyCost'            => '0.10',
                'reservationDuration'     => 2,
                'maxFeiCredit'            => new NullableMoney('EUR', (string) 20000),
                'esbCalculationActivated' => $this->faker->boolean,
                'loanReleasedOnInvoice'   => $this->faker->boolean,
                'currentStatus'           => ProgramStatus::STATUS_PAUSED,
            ];
            yield \sprintf('program:%sa', \mb_substr($companyGroupTagName, 0, 1)) => [
                'name' => \sprintf(
                    'Programme %sA',
                    \mb_strtoupper(\mb_substr($companyGroupTagName, 0, 1))
                ),
                'description'     => $this->faker->sentence,
                'addedBy'         => $this->getReference(StaffFixtures::CASA),
                'companyGroupTag' => $this->getReference($companyGroupTagReference),
                'cappedAt'        => \random_int(10, 40) / 100,
                'funds'           => new Money('EUR', (string) 200000000),
                'currentStatus'   => ProgramStatus::STATUS_ARCHIVED,
            ];
        }
    }

    private function buildProgram(array $programData): Program
    {
        $program = new Program(
            $programData['name'],
            $programData['companyGroupTag'],
            $programData['funds'],
            $programData['addedBy']
        );

        if (false === empty($programData['description'])) {
            $program->setDescription($programData['description']);
        }

        if (false === empty($programData['cappedAt'])) {
            $program->setCappedAt((string) $programData['cappedAt']);
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
            $program->setGuarantyCost($programData['guarantyCost']);
        }

        if (false === empty($programData['reservationDuration'])) {
            $program->setReservationDuration($programData['reservationDuration']);
        }

        if (false === empty($programData['maxFeiCredit'])) {
            $program->setMaxFeiCredit($programData['maxFeiCredit']);
        }

        if (false === empty($programData['esbCalculationActivated'])) {
            $program->setEsbCalculationActivated($programData['esbCalculationActivated']);
        }

        if (false === empty($programData['loanReleasedOnInvoice'])) {
            $program->setLoanReleasedOnInvoice($programData['loanReleasedOnInvoice']);
        }

        $cARatingType = CARatingType::getConstList();
        $program->setRatingType($cARatingType[\array_rand($cARatingType)]);

        return $program;
    }
}
