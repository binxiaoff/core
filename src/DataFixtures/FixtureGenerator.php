<?php

namespace Unilend\DataFixtures;

use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator;
use Gedmo\Sluggable\Util\Urlizer;
use ReflectionClass;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;
use Unilend\Entity\Company;
use Unilend\Entity\CompanyStatus;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Embeddable\NullableMoney;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Embeddable\OfferWithFee;
use Unilend\Entity\Embeddable\RangedOfferWithFee;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;
use Unilend\Entity\Tranche;

class FixtureGenerator
{

    private EntityManagerInterface $em;
    private Generator $faker;

    /**
     * @param EntityManagerInterface $em
     * @param Generator              $faker
     */
    public function __construct(EntityManagerInterface $em, Generator $faker)
    {
        $this->em = $em;
        $this->faker = $faker;
    }

    /**
     * @param string $name
     *
     * @return MarketSegment
     */
    public function marketSegment(string $name): MarketSegment
    {
        $segment = (new MarketSegment())->setLabel($name);
        $this->persist($segment);

        return $segment;
    }

    /**
     * @param string    $email
     * @param Company[] $companies
     * @param ?string   $publicId
     *
     * @return Clients
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    public function user(string $email, array $companies = [], ?string $publicId = null): Clients
    {
        // We create one client
        $client = (new Clients($this->faker->company))
            ->setTitle($this->faker->company)
            ->setLastName($this->faker->lastName)
            ->setFirstName($this->faker->firstName)
            ->setPhone($this->faker->phoneNumber)
            ->setMobile($this->faker->phoneNumber)
            ->setJobFunction($this->faker->jobTitle)
            ->setEmail($email)
            ->setPlainPassword('0000');
        if ($publicId) {
            $this->forcePublicId($client, $publicId);
        }
        $status = new ClientStatus($client, ClientStatus::STATUS_CREATED);
        $this->persist($status);
        $client->setCurrentStatus($status);
        $this->em->persist($client);

        // We need to flush things before the next step cause we need the primary keys
        $this->em->flush();

        // And we set the client as the manager of this company
        foreach ($companies as $company) {
            // TODO : How to insert a staff using the ORM since new Staff need a Staff :( ?
            $sql = <<<SQL
            INSERT INTO `staff` 
                (id_company, id_client, roles, updated, added, public_id) VALUES 
                (
                    "{$company->getId()}", 
                    "{$client->getId()}", 
                    '[\"DUTY_STAFF_ADMIN\"]', 
                    '2020-01-01', '2020-01-01', 
                    "client{$client->getId()}-company{$company->getId()}-staff"
                )
        SQL;
            $this->em->getConnection()->exec($sql);
            $staffId = $this->em->getConnection()->lastInsertId();
            /** @var Staff $staff */
            $staff = $this->em->getReference(Staff::class, $staffId);
            $staffStatus = new StaffStatus($staff, StaffStatus::STATUS_ACTIVE, $staff);
            $staff->setCurrentStatus($staffStatus);
            $this->em->persist($staffStatus);
            $client->setCurrentStaff($staff);
        }

        return $client;
    }

    /**
     * @param string|null $name
     * @param string|null $shortcode
     *
     * @return Company
     *
     * @throws \Exception
     */
    public function company(string $name = null, string $shortcode = null): Company
    {
        $company = $this->persist(new Company($name ?: $this->faker->company, $name ?: $this->faker->company))
            ->setBankCode($this->faker->randomNumber(8, true))
            ->setShortCode($shortcode ?: $this->faker->regexify('[A-Za-z0-9]{10}'))
            ->setApplicableVat($this->faker->vat);
        $status = $this->persist(new CompanyStatus($company, CompanyStatus::STATUS_SIGNED));
        $company->setCurrentStatus($status);
        $this->persist($company);

        return $company;
    }

    /**
    * @param string        $title
    * @param int           $status
    * @param Staff         $staff
    * @param MarketSegment $marketSegment
    *
    * @return Project
    *
    * @throws \Exception
    */
    public function project(string $title, int $status, Staff $staff, MarketSegment $marketSegment): Project
    {
        $money = new Money('EUR', '5000000');
        $project = $this->persist(new Project($staff, 'RISK-GROUP-1', $money, $marketSegment))
            ->setTitle($title)
            ->setInternalRatingScore('B')
            ->setFundingSpecificity('FSA')
            ->setSyndicationType('')
            ->setInterestExpressionEnabled(false) // "Réponse ferme"
            ->setDescription($this->faker->sentence);
        $status = new ProjectStatus($project, $status, $staff);
        $project->setCurrentStatus($status);
        $this->forcePublicId($project, Urlizer::urlize($title));

        return $project;
    }

    /**
     * @param Project $project
     * @param string  $name
     * @param int     $amount
     *
     * @return Tranche
     */
    public function tranche(Project $project, string $name, int $amount): Tranche
    {
        $tranche = (new Tranche(
            $project,
            new Money('EUR', (string) $amount),
            $name,
            $this->faker->randomDigit,
            'constant_capital',
            'stand_by',
            $this->faker->hexColor
        ));
        $project->addTranche($tranche);
        $this->persist($tranche);

        return $tranche;
    }

    /**
     * @param Project $project
     * @param Company $company
     * @param Staff   $staff
     * @param int     $status
     *
     * @return ProjectParticipation
     *
     * @throws \Exception
     */
    public function participation(
        Project $project,
        Company $company,
        Staff $staff,
        $status = ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED
    ): ProjectParticipation {
        $participation = (new ProjectParticipation($company, $project, $staff))
            ->setInterestRequest($this->rangedOffer(1000000, 2000000))
            ->setInterestReply($this->offer(2000000))
            ->setInvitationRequest($this->offerWithFee(1000000))
            ->setInvitationReplyMode('pro-rata')
            ->setAllocationFeeRate($this->faker->randomDigit);
        $status = new ProjectParticipationStatus($participation, $status, $staff);
        $participation->setCurrentStatus($status);
        $this->persist($status);
        $this->persist($participation);

        return $participation;
    }

    /**
     * @param int|null $value
     *
     * @return Money
     */
    public function money(?int $value = null): Money
    {
        return new Money('EUR', $value ?: $this->faker->numberBetween(1000000, 5000000));
    }

    /**
     * @param int|null $value
     *
     * @return NullableMoney
     */
    public function nullableMoney(?int $value = null): NullableMoney
    {
        return new NullableMoney('EUR', $value ?: $this->faker->numberBetween(1000000, 5000000));
    }

    /**
     * @param int|null $value
     *
     * @return Offer
     */
    public function offer(?int $value = null): Offer
    {
        return new Offer($this->nullableMoney($value));
    }

    /**
     * @param int $min
     * @param int $max
     * @param int $rate
     *
     * @return RangedOfferWithFee
     */
    public function rangedOffer(int $min, int $max, int $rate = 3): RangedOfferWithFee
    {
        return new RangedOfferWithFee(
            $this->nullableMoney($min),
            $rate,
            $this->nullableMoney($max)
        );
    }

    /**
     * @param int $value
     * @param int $rate
     *
     * @return OfferWithFee
     */
    public function offerWithFee(int $value, int $rate = 3): OfferWithFee
    {
        return new OfferWithFee($this->nullableMoney($value), $rate);
    }

    /**
     * @param ProjectParticipation $participation
     * @param Tranche              $tranche
     * @param Staff                $staff
     * @param int|null             $allocation
     * @param int|null             $reply
     *
     * @return ProjectParticipationTranche
     *
     * @throws \Exception
     */
    public function participationTranche(
        ProjectParticipation $participation,
        Tranche $tranche,
        Staff $staff,
        ?int $allocation = null,
        ?int $reply = null
    ): ProjectParticipationTranche {
        $projectParticipationTranche = new ProjectParticipationTranche($participation, $tranche, $staff);
        if ($allocation) {
            $projectParticipationTranche->setAllocation($this->offer($allocation));
        }
        if ($reply) {
            $projectParticipationTranche->setInvitationReply($this->offer($reply));
        }
        $this->persist($projectParticipationTranche);

        return $projectParticipationTranche;
    }

    /**
     * @param $entity
     * @param string $name
     *
     * @throws \ReflectionException
     */
    private function forcePublicId($entity, string $name): void
    {
        $ref = new ReflectionClass(get_class($entity));
        $property = $ref->getProperty("publicId");
        $property->setAccessible(true);
        $property->setValue($entity, $name);
    }

    /**
     * Persist with chaining ability
     *
     * @template T
     *
     * @param T $entity
     *
     * @return T
     */
    private function persist(object $entity)
    {
        $this->em->persist($entity);

        return $entity;
    }
}
