<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\DataFixtures\Projects;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectOrganizer;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Test\Core\DataFixtures\Companies\BasicCompanyFixtures;
use KLS\Test\Core\DataFixtures\Companies\ExampleCompanyFixtures;

class BasicCompanyArrangerProjectFixture extends AbstractProjectFixtures
{
    public function getDependencies(): array
    {
        return [
            BasicCompanyFixtures::class,
            ExampleCompanyFixtures::class,
        ];
    }

    public function getProjectOrganizerRoles(Company $company): iterable
    {
        switch ($company->getPublicId()) {
            case 'company:example':
                return [ProjectOrganizer::DUTY_PROJECT_ORGANIZER_AGENT];

            default:
                return [];
        }
    }

    protected static function getName(): string
    {
        return 'basic_arranger';
    }

    protected function getGlobalFundingMoney(): Money
    {
        return new Money('EUR', '2000000');
    }

    protected function getSubmitterStaff(): Staff
    {
        return $this->getReference('staff_company:basic_user-1');
    }

    /**
     * @throws Exception
     *
     * @return iterable|ProjectParticipation[]
     */
    protected function getAdditionalProjectParticipations(Project $project): iterable
    {
        return [
            new ProjectParticipation($this->getReference('company:example'), $project, $this->getSubmitterStaff()),
        ];
    }

    /**
     * @throws Exception
     */
    protected function getProjectParticipationMembers(ProjectParticipation $projectParticipation): iterable
    {
        $company = $projectParticipation->getParticipant();

        switch ($company->getPublicId()) {
            case 'company:basic':
                return [
                    new ProjectParticipationMember(
                        $projectParticipation,
                        $this->getReference('staff_company:basic_user-9'),
                        $this->getSubmitterStaff()
                    ),
                    new ProjectParticipationMember(
                        $projectParticipation,
                        $this->getReference('staff_company:basic_user-4'),
                        $this->getSubmitterStaff()
                    ),
                    new ProjectParticipationMember(
                        $projectParticipation,
                        $this->getReference('staff_company:basic_user-11'),
                        $this->getSubmitterStaff()
                    ),
                ];

            case 'company:example':
                return [
                    new ProjectParticipationMember(
                        $projectParticipation,
                        $this->getReference('staff_company:example_user-9'),
                        $this->getSubmitterStaff()
                    ),
                ];
        }

        return parent::getProjectParticipationMembers($projectParticipation);
    }
}
