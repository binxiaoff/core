<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\FileSystem;

use Faker\Provider\Base;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Unilend\Service\FileSystem\FileCrypto;
use Unilend\Service\FileSystem\FileSystemHelper;

/**
 * @coversDefaultClass \Unilend\Service\FileSystem\FileSystemHelper
 *
 * @internal
 */
class FileSystemHelperTest extends TestCase
{
    /** @var string */
    private string $destPath;
    /** @var string */
    private string $srcPath;
    /** @var FilesystemInterface|ObjectProphecy */
    private $userAttachmentFilesystem;
    /** @var FilesystemInterface|ObjectProphecy */
    private $generatedDocumentFilesystem;
    /** @var FileCrypto|ObjectProphecy */
    private $fileCrypto;
    /** @var string */
    private string $encryptedFilePath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->srcPath                     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Base::lexify('?????');
        $this->encryptedFilePath           = $this->srcPath . '-encrypted';
        $this->destPath                    = Base::lexify('/????/???');
        $this->userAttachmentFilesystem    = $this->prophesize(FilesystemInterface::class);
        $this->generatedDocumentFilesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileCrypto                  = $this->prophesize(FileCrypto::class);

        $this->buildTestFile();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystem(): void
    {
        $srcPath = $this->srcPath;

        $fileSystem   = $this->prophesize(FilesystemInterface::class);
        $streamWriter = $fileSystem->writeStream(Argument::exact($this->destPath), Argument::that(static function ($usedResource) use ($srcPath) {
            return stream_get_meta_data($usedResource)['uri'] === $srcPath;
        }));
        $streamWriter->willReturn(true);

        $this->createTestObject()->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath, false);

        $streamWriter->shouldHaveBeenCalled();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemWithEncryption(): void
    {
        $encryptedFilePath = $this->encryptedFilePath;
        $resource          = fopen($encryptedFilePath, 'wb+');

        $fileEncrypt = $this->fileCrypto->encryptFile(Argument::exact($this->srcPath), Argument::exact($this->encryptedFilePath));
        $fileEncrypt->willReturn(Base::asciify(str_repeat('*', 440)));

        $fileSystem   = $this->prophesize(FilesystemInterface::class);
        $streamWriter = $fileSystem->writeStream(Argument::exact($this->destPath), Argument::that(static function ($usedResource) use ($encryptedFilePath) {
            return stream_get_meta_data($usedResource)['uri'] === $encryptedFilePath;
        }));
        $streamWriter->willReturn(true);

        $this->createTestObject()->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath, true);

        $streamWriter->shouldHaveBeenCalled();
        $fileEncrypt->shouldHaveBeenCalled();

        fclose($resource);
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemNonexistentFile(): void
    {
        $nonexistentSrcFile = Base::lexify('/????/???');
        $fileSystem         = $this->prophesize(FilesystemInterface::class);

        $this->createTestObject()->writeTempFileToFileSystem($nonexistentSrcFile, $fileSystem->reveal(), $this->destPath, false);

        $fileSystem->writeStream(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemFailedWriting(): void
    {
        $this->expectException(RuntimeException::class);

        $fileSystem = $this->prophesize(FilesystemInterface::class);
        $fileSystem->writeStream(Argument::any(), Argument::any())->willReturn(false);

        $this->createTestObject()->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath, false);
    }

    /**
     * @return bool|resource
     */
    private function buildTestFile()
    {
        return fopen($this->srcPath, 'w+b');
    }

    /**
     * @return FileSystemHelper
     */
    private function createTestObject(): FileSystemHelper
    {
        return new FileSystemHelper(
            $this->fileCrypto->reveal(),
            $this->userAttachmentFilesystem->reveal(),
            $this->generatedDocumentFilesystem->reveal()
        );
    }
}
