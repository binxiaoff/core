<?php

namespace Unilend\Entity\External\Infolegale;

use JMS\Serializer\Annotation as JMS;

class Position
{
    /**
     * @var string
     *
     * @JMS\SerializedName("code")
     * @JMS\Type("string")
     */
    private $code;

    /**
     * @var string
     *
     * @JMS\SerializedName("label")
     * @JMS\Type("string")
     */
    private $label;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
