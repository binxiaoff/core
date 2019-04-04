<?php

namespace Unilend\Service\Document;

interface DocumentGeneratorInterface
{
    const CONTENT_TYPE_PDF = 'application/pdf';

    /**
     * @return string
     */
    public function getContentType(): string;

    /**
     * @param $document
     *
     * @return string
     */
    public function getPath($document): string;

    /**
     * @param $document
     *
     * @return bool
     */
    public function exists($document): bool;

    /**
     * @param $document
     */
    public function generate($document): void;
}
