<?php

declare(strict_types=1);

namespace Unilend\Test\Syndication\DataFixtures\Projects;

use Exception;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Test\Core\DataFixtures\Companies\BasicCompanyFixture;
use Unilend\Test\Core\DataFixtures\Companies\ExampleCompanyFixture;

class ExampleCompanyArrangerProjectFixture extends AbstractProjectFixture
{
    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [BasicCompanyFixture::class, ExampleCompanyFixture::class];
    }
    /**
     * @inheritDoc
     */
    protected function getGlobalFundingMoney(): Money
    {
        return new Money('EUR', '2000000');
    }

    /**
     * @inheritDoc
     */
    protected static function getName(): string
    {
        return 'example_arranger';
    }

    /**
     * @return Staff
     */
    protected function getSubmitterStaff(): Staff
    {
        return $this->getReference('staff_company/example_user/20');
    }

    /**
     * @param Project $project
     *
     * @return iterable|ProjectParticipation[]
     *
     * @throws Exception
     */
    protected function getAdditionalProjectParticipations(Project $project)
    {
        return [
            new ProjectParticipation($this->getReference('company/basic'), $project, $this->getSubmitterStaff()),
        ];
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return iterable
     *
     * @throws Exception
     */
    protected function getProjectParticipationMembers(ProjectParticipation $projectParticipation): iterable
    {
        $company = $projectParticipation->getParticipant();

        switch ($company->getPublicId()) {
            case 'company/basic':
                return [
                    (new ProjectParticipationMember($projectParticipation, $this->getReference('staff_company/basic_user/3'), $this->getSubmitterStaff()))
                        ->setPermissions(ProjectParticipationMember::PERMISSION_WRITE),
                    (new ProjectParticipationMember($projectParticipation, $this->getReference('staff_company/basic_user/8'), $this->getSubmitterStaff()))
                        ->setPermissions(ProjectParticipationMember::PERMISSION_WRITE),
                    new ProjectParticipationMember($projectParticipation, $this->getReference('staff_company/basic_user/4'), $this->getSubmitterStaff()),
                    new ProjectParticipationMember($projectParticipation, $this->getReference('staff_company/basic_user/10'), $this->getSubmitterStaff()),
                ];
            case 'company/example':
                return [
                    new ProjectParticipationMember($projectParticipation, $this->getReference('staff_company/example_user/9'), $this->getSubmitterStaff()),
                    new ProjectParticipationMember($projectParticipation, $this->getReference('staff_company/example_user/10'), $this->getSubmitterStaff()),
                ];
        }

        return parent::getProjectParticipationMembers($projectParticipation);
    }
}
