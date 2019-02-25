<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientAddressAttachment
 *
 * @ORM\Table(name="client_address_attachment", indexes={@ORM\Index(name="idx_client_address_attachment_id_client_address", columns={"id_client_address"}), @ORM\Index(name="idx_client_address_attachment_id_attachement", columns={"id_attachment"})})
 * @ORM\Entity
 */
class ClientAddressAttachment
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ClientAddress
     *
     * @ORM\ManyToOne(targetEntity="ClientAddress")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_address", referencedColumnName="id", nullable=false)
     * })
     */
    private $idClientAddress;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Attachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id", nullable=false)
     * })
     */
    private $idAttachment;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set idClientAddress
     *
     * @param ClientAddress $idClientAddress
     *
     * @return ClientAddressAttachment
     */
    public function setIdClientAddress(ClientAddress $idClientAddress): ClientAddressAttachment
    {
        $this->idClientAddress = $idClientAddress;

        return $this;
    }

    /**
     * Get idClientAddress
     *
     * @return ClientAddress
     */
    public function getIdClientAddress(): ClientAddress
    {
        return $this->idClientAddress;
    }

    /**
     * Set idAttachment
     *
     * @param Attachment $idAttachment
     *
     * @return ClientAddressAttachment
     */
    public function setIdAttachment(Attachment $idAttachment): ClientAddressAttachment
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * Get idAttachment
     *
     * @return Attachment
     */
    public function getIdAttachment(): Attachment
    {
        return $this->idAttachment;
    }
}
