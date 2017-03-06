<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderPanelPreference
 *
 * @ORM\Table(name="lender_panel_preference", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="page_name", columns={"page_name"})})
 * @ORM\Entity
 */
class LenderPanelPreference
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var string
     *
     * @ORM\Column(name="page_name", type="string", length=64, nullable=false)
     */
    private $pageName;

    /**
     * @var string
     *
     * @ORM\Column(name="panel_name", type="string", length=64, nullable=false)
     */
    private $panelName;

    /**
     * @var integer
     *
     * @ORM\Column(name="panel_order", type="integer", nullable=false)
     */
    private $panelOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="hidden", type="integer", nullable=false)
     */
    private $hidden;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=true)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_panel_preference", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderPanelPreference;



    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return LenderPanelPreference
     */
    public function setIdLender($idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return integer
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * Set pageName
     *
     * @param string $pageName
     *
     * @return LenderPanelPreference
     */
    public function setPageName($pageName)
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * Get pageName
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * Set panelName
     *
     * @param string $panelName
     *
     * @return LenderPanelPreference
     */
    public function setPanelName($panelName)
    {
        $this->panelName = $panelName;

        return $this;
    }

    /**
     * Get panelName
     *
     * @return string
     */
    public function getPanelName()
    {
        return $this->panelName;
    }

    /**
     * Set panelOrder
     *
     * @param integer $panelOrder
     *
     * @return LenderPanelPreference
     */
    public function setPanelOrder($panelOrder)
    {
        $this->panelOrder = $panelOrder;

        return $this;
    }

    /**
     * Get panelOrder
     *
     * @return integer
     */
    public function getPanelOrder()
    {
        return $this->panelOrder;
    }

    /**
     * Set hidden
     *
     * @param integer $hidden
     *
     * @return LenderPanelPreference
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return integer
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LenderPanelPreference
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return LenderPanelPreference
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idLenderPanelPreference
     *
     * @return integer
     */
    public function getIdLenderPanelPreference()
    {
        return $this->idLenderPanelPreference;
    }
}
