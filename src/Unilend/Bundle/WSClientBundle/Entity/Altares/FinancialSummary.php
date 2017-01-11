<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

/**
 * Class FinancialSummary
 * @package Unilend\Bundle\WSClientBundle\Entity\Altares
 *
 * @JMS\ExclusionPolicy("all")
 */
class FinancialSummary
{
    /**
     * @JMS\Groups({"summary", "management_line"})
     * @JMS\SerializedName("montantN")
     * @JMS\Type("integer")
     *
     * @JMS\Expose
     */
    private $amountY;

    /**
     * @JMS\Groups({"summary", "management_line"})
     * @JMS\SerializedName("montantN_1")
     * @JMS\Type("integer")
     *
     * @JMS\Expose
     */
    private $amountY_1;

    /**
     * @JMS\Groups({"summary", "management_line"})
     * @JMS\SerializedName("montantN_2")
     * @JMS\Type("integer")
     *
     * @JMS\Expose
     */
    private $amountY_2;

    /**
     * @JMS\Groups({"summary", "management_line"})
     * @JMS\SerializedName("montantN_3")
     * @JMS\Type("integer")
     *
     * @JMS\Expose
     */
    private $amountY_3;

    /**
     * @JMS\Groups({"summary"})
     * @JMS\SerializedName("poste")
     * @JMS\Type("string")
     *
     * @JMS\Expose
     */
    private $post;

    /**
     * @JMS\Groups({"summary"})
     * @JMS\SerializedName("libellePoste")
     * @JMS\Type("string")
     *
     * @JMS\Expose
     */
    private $postLabel;

    /**
     * @JMS\Groups({"management_line"})
     * @JMS\SerializedName("libellePosteCle")
     * @JMS\Type("string")
     *
     * @JMS\Expose
     */
    private $keyPostLabel;

    /**
     * @JMS\Groups({"management_line"})
     * @JMS\SerializedName("posteCle")
     * @JMS\Type("string")
     *
     * @JMS\Expose
     */
    private $keyPost;

    /**
     * @return mixed
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return mixed
     */
    public function getPostLabel()
    {
        return $this->postLabel;
    }

    /**
     * @return mixed
     */
    public function getAmountY()
    {
        return $this->amountY;
    }

    /**
     * @return mixed
     */
    public function getAmountY1()
    {
        return $this->amountY_1;
    }

    /**
     * @return mixed
     */
    public function getAmountY2()
    {
        return $this->amountY_2;
    }

    /**
     * @return mixed
     */
    public function getAmountY3()
    {
        return $this->amountY_3;
    }

    /**
     * @return mixed
     */
    public function getKeyPostLabel()
    {
        return $this->keyPostLabel;
    }

    /**
     * @return mixed
     */
    public function getKeyPost()
    {
        return $this->keyPost;
    }

}
