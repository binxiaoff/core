<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class BalancePost
{
    /**
     * @var string
     *
     * @JMS\SerializedName("poste")
     * @JMS\Type("string")
     */
    private $postLabel;

    /**
     * @var double
     *
     * @JMS\SerializedName("valeur")
     * @JMS\Type("double")
     */
    private $postValue;

    /**
     * @return string
     */
    public function getPostLabel()
    {
        return $this->postLabel;
    }

    /**
     * @return float
     */
    public function getPostValue()
    {
        return $this->postValue;
    }
}
