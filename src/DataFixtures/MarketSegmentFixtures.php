<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\Entity\MarketSegment;

class MarketSegmentFixtures extends AbstractFixtures
{

    public const SEGMENT1 = 'public_collectivity';
    public const SEGMENT2 = 'energy';
    public const SEGMENT3 = 'corporate';
    public const SEGMENT4 = 'real_estate_development';
    public const SEGMENT5 = 'ppp';
    public const SEGMENT6 = 'agriculture';
    public const SEGMENT7 = 'patrimonial';
    public const SEGMENT8 = 'pro';

    public const SEGMENTS = [
        self::SEGMENT1,
        self::SEGMENT2,
        self::SEGMENT3,
        self::SEGMENT4,
        self::SEGMENT5,
        self::SEGMENT6,
        self::SEGMENT7,
        self::SEGMENT8,
    ];

    /**
     * Load fake MarketSegments
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        // We need to reset auto increment cause ID are important for the front :(
        $manager->getConnection()->exec('ALTER TABLE market_segment AUTO_INCREMENT = 1;');
        foreach (self::SEGMENTS as $index => $label) {
            $segment = (new MarketSegment())->setLabel($label);
            $manager->persist($segment);
            $this->addReference($label, $segment);
        }

        $manager->flush();
    }
}
