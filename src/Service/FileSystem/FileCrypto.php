<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use Defuse\Crypto\{Exception\BadFormatException, Exception\EnvironmentIsBrokenException, Exception\IOException, Exception\WrongKeyOrModifiedCiphertextException, File, Key};

class FileCrypto
{
    /**
     * @param $inputFilePath
     * @param $outputFilePath
     *
     * @throws IOException
     * @throws EnvironmentIsBrokenException
     *
     * @return string
     */
    public function encryptFile(string $inputFilePath, string $outputFilePath): string
    {
        $key = Key::createNewRandomKey();
        File::encryptFile($inputFilePath, $outputFilePath, $key);

        return $key->saveToAsciiSafeString();
    }

    /**
     * @param $inputFileResource
     * @param $outputFileResource
     * @param $key
     *
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws BadFormatException
     */
    public function decryptFileResource($inputFileResource, $outputFileResource, $key): void
    {
        File::decryptResource($inputFileResource, $outputFileResource, Key::loadFromAsciiSafeString($key));
    }
}
