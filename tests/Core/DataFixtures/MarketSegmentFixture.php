<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\MarketSegment;

class MarketSegmentFixture extends AbstractFixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
        foreach (['pro', 'ppp'] as $label) {
            $marketSegment = new MarketSegment();

            $marketSegment->setLabel($label);
            $reference = 'marketSegment/' . $label;
            $this->setPublicId($marketSegment, $reference);
            $this->addReference($reference, $marketSegment);
            $manager->persist($marketSegment);
        }

        $manager->flush();
    }
}
