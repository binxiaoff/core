<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\FileSystem;

use Faker\Provider\Base;
use KLS\Core\Service\FileSystem\FileCrypto;
use KLS\Core\Service\FileSystem\FileSystemHelper;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

/**
 * @coversDefaultClass \KLS\Core\Service\FileSystem\FileSystemHelper
 *
 * @internal
 */
class FileSystemHelperTest extends TestCase
{
    use ProphecyTrait;

    private string $destPath;
    private string $srcPath;
    /** @var FilesystemOperator|ObjectProphecy */
    private $userAttachmentFilesystem;
    /** @var FilesystemOperator|ObjectProphecy */
    private $generatedDocumentFilesystem;
    /** @var FileCrypto|ObjectProphecy */
    private $fileCrypto;

    private string $encryptedFilePath;

    protected function setUp(): void
    {
        $this->srcPath                     = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . Base::lexify('?????');
        $this->encryptedFilePath           = $this->srcPath . '-encrypted';
        $this->destPath                    = Base::lexify('/????/???');
        $this->userAttachmentFilesystem    = $this->prophesize(FilesystemOperator::class);
        $this->generatedDocumentFilesystem = $this->prophesize(FilesystemOperator::class);
        $this->fileCrypto                  = $this->prophesize(FileCrypto::class);

        $this->buildTestFile();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystem(): void
    {
        $srcPath = $this->srcPath;

        $fileSystem   = $this->prophesize(FilesystemOperator::class);
        $streamWriter = $fileSystem->writeStream(Argument::exact($this->destPath), Argument::that(static function ($usedResource) use ($srcPath) {
            return \stream_get_meta_data($usedResource)['uri'] === $srcPath;
        }));

        $this->createTestObject()->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath, false);

        $streamWriter->shouldHaveBeenCalled();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemWithEncryption(): void
    {
        $encryptedFilePath = $this->encryptedFilePath;
        $resource          = \fopen($encryptedFilePath, 'wb+');

        $fileEncrypt = $this->fileCrypto->encryptFile(Argument::exact($this->srcPath), Argument::exact($this->encryptedFilePath));
        $fileEncrypt->willReturn(Base::asciify(\str_repeat('*', 440)));

        $fileSystem   = $this->prophesize(FilesystemOperator::class);
        $streamWriter = $fileSystem->writeStream(Argument::exact($this->destPath), Argument::that(static function ($usedResource) use ($encryptedFilePath) {
            return \stream_get_meta_data($usedResource)['uri'] === $encryptedFilePath;
        }));

        $this->createTestObject()->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath, true);

        $streamWriter->shouldHaveBeenCalled();
        $fileEncrypt->shouldHaveBeenCalled();

        \fclose($resource);
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemNonexistentFile(): void
    {
        $nonexistentSrcFile = Base::lexify('/????/???');
        $fileSystem         = $this->prophesize(FilesystemOperator::class);

        $this->createTestObject()->writeTempFileToFileSystem($nonexistentSrcFile, $fileSystem->reveal(), $this->destPath, false);

        $fileSystem->writeStream(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemFailedWriting(): void
    {
        $this->expectException(RuntimeException::class);

        $fileSystem = $this->prophesize(FilesystemOperator::class);
        $fileSystem->writeStream(Argument::any(), Argument::any())->willReturn(false);

        $this->createTestObject()->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath, false);
    }

    private function buildTestFile(): void
    {
        \fopen($this->srcPath, 'w+b');
    }

    private function createTestObject(): FileSystemHelper
    {
        return new FileSystemHelper(
            $this->fileCrypto->reveal(),
            $this->userAttachmentFilesystem->reveal(),
            $this->generatedDocumentFilesystem->reveal()
        );
    }
}
