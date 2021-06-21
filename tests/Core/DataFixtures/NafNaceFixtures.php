<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\NafNace;

class NafNaceFixtures extends AbstractFixtures
{
    public function load(ObjectManager $manager)
    {
        foreach ($this->loadData() as $reference => $nafNaceData) {
            $nafNace = new NafNace(
                $nafNaceData['nafCode'],
                $nafNaceData['naceCode'],
                $nafNaceData['nafTitle'],
                $nafNaceData['naceTitle'],
            );

            $this->setPublicId($nafNace, $reference);
            $this->addReference($reference, $nafNace);

            $manager->persist($nafNace);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        yield 'nafnace:agriculture' => [
            'nafCode'   => '0001A',
            'nafTitle'  => 'Agriculture',
            'naceCode'  => 'A0.0.1',
            'naceTitle' => 'Agriculture',
        ];
    }
}
