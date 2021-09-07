<?php

declare(strict_types=1);

namespace KLS\Core\Service\FileSystem;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\File;
use Defuse\Crypto\Key;

class FileCrypto
{
    /**
     * @throws IOException
     * @throws EnvironmentIsBrokenException
     */
    public function encryptFile(string $inputFilePath, string $outputFilePath): string
    {
        $key = Key::createNewRandomKey();
        File::encryptFile($inputFilePath, $outputFilePath, $key);

        return $key->saveToAsciiSafeString();
    }

    /**
     * @param resource $inputFileResource
     * @param resource $outputFileResource
     *
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws BadFormatException
     */
    public function decryptFileResource($inputFileResource, $outputFileResource, string $key): void
    {
        File::decryptResource($inputFileResource, $outputFileResource, Key::loadFromAsciiSafeString($key));
    }
}
