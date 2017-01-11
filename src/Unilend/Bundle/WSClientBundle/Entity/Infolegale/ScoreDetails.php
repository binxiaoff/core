<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class ScoreDetails
{
    /**
     * @JMS\Type("integer")
     */
    private $score;

    /**
     * @JMS\Type("string")
     */
    private $credit;

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return string
     */
    public function getCredit()
    {
        return $this->credit;
    }
}
