<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\File;
use KLS\Core\Service\File\FileDeleteInterface;
use KLS\Core\Service\File\FileDeleteManager;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \KLS\Core\Service\File\FileDeleteManager
 *
 * @internal
 */
class FileDeleteManagerTest extends TestCase
{
    /**
     * @covers ::delete
     */
    public function testDelete(): void
    {
        /** @var FileDeleteInterface|ObjectProphecy $manager1 */
        $manager1 = $this->prophesize(FileDeleteInterface::class);
        /** @var FileDeleteInterface|ObjectProphecy $manager2 */
        $manager2 = $this->prophesize(FileDeleteInterface::class);

        $file = new File();
        $file->setPublicId();
        $type = ProjectFile::PROJECT_FILE_TYPE_GENERAL;

        $manager1->supports($type)->shouldBeCalledOnce()->willReturn(true);
        $manager1->delete($file, $type)->shouldBeCalledOnce();
        $manager2->supports($type)->shouldNotBeCalled();
        $manager2->delete($file, $type)->shouldNotBeCalled();

        $fileDeleteManager = new FileDeleteManager([$manager1->reveal(), $manager2->reveal()]);
        $fileDeleteManager->delete($file, $type);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteWithNothingSupports(): void
    {
        /** @var FileDeleteInterface|ObjectProphecy $manager1 */
        $manager1 = $this->prophesize(FileDeleteInterface::class);

        $file = new File();
        $file->setPublicId();
        $type = 'general';

        $manager1->supports($type)->shouldBeCalledOnce()->willReturn(false);
        $manager1->delete(Argument::cetera())->shouldNotBeCalled();

        static::expectException(NotFoundHttpException::class);

        $fileDeleteManager = new FileDeleteManager([$manager1->reveal()]);
        $fileDeleteManager->delete($file, $type);
    }

    /**
     * @covers ::delete
     *
     * @dataProvider exceptionProvider
     */
    public function testDeleteException(string $exceptionClass): void
    {
        /** @var FileDeleteInterface|ObjectProphecy $manager1 */
        $manager1 = $this->prophesize(FileDeleteInterface::class);

        $file = new File();
        $file->setPublicId();
        $type = ProjectFile::PROJECT_FILE_TYPE_GENERAL;

        $manager1->supports($type)->shouldBeCalledOnce()->willReturn(true);
        $manager1->delete($file, $type)->shouldBeCalledOnce()->willThrow($exceptionClass);

        static::expectException($exceptionClass);

        $fileDeleteManager = new FileDeleteManager([$manager1->reveal()]);
        $fileDeleteManager->delete($file, $type);
    }

    public function exceptionProvider(): iterable
    {
        yield ORMException::class => [ORMException::class];
        yield OptimisticLockException::class => [OptimisticLockException::class];
    }
}
