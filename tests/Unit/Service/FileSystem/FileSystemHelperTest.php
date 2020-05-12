<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\FileSystem;

use Doctrine\Common\Persistence\ManagerRegistry;
use Faker\Provider\Base;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    private $destPath;
    /** @var string */
    private $srcPath;
    /** @var bool|resource */
    private $testFileResource;
    /** @var ContainerInterface */
    private $container;
    /** @var ManagerRegistry */
    private $managerRegistry;
    /** @var FileCrypto */
    private $fileCrypto;
    /** @var string */
    private $encryptedFilePath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->srcPath           = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Base::lexify('?????');
        $this->encryptedFilePath = $this->srcPath . '-encrypted';
        $this->destPath          = Base::lexify('/????/???');
        $this->testFileResource  = $this->buildTestFile();
        $this->container         = $this->prophesize(ContainerInterface::class);
        $this->managerRegistry   = $this->prophesize(ManagerRegistry::class);
        $this->fileCrypto        = $this->prophesize(FileCrypto::class);
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
            $this->container->reveal(),
            $this->fileCrypto->reveal()
        );
    }
}
