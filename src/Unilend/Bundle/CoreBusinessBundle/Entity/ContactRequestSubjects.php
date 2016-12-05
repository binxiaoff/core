<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactRequestSubjects
 *
 * @ORM\Table(name="contact_request_subjects")
 * @ORM\Entity
 */
class ContactRequestSubjects
{
    /**
     * @var string
     *
     * @ORM\Column(name="label_contact_request_subject", type="string", length=155, nullable=false)
     */
    private $labelContactRequestSubject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_contact_request_subject", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContactRequestSubject;



    /**
     * Set labelContactRequestSubject
     *
     * @param string $labelContactRequestSubject
     *
     * @return ContactRequestSubjects
     */
    public function setLabelContactRequestSubject($labelContactRequestSubject)
    {
        $this->labelContactRequestSubject = $labelContactRequestSubject;

        return $this;
    }

    /**
     * Get labelContactRequestSubject
     *
     * @return string
     */
    public function getLabelContactRequestSubject()
    {
        return $this->labelContactRequestSubject;
    }

    /**
     * Get idContactRequestSubject
     *
     * @return integer
     */
    public function getIdContactRequestSubject()
    {
        return $this->idContactRequestSubject;
    }
}
