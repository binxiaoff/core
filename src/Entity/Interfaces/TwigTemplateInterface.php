<?php

declare(strict_types=1);

namespace Unilend\Entity\Interfaces;

use DateTimeImmutable;
use Twig\Source;

interface TwigTemplateInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @return Source
     */
    public function getSource(): Source;

    /**
     * @return string
     */
    public function getLocale(): string;

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable;

    /**
     * @return DateTimeImmutable
     */
    public function getUpdated(): ?DateTimeImmutable;
}
