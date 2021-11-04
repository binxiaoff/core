<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\File;

use KLS\Core\Entity\File;
use KLS\Core\Message\File\FileUploaded;
use KLS\Core\MessageHandler\File\FileUploadedHandler;
use KLS\Core\MessageHandler\File\FileUploadedNotifierInterface;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Core\MessageHandler\File\FileUploadedHandler
 *
 * @internal
 */
class FileUploadedHandlerTest extends TestCase
{
    use PropertyValueTrait;
    use ProphecyTrait;

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

        $notifier1->notify($context)->shouldBeCalledOnce();
        $notifier2->notify($context)->shouldBeCalledOnce();

        $message = new FileUploaded($file, $context);
        $handler = new FileUploadedHandler([$notifier1->reveal(), $notifier2->reveal()]);
        $handler($message);
    }
}
