<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\MarketSegment;

class MarketSegmentFixtures extends AbstractFixtures
{

    public const SEGMENT1 = "SEGMENT1";
    public const SEGMENT2 = "SEGMENT2";
    public const SEGMENT3 = "SEGMENT3";
    public const SEGMENT4 = "SEGMENT4";
    public const SEGMENT5 = "SEGMENT5";
    public const SEGMENT6 = "SEGMENT6";
    public const SEGMENT7 = "SEGMENT7";
    public const SEGMENT8 = "SEGMENT8";

    public const SEGMENTS = [
        self::SEGMENT1,
        self::SEGMENT2,
        self::SEGMENT3,
        self::SEGMENT4,
        self::SEGMENT5,
        self::SEGMENT6,
        self::SEGMENT7,
        self::SEGMENT8
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
        for ($i = 1; $i < 11; $i++) {
            $segment = (new MarketSegment())->setLabel("Segment #$i");
            $manager->persist($segment);
            $this->addReference(self::SEGMENTS[$i - 1], $segment);
        }

        $manager->flush();
    }
}
