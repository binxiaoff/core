<?php

namespace Unilend\Test\Unit\Service\File;

use Exception;
use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\{Clients, Company, Embeddable\Money, MarketSegment, Staff};
use Unilend\Entity\Project;

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
        $client = new Clients('contact@demo.fr');
        $client->setFirstName('Firstname');
        $client->setLastName('Lastname');
        $client->setJobFunction('JobFunction');
        $client->setPhone('05000000');
        $staff = new Staff($company, $client, $mockedStaff);

        $project = new Project($staff, 'risk1', new Money('EUR', '100'), new MarketSegment());

        // Contact should be hydrated
        $contact = $project->getPrivilegedContactPerson();

        static::assertEquals('Firstname', $contact->getFirstName());
        static::assertEquals('Lastname', $contact->getLastName());
        static::assertEquals('contact@demo.fr', $contact->getEmail());
        static::assertEquals('JobFunction', $contact->getOccupation());
        static::assertEquals('05000000', $contact->getPhone());
    }
}
