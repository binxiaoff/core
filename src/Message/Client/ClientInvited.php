<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientInvited
{
    /** @var string */
    private $guestEmail;
    /** @var string */
    private $guestEmailDomain;
    /** @var int */
    private $idInviter;
    /** @var int */
    private $idProject;

    /**
     * @param string $guestEmail
     * @param string $guestEmailDomain
     * @param int    $idInviter
     * @param int    $idProject
     */
    public function __construct(string $guestEmail, string $guestEmailDomain, int $idInviter, int $idProject)
    {
        $this->guestEmail       = $guestEmail;
        $this->guestEmailDomain = $guestEmailDomain;
        $this->idInviter        = $idInviter;
        $this->idProject        = $idProject;
    }

    /**
     * @return string
     */
    public function getGuestEmail(): string
    {
        return $this->guestEmail;
    }

    /**
     * @return string
     */
    public function getGuestEmailDomain(): string
    {
        return $this->guestEmailDomain;
    }

    /**
     * @return int
     */
    public function getIdInviter(): int
    {
        return $this->idInviter;
    }

    /**
     * @return int
     */
    public function getIdProject(): int
    {
        return $this->idProject;
    }
}
