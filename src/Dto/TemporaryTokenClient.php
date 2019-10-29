<?php

declare(strict_types=1);

namespace Unilend\Dto;

class TemporaryTokenClient
{
    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $mobile;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $jobFunction;

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return TemporaryTokenClient
     */
    public function setLastName(string $lastName): TemporaryTokenClient
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return TemporaryTokenClient
     */
    public function setFirstName(string $firstName): TemporaryTokenClient
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMobile(): string
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     *
     * @return TemporaryTokenClient
     */
    public function setMobile(string $mobile): TemporaryTokenClient
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return TemporaryTokenClient
     */
    public function setPassword(string $password): TemporaryTokenClient
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getJobFunction(): string
    {
        return $this->jobFunction;
    }

    /**
     * @param string $jobFunction
     *
     * @return TemporaryTokenClient
     */
    public function setJobFunction(string $jobFunction): TemporaryTokenClient
    {
        $this->jobFunction = $jobFunction;

        return $this;
    }
}
