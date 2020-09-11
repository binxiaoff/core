<?php

namespace Unilend\Test\Unit\Service\File;

use PHPUnit\Framework\TestCase;
use Unilend\Entity\Clients;
use Unilend\Entity\Company;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Project;
use Unilend\Entity\Staff;

class ProjectTest extends TestCase
{

    /**
     * @throws \Exception
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
        $this->assertEquals('Firstname', $contact->getFirstName());
        $this->assertEquals('Lastname', $contact->getLastName());
        $this->assertEquals('contact@demo.fr', $contact->getEmail());
        $this->assertEquals('JobFunction', $contact->getOccupation());
        $this->assertEquals('05000000', $contact->getPhone());
    }
}
