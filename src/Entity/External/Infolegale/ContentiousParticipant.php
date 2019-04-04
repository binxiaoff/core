<?php

namespace Unilend\Entity\External\Infolegale;

use JMS\Serializer\Annotation as JMS;

class ContentiousParticipant
{
    const TYPE_COMPLAINANT = 'DM';
    const TYPE_TARGET      = 'DF';

    /**
     * @var string
     *
     * @JMS\SerializedName("identiteActeur/nom")
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var string
     *
     * @JMS\SerializedName("identiteActeur/siren")
     * @JMS\Type("string")
     */
    private $siren;

    /**
     * @var string
     *
     * @JMS\SerializedName("typeActeur")
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
