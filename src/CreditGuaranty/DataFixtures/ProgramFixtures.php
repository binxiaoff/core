<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\{AbstractFixtures, MarketSegmentFixtures, StaffFixtures};
use Unilend\Core\Entity\{Embeddable\Money, Embeddable\NullableMoney, MarketSegment, Staff};
use Unilend\CreditGuaranty\Entity\{Program, ProgramStatus};

class ProgramFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const REFERENCE_DRAFT          = 'draft_program';
    public const REFERENCE_CANCELLED      = 'cancelled_program';
    public const REFERENCE_COMMERCIALIZED = 'commercialized_program';
    public const REFERENCE_PAUSED         = 'paused_program';

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $programData = [
            self::REFERENCE_DRAFT          => [
                'name'          => 'Programme en brouillon',
                'marketSegment' => MarketSegmentFixtures::SEGMENT3,
                'funds'         => ['currency' => 'EUR', 'amount' => '100000000'],
                'addedBy'       => StaffFixtures::CASA,
                'currentStatus' => ProgramStatus::STATUS_DRAFT,
                'cappedAt'      => ['currency' => 'EUR', 'amount' => '100000'],
                'description'   => 'La description pour la pogramme en brouillon',
                'distributionDeadline' => new \DateTimeImmutable(),
            ],
            self::REFERENCE_CANCELLED      => [
                'name'          => 'Programme annulée',
                'marketSegment' => MarketSegmentFixtures::SEGMENT6,
                'funds'         => ['currency' => 'EUR', 'amount' => '200000000'],
                'addedBy'       => StaffFixtures::CASA,
                'currentStatus' => ProgramStatus::STATUS_CANCELLED,
                'cappedAt'      => ['currency' => 'EUR', 'amount' => '100000'],
            ],
            self::REFERENCE_COMMERCIALIZED => [
                'name'                 => 'Programme commercialisée',
                'marketSegment'        => MarketSegmentFixtures::SEGMENT3,
                'funds'                => ['currency' => 'EUR', 'amount' => '300000000'],
                'addedBy'              => StaffFixtures::CASA,
                'currentStatus'        => ProgramStatus::STATUS_COMMERCIALIZED,
                'cappedAt'             => ['currency' => 'EUR', 'amount' => '100000'],
                'description'          => 'La description pour la pogramme en brouillon',
                'distributionDeadline' => new \DateTimeImmutable(),
                'distributionProcess'  => [
                    'Création d’un dossier emprunteur',
                    'Vérification de l’éligibilité',
                    'Réservation validée par FIN BO',
                    'Edition de l’offre de prêt et de ses annexes',
                    'Signature du client et contractualisation',
                    'Renseignement du N° de prêt et montant des réalisations',
                ],
                'guarantyDuration'     => 240,
                'guarantyCoverage'     => '0.07',
                'guarantyCost'         => ['currency' => 'EUR', 'amount' => '1000'],
            ],
            self::REFERENCE_PAUSED         => [
                'name'          => 'Programme en pause',
                'marketSegment' => MarketSegmentFixtures::SEGMENT6,
                'funds'         => ['currency' => 'EUR', 'amount' => '400000000'],
                'addedBy'       => StaffFixtures::CASA,
                'currentStatus' => ProgramStatus::STATUS_PAUSED,
            ],
        ];

        foreach ($programData as $reference => $programDatum) {
            $program = $this->buildProgram($programDatum);
            $manager->persist($program);

            $addedBy = $this->getReference($programDatum['addedBy']);
            if (ProgramStatus::STATUS_PAUSED === $programDatum['currentStatus']) {
                $status = new ProgramStatus($program, ProgramStatus::STATUS_COMMERCIALIZED, $addedBy);
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

    /**
     * @param array $programDatum
     *
     * @return Program
     */
    public function buildProgram(array $programDatum): Program
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference($programDatum['addedBy']);
        /** @var MarketSegment $marketSegment */
        $marketSegment = $this->getReference($programDatum['marketSegment']);
        $program       = new Program($programDatum['name'], $marketSegment, new Money($programDatum['funds']['currency'], $programDatum['funds']['amount']), $addedBy);

        if (false === empty($programDatum['cappedAt'])) {
            $program->setCappedAt(new NullableMoney($programDatum['cappedAt']['currency'], $programDatum['cappedAt']['amount']));
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

        return $program;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            MarketSegmentFixtures::class,
            StaffFixtures::class,
        ];
    }
}
