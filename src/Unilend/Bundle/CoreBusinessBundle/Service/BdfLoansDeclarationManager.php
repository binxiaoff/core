<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

class BdfLoansDeclarationManager
{
    const DECLARATION_FILE_PATH = 'bdf/emissions/declarations_mensuelles';
    const IFP_TYPE              = 'ifp';
    const CIP_TYPE              = 'cip';
    const UNILEND_IFP_ID        = 'IF010';
    const UNILEND_CIP_ID        = 'CI004';

    /** @var string */
    private $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    /**
     * @return string
     */
    public function getBaseDir(): string
    {
        return $this->baseDir . self::DECLARATION_FILE_PATH;
    }

    /**
     * @return string
     */
    public function getIfpPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getBaseDir(), self::IFP_TYPE]);
    }

    /**
     * @return string
     */
    public function getIfpArchivePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getIfpPath(), 'archives']);
    }

    /**
     * @return string
     */
    public function getCipPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getBaseDir(), self::CIP_TYPE]);
    }

    /**
     * @return string
     */
    public function getCipArchivePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getCipPath(), 'archives']);
    }
}
