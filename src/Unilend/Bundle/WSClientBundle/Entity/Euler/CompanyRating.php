<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Euler;

use JMS\Serializer\Annotation as JMS;

class CompanyRating
{
    const COLOR_BLACK  = 'Black';
    const COLOR_RED    = 'Red';
    const COLOR_YELLOW = 'Yellow';
    const COLOR_GREEN  = 'Green';
    const COLOR_WHITE  = 'White';

    const GRADE_UNKNOWN = 'NA';

    /**
     * @JMS\Groups({"grade"})
     * @JMS\SerializedName("message")
     * @JMS\Type("integer")
     */
    private $grade;

    /**
     * @JMS\Groups({"grade"})
     * @JMS\Type("integer")
     */
    private $code;
    /**
     * @JMS\Groups({"light"})
     * @JMS\SerializedName("Color")
     * @JMS\Type("string")
     */
    private $color;

    /**
     * @return mixed
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }
}
