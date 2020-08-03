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
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;
use Unilend\Entity\Tranche;

class ParticipationTrancheFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use OfferFixtureTrait;

    /**
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Project[] $projects */
        $projects = $this->getReferences(ProjectFixtures::PROJECTS);
        /** @var Company[] $companies */
        $companies = $this->getReferences(CompanyFixtures::COMPANIES);
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::ADMIN);
        $staffCompany = $staff->getCompany();
        foreach ($projects as $project) {
            foreach ($project->getProjectParticipations() as $participation) {
                foreach ($project->getTranches() as $tranche) {
                    if (
                        (!$tranche->isSyndicated() && $participation->getParticipant() === $staffCompany) ||
                        ($tranche->isSyndicated() && $participation->getParticipant() !== $staffCompany)
                    ) {
                        $participationTranche = (new ProjectParticipationTranche($participation, $tranche, $staff));
                        if ($project === $this->getReference(ProjectFixtures::PROJECT_ALLOCATION)) {
                            $participationTranche->setAllocation($this->createOffer(1000000));
                        }
                        $participationTranche->setInvitationReply($this->createOffer(1000000));
                        $manager->persist($participationTranche);
                    }
                }
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
            ParticipationFixtures::class,
        ];
    }
}
