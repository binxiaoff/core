<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\CompanyGroupFixture;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\CompanyGroupTagRepository;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramStatus;

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

    private CompanyGroupTagRepository $companyGroupTagRepository;

    public function __construct(TokenStorageInterface $tokenStorage, CompanyGroupTagRepository $companyGroupTagRepository)
    {
        parent::__construct($tokenStorage);
        $this->companyGroupTagRepository = $companyGroupTagRepository;
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $programData = [
            self::REFERENCE_DRAFT => [
                'name'                 => 'Programme en brouillon',
                'companyGroupTag'      => Program::COMPANY_GROUP_TAG_CORPORATE,
                'funds'                => ['currency' => 'EUR', 'amount' => '100000000'],
                'addedBy'              => StaffFixtures::CASA,
                'currentStatus'        => ProgramStatus::STATUS_DRAFT,
                'cappedAt'             => random_int(10, 40) / 100,
                'description'          => 'La description pour le programme en brouillon',
                'distributionDeadline' => new \DateTimeImmutable(),
            ],
            self::REFERENCE_CANCELLED => [
                'name'            => 'Programme annulée',
                'companyGroupTag' => Program::COMPANY_GROUP_TAG_AGRICULTURE,
                'funds'           => ['currency' => 'EUR', 'amount' => '200000000'],
                'addedBy'         => StaffFixtures::CASA,
                'currentStatus'   => ProgramStatus::STATUS_CANCELLED,
                'cappedAt'        => random_int(10, 40) / 100,
            ],
            self::REFERENCE_COMMERCIALIZED => [
                'name'                 => 'Programme commercialisée',
                'companyGroupTag'      => Program::COMPANY_GROUP_TAG_AGRICULTURE,
                'funds'                => ['currency' => 'EUR', 'amount' => '300000000'],
                'addedBy'              => StaffFixtures::CASA,
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
                'guarantyDuration' => 240,
                'guarantyCoverage' => '0.07',
                'guarantyCost'     => ['currency' => 'EUR', 'amount' => '1000'],
            ],
            self::REFERENCE_PAUSED => [
                'name'            => 'Programme en pause',
                'companyGroupTag' => Program::COMPANY_GROUP_TAG_CORPORATE,
                'funds'           => ['currency' => 'EUR', 'amount' => '400000000'],
                'addedBy'         => StaffFixtures::CASA,
                'currentStatus'   => ProgramStatus::STATUS_PAUSED,
            ],
        ];

        foreach ($programData as $reference => $programDatum) {
            $program = $this->buildProgram($programDatum, $manager);
            $manager->persist($program);

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

    public function buildProgram(array $programDatum): Program
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference($programDatum['addedBy']);
        // todo: put the references on the compnayGroupTag and use them here
        $companyGroupTag = $this->companyGroupTagRepository->findOneBy(['code' => $programDatum['companyGroupTag']]);
        $program         = new Program($programDatum['name'], $companyGroupTag, new Money($programDatum['funds']['currency'], $programDatum['funds']['amount']), $addedBy);

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

        $cARatingType = CARatingType::getConstList();
        $program->setRatingType($cARatingType[array_rand($cARatingType)]);

        return $program;
    }

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
}
