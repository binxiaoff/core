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
     * @var string
     *
     * @JMS\Groups({"grade"})
     * @JMS\SerializedName("message")
     * @JMS\Type("string")
     */
    private $grade;

    /**
     * @var int
     *
     * @JMS\Groups({"grade"})
     * @JMS\Type("integer")
     */
    private $code;

    /**
     * @var string
     *
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
        if (is_numeric($this->grade)) {
            return (int) $this->grade;
        }

        return $this->grade;
    }

    /**
     * @param string $grade
     *
     * @return $this
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;

        return $this;
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
