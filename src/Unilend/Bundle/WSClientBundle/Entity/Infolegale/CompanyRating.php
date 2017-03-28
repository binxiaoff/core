<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("service", namespace = "Infolegale/Webservices/getScore")
 * @JMS\XmlNamespace(uri = "Infolegale/Webservices/getScore", prefix = "gs")
 * @JMS\XmlNamespace(uri = "Infolegale/Webservices/Main", prefix = "ilg")
 */
class CompanyRating
{
    /**
     * @JMS\XmlKeyValuePairs
     * @JMS\Type("array<string, string>")
     * @JMS\XmlElement(namespace = "Infolegale/Webservices/Main")
     */
    private $errors;

    /**
     *
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Infolegale\RequestInfo")
     * @JMS\XmlElement(namespace = "Infolegale/Webservices/Main")
     */
    private $request;

    /**
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Infolegale\ScoreInfo")
     * @JMS\XmlElement(namespace = "Infolegale/Webservices/Main")
     */
    private $content;

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}
