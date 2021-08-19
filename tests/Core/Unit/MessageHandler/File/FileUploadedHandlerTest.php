<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\File;

use InvalidArgumentException;
use KLS\Core\Entity\File;
use KLS\Core\Message\File\FileUploaded;
use KLS\Core\MessageHandler\File\FileUploadedHandler;
use KLS\Core\MessageHandler\File\FileUploadedNotifierInterface;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Core\MessageHandler\File\FileUploadedHandler
 *
 * @internal
 */
class FileUploadedHandlerTest extends TestCase
{
    use PropertyValueTrait;

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /** @var FileUploadedNotifierInterface|ObjectProphecy $notifier1 */
        $notifier1 = $this->prophesize(FileUploadedNotifierInterface::class);
        /** @var FileUploadedNotifierInterface|ObjectProphecy $notifier2 */
        $notifier2 = $this->prophesize(FileUploadedNotifierInterface::class);

        $context = ['projectId' => 1];
        $file    = new File();
        $this->forcePropertyValue($file, 'id', 1);

        $notifier1->allowsToNotify($context)->shouldBeCalledOnce()->willReturn(true);
        $notifier1->notify($context)->shouldBeCalledOnce();
        $notifier2->allowsToNotify(Argument::any())->shouldNotBeCalled();
        $notifier2->notify(Argument::any())->shouldNotBeCalled();

        $message = new FileUploaded($file, $context);
        $handler = new FileUploadedHandler([$notifier1->reveal(), $notifier2->reveal()]);
        $handler($message);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithNothingSupports(): void
    {
        /** @var FileUploadedNotifierInterface|ObjectProphecy $notifier1 */
        $notifier1 = $this->prophesize(FileUploadedNotifierInterface::class);

        $context = ['id' => 1];
        $file    = new File();
        $this->forcePropertyValue($file, 'id', 42);

        $notifier1->allowsToNotify($context)->shouldBeCalledOnce()->willReturn(false);
        $notifier1->notify(Argument::any())->shouldNotBeCalled();

        static::expectException(InvalidArgumentException::class);

        $message = new FileUploaded($file, $context);
        $handler = new FileUploadedHandler([$notifier1->reveal()]);
        $handler($message);
    }
}
