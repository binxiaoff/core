<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use KLS\CreditGuaranty\FEI\Entity\Department;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

class DepartmentFixtures extends AbstractFixtures
{
    public function load(ObjectManager $manager)
    {
        foreach ($this->loadData() as $reference => $departmentData) {
            $department = new Department(
                $departmentData['code'],
                $departmentData['name'],
                $departmentData['region'],
            );

            $this->setPublicId($department, $reference);
            $this->addReference($reference, $department);

            $manager->persist($department);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        yield 'department:paris' => [
            'code'   => '75',
            'name'   => 'Paris',
            'region' => 'Ile-de-France',
        ];
        yield 'department:seine-et-marne' => [
            'code'   => '77',
            'name'   => 'Seine-et-Marne',
            'region' => 'Ile-de-France',
        ];
        yield 'department:val-de-marne' => [
            'code'   => '94',
            'name'   => 'Val-de-Marne',
            'region' => 'Ile-de-France',
        ];
    }
}
