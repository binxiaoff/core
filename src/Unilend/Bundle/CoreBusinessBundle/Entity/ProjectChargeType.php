<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectChargeType
 *
 * @ORM\Table(name="project_charge_type")
 * @ORM\Entity
 */
class ProjectChargeType
{
    const TYPE_CLAUSE_PENALE                    = 'clause_penale';
    const TYPE_VISITE_DOMICILIAIRE              = 'visite_domiciliaire';
    const TYPE_INJONCTION_DE_PAYER              = 'injonction_de_payer';
    const TYPE_REFERE_PROVISION                 = 'refere_provision';
    const TYPE_SAISIE_CONSERVATOIRE             = 'saisie_conservatoire';
    const TYPE_ASSIGNATION_AU_FOND              = 'assignation_au_fond';
    const TYPE_ASSIGNATION_PROCEDURE_COLLECTIVE = 'assignation_procedure_collective';
    const TYPE_PROCEDURE_APPEL                  = 'procedure_appel';
    const TYPE_OPPOSITION_VENTE_FONDS_COMMERCE  = 'opposition_vente_fonds_commerce';
    const TYPE_DECLARATIONS_CREANCE             = 'declarations_creance';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
     */
    private $label;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return ProjectChargeType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }
}
