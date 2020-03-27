<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\FileSystem;

use Doctrine\Common\Persistence\ManagerRegistry;
use Faker\Provider\{Base, File};
use League\Flysystem\{FileNotFoundException, FilesystemInterface};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{HeaderUtils, ResponseHeaderBag, StreamedResponse};
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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->srcPath          = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Base::lexify('?????');
        $this->destPath         = Base::lexify('/????/???');
        $this->testFileResource = $this->buildTestFile();
        $this->container        = $this->prophesize(ContainerInterface::class);
        $this->managerRegistry  = $this->prophesize(ManagerRegistry::class);
        $this->fileCrypto       = $this->prophesize(FileCrypto::class);
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
        static::assertFileNotExists($this->srcPath);
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
     * @return array
     */
    public function downloadDataProvider(): array
    {
        return [
            'mime type detected, file name defined'         => [File::mimeType(), Base::lexify('???') . '.' . File::fileExtension()],
            'mime type not detected, file name defined'     => [false, Base::lexify('???') . '.' . File::fileExtension()],
            'mime type detected, file name not defined'     => [File::mimeType(), null],
            'mime type not detected, file name not defined' => [false, null],
        ];
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
