<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Sluggable\Util\Urlizer;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;
use Unilend\Entity\Company;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Embeddable\NullableMoney;
use Unilend\Entity\Embeddable\RangedOfferWithFee;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;
use Unilend\Entity\Tranche;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS);
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);
        foreach ($projects as $project) {
            $manager->persist($this->createParticipation($project, $staff->getCompany(), $staff));
            foreach ($companies as $company) {
                $manager->persist($this->createParticipation($project, $company, $staff));
            }
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
            ProjectFixtures::class,
        ];
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
    public function createParticipation(
        Project $project,
        Company $company,
        Staff $staff,
        $status = ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED
    ): ProjectParticipation {
        $participation = (new ProjectParticipation($company, $project, $staff))
            ->setInterestRequest($this->createRangedOffer(1000000, 2000000))
            ->setInterestReply($this->createOffer(2000000))
            ->setInvitationRequest($this->createOfferWithFee(1000000))
            ->setInvitationReplyMode('pro-rata')
            ->setAllocationFeeRate($this->faker->randomDigit);
        $status = new ProjectParticipationStatus($participation, $status, $staff);
        $participation->setCurrentStatus($status);

        return $participation;
    }
}
