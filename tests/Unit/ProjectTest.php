<?php

namespace Unilend\Test\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\{Clients, Company, Embeddable\Money, MarketSegment, Staff};
use Unilend\Entity\Project;

class ProjectTest extends TestCase
{

    /**
     * @throws \Exception
     */
    public function testProjectParticipationMember()
    {
        $rootStaff = $this->getMockBuilder(Staff::class)->disableOriginalConstructor()->getMock();
        $company = new Company('Company', 'company');
        $client = new Clients('email@email.fr');
        $staff = new Staff($company, $client, $rootStaff);
        $client->setCurrentStaff($staff);
        $project = new Project($staff, 'risk1', new Money('EUR', '10000'), new MarketSegment());
        $this->assertCount(1, $project->getProjectParticipations());
        $this->assertCount(1, $project->getProjectParticipations()[0]->getProjectParticipationMembers());
        $this->assertSame(
            $staff,
            $project->getProjectParticipations()[0]->getProjectParticipationMembers()[0]->getStaff()
        );
    }
}
