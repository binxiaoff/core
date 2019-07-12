<?php

namespace Unilend\Service\Document;

interface DocumentGeneratorInterface
{
    /**
     * @param $document
     *
     * @return string
     */
    public function getFilePath(object $document): string;

    /**
     * @param $document
     *
     * @return bool
     */
    public function exists(object $document): bool;

    /**
     * @param $document
     */
    public function generate(object $document): void;
}
