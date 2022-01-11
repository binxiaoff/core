<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\EventSubscriber\Mailer;

use Faker\Factory;
use KLS\Core\Entity\MailLog;
use KLS\Core\EventSubscriber\Mailer\PreSendMailSubscriber;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Mailer\TraceableEmailInterface;
use KLS\Core\Repository\MailLogRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * @coversDefaultClass \KLS\Core\EventSubscriber\Mailer\PreSendMailSubscriber
 *
 * @internal
 */
class PreSendMailSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var MailLogRepository|ObjectProphecy */
    private $mailLogRepository;
    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    protected function setUp(): void
    {
        $this->mailLogRepository = $this->prophesize(MailLogRepository::class);
        $this->logger            = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @dataProvider errorReportingDataProvider
     *
     * @covers ::enableTemplateErrorReporting
     */
    public function testEnableTemplateErrorReporting(
        bool $enableErrorDelivery,
        ?string $errorReportingEmail,
        MessageEvent $event
    ): void {
        $this->createTestObject($enableErrorDelivery, $errorReportingEmail)->enableTemplateErrorReporting($event);
        $message = $event->getMessage();
        if ($message instanceof MailjetMessage) {
            if ($errorReportingEmail) {
                static::assertSame(
                    $message->getHeaders()->get('X-MJ-TemplateErrorReporting')->getBodyAsString(),
                    $errorReportingEmail
                );
            } else {
                static::assertNull($message->getHeaders()->get('X-MJ-TemplateErrorReporting'));
            }
            if ($enableErrorDelivery) {
                static::assertSame(
                    $message->getHeaders()->get('X-MJ-TemplateErrorDeliver')->getBodyAsString(),
                    'deliver'
                );
            } else {
                static::assertNull($message->getHeaders()->get('X-MJ-TemplateErrorDeliver'));
            }
        } else {
            static::assertNull($message->getHeaders()->get('X-MJ-TemplateErrorReporting'));
            static::assertNull($message->getHeaders()->get('X-MJ-TemplateErrorDeliver'));
        }
    }

    /**
     * @dataProvider logMailDataProvider
     *
     * @covers ::logMessage
     */
    public function testLogMessage(
        bool $enableErrorDelivery,
        ?string $errorReportingEmail,
        MessageEvent $event,
        ?MailLog $mailLog
    ): void {
        $message = $event->getMessage();
        if (false === $message instanceof TraceableEmailInterface) {
            $this->mailLogRepository->findOneBy(Argument::any())->shouldNotBeCalled();
            $this->mailLogRepository->persist(Argument::any())->shouldNotBeCalled();
            $this->mailLogRepository->flush()->shouldNotBeCalled();
        } else {
            if (false === $event->isQueued()) {
                $this->mailLogRepository->findOneBy([
                    'messageId' => $message->getMessageId(),
                ])->shouldBeCalledOnce()->willReturn($mailLog);
            }

            if (null === $mailLog) {
                $this->mailLogRepository->persist(Argument::type(MailLog::class))->shouldBeCalledOnce();
            }
            $this->mailLogRepository->flush()->shouldBeCalledOnce();
        }
        $this->createTestObject($enableErrorDelivery, $errorReportingEmail)->logMessage($event);
    }

    public function errorReportingDataProvider(): iterable
    {
        $faker               = Factory::create('fr_FR');
        $errorReportingEmail = $faker->email();

        yield 'email' => [true, $errorReportingEmail, $this->createMessageEvent(new Email(), true)];
        yield 'email, delivery disabled' => [
            false, $errorReportingEmail, $this->createMessageEvent(new Email(), true),
        ];
        yield 'email, email not set' => [true, null, $this->createMessageEvent(new Email(), true)];
        yield 'email, delivery disabled, email not set' => [
            false, null, $this->createMessageEvent(new Email(), true),
        ];

        yield 'mailjet' => [true, $errorReportingEmail, $this->createMessageEvent(new MailjetMessage(), true)];
        yield 'mailjet, delivery disabled' => [
            false, $errorReportingEmail, $this->createMessageEvent(new MailjetMessage(), true),
        ];
        yield 'mailjet, email not set' => [true, null, $this->createMessageEvent(new MailjetMessage(), true)];
        yield 'mailjet, delivery disabled, email not set' => [
            false, null, $this->createMessageEvent(new MailjetMessage(), true),
        ];
    }

    public function logMailDataProvider(): iterable
    {
        $faker               = Factory::create('fr_FR');
        $errorReportingEmail = $faker->email();

        yield 'rawMessage' => [
            true, $errorReportingEmail, $this->createMessageEvent(new RawMessage($faker->text), true), null,
        ];
        yield 'Message' => [
            true, $errorReportingEmail, $this->createMessageEvent(new Message(), true), null,
        ];
        yield 'Email' => [
            true, $errorReportingEmail, $this->createMessageEvent(new Email(), true), null,
        ];
        yield 'Mailjet' => [
            true, $errorReportingEmail, $this->createMessageEvent(new MailjetMessage(), true), null,
        ];
        $message = new MailjetMessage();
        $event   = $this->createMessageEvent($message, false);
        yield 'Mailjet, not queued' => [
            true,
            $errorReportingEmail,
            $this->createMessageEvent($message, false),
            new MailLog($message, $event->getTransport()),
        ];
    }

    private function createTestObject(bool $enableErrorDelivery, ?string $errorReportingEmail): PreSendMailSubscriber
    {
        return new PreSendMailSubscriber(
            $enableErrorDelivery,
            $errorReportingEmail,
            $this->mailLogRepository->reveal(),
            $this->logger->reveal()
        );
    }

    private function createMessageEvent(RawMessage $mailjetMessage, bool $isQueued): MessageEvent
    {
        $faker     = Factory::create('fr_FR');
        $envelop   = new Envelope(new Address($faker->email()), [new Address($faker->email())]);
        $transport = $faker->asciify('**********');

        return new MessageEvent(clone $mailjetMessage, $envelop, $transport, $isQueued);
    }
}
