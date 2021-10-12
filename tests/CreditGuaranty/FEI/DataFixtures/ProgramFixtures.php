<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use KLS\Test\Core\DataFixtures\CompanyGroups\FooCompanyGroupFixtures;

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
            'addedBy'              => 'staff_company:bar_user-a',
            'companyGroupTag'      => 'companyGroup:foo_tag:agriculture',
            'funds'                => ['currency' => 'EUR', 'amount' => '300000000'],
            'currentStatus'        => ProgramStatus::STATUS_DISTRIBUTED,
            'cappedAt'             => '0.15',
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
            'guarantyCost'            => '0.10',
            'maxFeiCredit'            => ['currency' => 'EUR', 'amount' => '20000'],
            'reservationDuration'     => 2,
            'esbCalculationActivated' => true,
            'loanReleasedOnInvoice'   => false,
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
            $program->setGuarantyCost($programData['guarantyCost']);
        }

        if (false === empty($programData['maxFeiCredit'])) {
            $program->setMaxFeiCredit(new NullableMoney($programData['maxFeiCredit']['currency'], $programData['maxFeiCredit']['amount']));
        }

        if (false === empty($programData['reservationDuration'])) {
            $program->setReservationDuration($programData['reservationDuration']);
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
