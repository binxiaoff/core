<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Folder;
use Unilend\Core\Entity\User;
use Unilend\Core\Exception\Drive\FolderAlreadyExistsException;

/**
 * @coversDefaultClass \Unilend\Core\Entity\Drive
 *
 * @internal
 */
class DriveTest extends TestCase
{
    /**
     * @covers ::getFolders
     *
     * @dataProvider providerGetFolders
     */
    public function testGetFolders(Drive $drive, int $expectedCount, ?int $depth = null, ?string $exceptionClass = null): void
    {
        if ($exceptionClass) {
            $this->expectException($exceptionClass);
        }

        $result = $drive->getFolders($depth);

        static::assertContainsOnly(Folder::class, $result);
        static::assertCount($expectedCount, $result);
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerGetFolders(): iterable
    {
        $drive = new Drive();
        $drive->createFolder('/toto/tata/titi/tutu');
        $drive->createFolder('/tete/tata/titi/tutu');
        $drive->createFolder('/trtr');
        $drive->createFolder('/foo/bar');

        return [
            'It return no folders with a newly created drive'                        => [new Drive(), 0],
            'It return no folders with a newly created drive and a given depth of 3' => [new Drive(), 0, 3],
            'It return the correct number of folders with no depth given'            => [$drive, 11],
            'It return the correct number of folders with a depth of 1'              => [$drive, 4, 1],
            'It return the correct number of folders with a depth of 2'              => [$drive, 7, 2],
            'It return the correct number of folders with a depth of 3'              => [$drive, 9, 3],
            'It throws an exception when 0 is given as a depth'                      => [new Drive(), 0, 0, InvalidArgumentException::class],
            'It throws an exception when a negative number is given as a depth'      => [new Drive(), 0, -1, InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider providerCreateFolder
     *
     * @covers ::createFolder
     *
     * @throws FolderAlreadyExistsException
     */
    public function testCreateFolder(Drive $drive, string $path, ?string $exceptionClass = null): void
    {
        if ($exceptionClass) {
            $this->expectException($exceptionClass);
        }

        $drive->createFolder($path);

        static::assertTrue($drive->exist($path));
    }

    /**
     * @throws FolderAlreadyExistsException
     *
     * @return string[]
     */
    public function providerCreateFolder(): iterable
    {
        $folders = [
            'It ignores attempt to create /'                                         => '/',
            'It can create a folder directly when given path has leading /'          => implode('/', ['onelevel']),
            'It can create all folders recursively when given path has leading /'    => implode('/', ['several', 'level', 'depth']),
            'It can create a folder directly when given path has suffixed /'         => implode('/', ['onelevel']) . '/',
            'It can create all folders recursively when given path has suffixed /'   => implode('/', ['several', 'level', 'depth']) . '/',
            'It can create a folder directly when given path has no affixed /'       => 'onelevelwithoutleadingslash',
            'It can create all folders recursively when given path has no affixed /' => 'several' . implode('/', ['level', 'without', 'leading', 'slash']),
        ];

        foreach ($folders as $test => $folder) {
            yield $test => [new Drive(), $folder];
        }

        $drive = new Drive();
        $drive->createFolder('/existingFolder');

        yield 'It throw an exception when the folder already exist' => [$drive, 'existingFolder', FolderAlreadyExistsException::class];
    }

    /**
     * @coverss ::rmFolder
     *
     * @dataProvider providerDeleteFolder
     *
     * @param mixed $path
     */
    public function testDeleteFolder(Drive $drive, $path, ?string $exceptionClass = null): void
    {
        if ($exceptionClass) {
            $this->expectException($exceptionClass);
        }

        $drive->deleteFolder($path);

        if ($path instanceof Folder) {
            $path = $path->getPath();
        }

        static::assertNull($drive->getFolder($path));

        foreach ($drive->getFolders() as $folder) {
            static::assertFalse(mb_strpos($folder->getPath(), $path));
        }
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerDeleteFolder(): iterable
    {
        $testDrives = array_map(static fn () => (new Drive())->createFolder(implode('/', ['toto', 'tata', 'titi'])), range(0, 5));

        $testDrives[5]->getFolder('/toto')->addFile($this->generateMockFile('test.png'));

        return [
            'It handles non existant path with leading /'                       => [new Drive(), '/toto'],
            'It handles non existant path without affixed /'                    => [new Drive(), 'toto'],
            'It handles non existant path with suffixed /'                      => [new Drive(), 'toto/'],
            'It throws an exception for a folder with an incorrect drive'       => [new Drive(), new Folder('t', new Drive(), '/'), InvalidArgumentException::class],
            'It throws an exception when attempting to delete the root folder'  => [new Drive(), '/', InvalidArgumentException::class],
            'It can delete folder when parameter is Folder and path is simple'  => [$testDrives[0], $testDrives[0]->getFolders()['/toto']],
            'It can delete folder when parameter is Folder and path is complex' => [$testDrives[1], $testDrives[1]->getFolders()['/toto/tata']],
            'It can delete folder when parameter is string and path is simple'  => [$testDrives[2], implode('/', ['toto'])],
            'It can delete folder when parameter is string and path is complex' => [$testDrives[3], implode('/', ['toto', 'tata'])],
            'It ignore path targeting file'                                     => [$testDrives[5], implode('/', ['toto', 'test.png'])],
        ];
    }

    /**
     * @covers ::getFolder
     *
     * @dataProvider providerGetFolder
     *
     * @param mixed $expectedResult
     */
    public function testGetFolder(Drive $drive, string $path, $expectedResult): void
    {
        $result = $drive->getFolder($path);

        static::assertSame($expectedResult, $result);
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerGetFolder(): iterable
    {
        $drive = new Drive();
        $drive->createFolder(implode('/', ['toto', 'tata', 'titi']));

        foreach ($drive->getFolders() as $folder) {
            $folder->addFile($this->generateMockFile('test.png'));
        }

        $drive->addFile($this->generateMockFile('test.png'));

        return [
            'It returns self when given ' . '/' . ' as argument' => [$drive, '/', $drive],
            'It returns a folder when the folder exists 1'       => [$drive, '/toto', $drive->getFolders()['/toto']],
            'It returns a folder when the folder exists 2'       => [$drive, '/toto/tata', $drive->getFolders()['/toto/tata']],
            'It returns a folder when the folder exists 3'       => [$drive, '/toto/tata/titi', $drive->getFolders()['/toto/tata/titi']],
            'It returns null when the path does not exists'      => [$drive, '/tutu', null],
            'It returns null when the path target a file 1'      => [$drive, '/test.png', null],
            'It returns null when the path target a file 2'      => [$drive, '/toto/test.png', null],
        ];
    }

    /**
     * @covers ::get
     *
     * @dataProvider providerGet
     *
     * @param mixed $expected
     */
    public function testGet(Drive $drive, string $path, $expected): void
    {
        $result = $drive->get($path);

        static::assertSame($expected, $result);
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerGet(): iterable
    {
        $drive = $this->getCommonDrive();

        return [
            'It should return the drive for /'                      => [$drive, '/', $drive],
            'It should return a folder when path target a folder 1' => [$drive, '/toto', $drive->getFolder('/toto')],
            'It should return a folder when path target a folder 2' => [$drive, '/toto/tata', $drive->getFolder('/toto/tata')],
            'It should return a folder when path target a folder 3' => [$drive, '/foo/bar', $drive->getFolder('/foo/bar')],
            'It should return a file when path target a file 1'     => [$drive, '/root.png', $drive->getFolder('/')->getFile('root.png')],
            'It should return a file when path target a file 2'     => [$drive, '/foo/bar/jamiroquai.png',  $drive->getFolder('/foo/bar')->getFile('jamiroquai.png')],
            'It should return a file when path target a file 3'     => [$drive, '/toto/tata/titi/hidden.png', $drive->getFolder('/toto/tata/titi')->getFile('hidden.png')],
            'It should return null when not found 1'                => [$drive, '/toto/tata/hidden.png', null],
            'It should return null when not found 2'                => [$drive, '/toto/tutu/', null],
        ];
    }

    /**
     * @covers ::exist
     *
     * @dataProvider providerExist
     */
    public function testExist(Drive $drive, string $path, bool $expected): void
    {
        static::assertSame($drive->exist($path), $expected);
    }

    public function providerExist(): iterable
    {
        $commonDrive = $this->getCommonDrive();

        yield from [
            'It should return true for root for common drive'                   => [$commonDrive, '/', true],
            'It should return true for existing folder for common drive 1'      => [$commonDrive, '/toto', true],
            'It should return true for existing folder for common drive 2'      => [$commonDrive, '/toto/tata', true],
            'It should return true for existing file for common drive 1'        => [$commonDrive, '/root.png', true],
            'It should return true for existing file for common drive 2'        => [$commonDrive, '/toto/tata/titi/hidden.png', true],
            'It should return false for non existing folder for common drive 1' => [$commonDrive, '/tutu', false],
            'It should return false for non existing folder for common drive 2' => [$commonDrive, '/foo/bar/falm', false],
            'It should return false for non existing file for common drive 2'   => [$commonDrive, '/false.pdf', false],
            'It should return false for non existing file for common drive 3'   => [$commonDrive, '/toto/tata/false.pdf', false],
        ];

        yield from [
            'It should return true for root for new drive'                 => [$commonDrive, '/', true],
            'It should return false for non existing folder for new drive' => [new Drive(), '/tutu', false],
            'It should return false for non existing file for new drive'   => [new Drive(), '/false.pdf', false],
        ];
    }

    /**
     * @covers ::delete
     *
     * @dataProvider providerDelete
     *
     * @param mixed $element
     */
    public function testDelete(Drive $drive, $element, string $testPath, ?string $exceptionClass = null): void
    {
        if ($exceptionClass) {
            $this->expectException($exceptionClass);
        }

        $drive->delete($element);

        static::assertFalse($drive->exist($testPath));
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerDelete(): iterable
    {
        yield from [
            'It should throw an expection for /'                                  => [$this->getCommonDrive(), '/', '/', \LogicException::class],
            'It should remove file (given as string path) when it is present 1'   => [$this->getCommonDrive(), '/root.png', '/root.png'],
            'It should remove file (given as string path) when it is present 2'   => [$this->getCommonDrive(), '/toto/tata/titi/hidden.png', '/toto/tata/titi/hidden.png'],
            'It should remove folder (given as string path) when it is present 1' => [$this->getCommonDrive(), '/toto/tata', '/toto/tata'],
            'It should remove folder (given as string path) when it is present 2' => [$this->getCommonDrive(), '/foo', '/foo'],
            'It should not throw exception when path does not exist 1'            => [$this->getCommonDrive(), '/roll.png',  '/roll.png'],
            'It should not throw exception when path does not exist 2'            => [$this->getCommonDrive(), '/a/z/r/',  '/a/z/r/'],
        ];

        yield from [
            'It remove file (as object) if it exists in drive file collection'   => [$drive = $this->getCommonDrive(), $drive->get('/root.png'), '/root.png'],
            'It ignore unknown absent file (as object) in drive file collection' => [$drive = $this->getCommonDrive(), $this->generateMockFile('dummy.png'), '/dummy.png'],
            'It remove folder (given as folder object) when it is present 1'     => [$drive = $this->getCommonDrive(), $drive->get('/toto/tata'), '/toto/tata'],
            'It remove folder (given as folder object) when it is present 2'     => [$drive = $this->getCommonDrive(), $drive->get('/foo'), '/foo'],
            'It throws an error for drive mismatch'                              => [$drive, new Folder('foo', new Drive(), '/'), '/foo', InvalidArgumentException::class],
        ];

        $drive  = $this->getCommonDrive();
        $folder = $drive->getFolder('/toto');
        $drive->deleteFolder($folder);

        yield from [
            'It ignores an already delete folder' => [$drive, $folder, '/toto'],
        ];
    }

    /**
     * @covers ::getContent
     *
     * @dataProvider providerGetContent
     */
    public function testGetContent(Drive $drive, array $expected): void
    {
        $result = $drive->getContent();

        static::assertCount(count($expected), $result);

        foreach ($expected as $e) {
            static::assertContains($e, $result);
        }
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerGetContent(): iterable
    {
        $simpleDrive = (new Drive())->createFolder('/toto');

        $complexDrive = $this->getCommonDrive();

        return [
            'It lists no content for an empty drive'            => [new Drive(), []],
            'It lists the content of a simple drive'            => [$simpleDrive, [$simpleDrive->getFolder('/toto')]],
            'It lists only the first level for a complex drive' => [
                $complexDrive,
                [$complexDrive->getFolder('/toto'), $complexDrive->getFolder('/foo'), $complexDrive->getFile('/root.png')],
            ],
        ];
    }

    /**
     * @covers ::list
     *
     * @dataProvider providerList
     */
    public function testList(Drive $drive, array $expected, ?int $depth = null): void
    {
        $result = $depth ? $drive->list($depth) : $drive->list();

        static::assertCount(count($expected), $result);

        foreach ($expected as $e) {
            static::assertContains($e, $result);
        }
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function providerList(): iterable
    {
        $simpleDrive = (new Drive())->createFolder('/toto');

        $complexDrive = $this->getCommonDrive();

        return [
            'It lists no content for an empty drive'                               => [new Drive(), []],
            'It lists no content for an empty drive no matter the depth'           => [new Drive(), [], 5],
            'It lists the content of a simple drive 1'                             => [$simpleDrive, [$simpleDrive->getFolder('/toto')]],
            'It lists the content of a simple drive 2'                             => [$simpleDrive, [$simpleDrive->getFolder('/toto')], 3],
            'It lists only the first level for a complex drive with default depth' => [
                $complexDrive,
                [$complexDrive->getFolder('/toto'), $complexDrive->getFolder('/foo'), $complexDrive->getFile('/root.png')],
            ],
            'It lists only the first level for a complex drive with default depth arbitrary depth' => [
                $complexDrive,
                [
                    $complexDrive->getFolder('/toto'),
                    $complexDrive->getFolder('/toto/tata'),
                    $complexDrive->getFolder('/toto/tata/titi'),
                    $complexDrive->getFolder('/foo'),
                    $complexDrive->getFolder('/foo/bar'),
                    $complexDrive->getFolder('/foo/bar/qux'),
                    $complexDrive->getFile('/foo/bar/jamiroquai.png'),
                    $complexDrive->getFile('/root.png'),
                ],
                3,
            ],
        ];
    }

    /**
     * @covers ::isNameValid
     *
     * @dataProvider providerIsNameValid
     */
    public function testIsNameValid(Drive $drive, string $tested, bool $expected)
    {
        static::assertSame($expected, $drive->isNameValid($tested));
    }

    public function providerIsNameValid(): array
    {
        $drive = new Drive();

        return [
            'A name containing a / is invalid'  => [$drive, 'azeze/azeaze', false],
            'A name starting with / is invalid' => [$drive, '/dazazd/azddza', false],
            'A name ending with / is invalid'   => [$drive, 'azdazd/', false],
            'A name without / is valid'         => [$drive, 'toto', true],
        ];
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    private function getCommonDrive(): Drive
    {
        $drive = new Drive();

        $fileNames = ['root.png', 'hidden.png', 'jamiroquai.png'];
        $files     = array_map([$this, 'generateMockFile'], array_combine($fileNames, $fileNames));

        $drive->createFolder('/toto/tata/titi/tutu');
        $drive->createFolder('/foo/bar/qux/buz');

        $drive->addFile($files['root.png']);
        $drive->getFolder('/toto/tata/titi')->addFile($files['hidden.png']);
        $drive->getFolder('/foo/bar')->addFile($files['jamiroquai.png']);

        return $drive;
    }

    private function generateMockFile(string $name): File
    {
        $file = new File();

        $fileVersion = new FileVersion('', new User('test@test.com'), $file, '');
        $fileVersion->setOriginalName($name);

        $file->setCurrentFileVersion($fileVersion);

        return $file;
    }
}
