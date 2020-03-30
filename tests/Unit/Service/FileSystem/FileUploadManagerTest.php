<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\FileSystem;

use Faker\Provider\Base;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Service\FileSystem\FileSystemHelper;
use Unilend\Service\FileSystem\FileUploadManager;

/**
 * @coversDefaultClass \Unilend\Service\FileSystem\FileUploadManager
 *
 * @internal
 */
class FileUploadManagerTest extends TestCase
{
    /**
     * @covers ::uploadFile
     *
     * @dataProvider subdirectoryDataProvider
     *
     * @param string $subdirectory
     *
     * @throws FileExistsException
     */
    public function testUploadFile(?string $subdirectory = null)
    {
        $tempPath         = sys_get_temp_dir();
        $originalFileName = Base::lexify('????????');
        $fullPath         = $tempPath . DIRECTORY_SEPARATOR . $originalFileName;
        fopen($fullPath, 'wb+');

        $file = new UploadedFile($fullPath, $originalFileName, null, null, true);

        $uploadRootDirectory = Base::lexify('????????');

        $fileSystemHelper = $this->prophesize(FileSystemHelper::class);
        $filesystem       = $this->prophesize(FilesystemInterface::class);

        $fileUploadManager = new FileUploadManager($fileSystemHelper->reveal());

        [$uploadedFilePath] = $fileUploadManager->uploadFile($file, $filesystem->reveal(), $uploadRootDirectory, $subdirectory);

        $fileSystemHelper->writeTempFileToFileSystem(
            Argument::exact($file->getPathname()),
            Argument::exact($filesystem),
            Argument::exact($uploadedFilePath),
            Argument::exact(true),
        )->shouldHaveBeenCalled();

        $filesystem->has(Argument::type('string'))->willReturn(false)->shouldHaveBeenCalled();

        $pathInfo         = pathinfo($uploadedFilePath);
        $uploadedFilename = $pathInfo['filename'];
        $uploadedDirname  = $pathInfo['dirname'];

        $originalFilename = pathinfo($originalFileName, PATHINFO_FILENAME);
        static::assertStringStartsWith($originalFilename, $uploadedFilename, 'created filename start with uploaded original filename');
        static::assertNotSame($originalFilename, $uploadedFilename);

        $uploadedFilePathDirectories = explode(DIRECTORY_SEPARATOR, $uploadedDirname);

        static::assertGreaterThanOrEqual(3, $uploadedFilePathDirectories, 'minimum number of directories');

        static::assertSame($uploadRootDirectory, array_shift($uploadedFilePathDirectories));
        static::assertSame(1, mb_strlen(array_shift($uploadedFilePathDirectories)), 'first mandatory subdirectory');
        static::assertSame(1, mb_strlen(array_shift($uploadedFilePathDirectories)), 'second mandatory subdirectory');
        static::assertSame((string) $subdirectory, implode(DIRECTORY_SEPARATOR, $uploadedFilePathDirectories));
    }

    /**
     * @return array
     */
    public function subdirectoryDataProvider(): array
    {
        return [
            'empty subdirectory'    => [null],
            'one subdirectory'      => [Base::lexify('?????')],
            'multiple subdirectory' => [Base::lexify('?????' . DIRECTORY_SEPARATOR . '??????')],
        ];
    }
}
