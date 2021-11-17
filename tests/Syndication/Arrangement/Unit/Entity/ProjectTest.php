<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Entity;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Arrangement\Entity\Project;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ProjectTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testPrivilegedContactPersonConstruct(): void
    {
        // We need a mocked staff since we can't construct Staff by itself
        $mockedStaff = $this->getMockBuilder(Staff::class)->disableOriginalConstructor()->getMock();
        $company     = new Company('Company 1', '850890666');
        $user        = new User('contact@demo.fr');
        $user->setFirstName('Firstname');
        $user->setLastName('Lastname');
        $user->setJobFunction('JobFunction');
        $user->setPhone('05000000');
        $staff = new Staff($user, $company->getRootTeam(), $mockedStaff);

        $project = new Project($staff, 'risk1', new Money('EUR', '100'));

        // Contact should be hydrated
        $contact = $project->getPrivilegedContactPerson();

        static::assertSame('Firstname', $contact->getFirstName());
        static::assertSame('Lastname', $contact->getLastName());
        static::assertSame('contact@demo.fr', $contact->getEmail());
        static::assertSame('JobFunction', $contact->getOccupation());
        static::assertSame('05000000', $contact->getPhone());
    }

    /**
     * @throws \Exception
     */
    public function testProjectParticipationMember(): void
    {
        $rootStaff = $this->getMockBuilder(Staff::class)->disableOriginalConstructor()->getMock();
        $company   = new Company('Company', '850890666');
        $user      = new User('email@email.fr');
        $staff     = new Staff($user, $company->getRootTeam(), $rootStaff);
        $user->setCurrentStaff($staff);
        $project = new Project($staff, 'risk1', new Money('EUR', '10000'));
        static::assertCount(1, $project->getProjectParticipations());
        static::assertCount(1, $project->getProjectParticipations()[0]->getProjectParticipationMembers());
        static::assertSame(
            $staff,
            $project->getProjectParticipations()[0]->getProjectParticipationMembers()[0]->getStaff()
        );
    }
}
