<?php

declare(strict_types=1);

namespace Unilend\Test\Syndication\Unit\Entity;

use Exception;
use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\{Company, Embeddable\Money, Staff, User};
use Unilend\Syndication\Entity\Project;

class ProjectTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testPrivilegedContactPersonConstruct()
    {
        // We need a mocked staff since we can't construct Staff by itself
        $mockedStaff = $this->getMockBuilder(Staff::class)->disableOriginalConstructor()->getMock();
        $company = new Company('Company 1', 'Company 1');
        $user = new User('contact@demo.fr');
        $user->setFirstName('Firstname');
        $user->setLastName('Lastname');
        $user->setJobFunction('JobFunction');
        $user->setPhone('05000000');
        $staff = new Staff($user, $company->getRootTeam(), $mockedStaff);

        $project = new Project($staff, 'risk1', new Money('EUR', '100'));

        // Contact should be hydrated
        $contact = $project->getPrivilegedContactPerson();

        static::assertEquals('Firstname', $contact->getFirstName());
        static::assertEquals('Lastname', $contact->getLastName());
        static::assertEquals('contact@demo.fr', $contact->getEmail());
        static::assertEquals('JobFunction', $contact->getOccupation());
        static::assertEquals('05000000', $contact->getPhone());
    }


    /**
     * @throws \Exception
     */
    public function testProjectParticipationMember()
    {
        $rootStaff = $this->getMockBuilder(Staff::class)->disableOriginalConstructor()->getMock();
        $company = new Company('Company', 'company');
        $user = new User('email@email.fr');
        $staff = new Staff($user, $company->getRootTeam(), $rootStaff);
        $user->setCurrentStaff($staff);
        $project = new Project($staff, 'risk1', new Money('EUR', '10000'));
        self::assertCount(1, $project->getProjectParticipations());
        self::assertCount(1, $project->getProjectParticipations()[0]->getProjectParticipationMembers());
        self::assertSame(
            $staff,
            $project->getProjectParticipations()[0]->getProjectParticipationMembers()[0]->getStaff()
        );
    }
}
