<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\ServiceTerms;

use Faker\Provider\{Base, Lorem};
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\{Argument, Argument\ArgumentsWildcard, Call\Call};
use ReflectionClassConstant;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};
use Unilend\Service\{FileSystem\FileSystemHelper, ServiceTerms\ServiceTermsGenerator, ServiceTerms\ServiceTermsNotificationSender};
use Unilend\SwiftMailer\{TemplateMessage, TemplateMessageProvider};

/**
 * @coversDefaultClass \Unilend\Service\ServiceTerms\ServiceTermsNotificationSender
 *
 * @internal
 */
class ServiceTermsNotificationSenderTest extends TestCase
{
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var ServiceTermsGenerator */
    private $serviceTermsGenerator;
    /** @var FileSystemHelper */
    private $fileSystemHelper;
    /** @var Swift_Mailer */
    private $mailer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageProvider       = $this->prophesize(TemplateMessageProvider::class);
        $this->serviceTermsGenerator = $this->prophesize(ServiceTermsGenerator::class);
        $this->fileSystemHelper      = $this->prophesize(FileSystemHelper::class);
        $this->mailer                = $this->prophesize(Swift_Mailer::class);
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testSendAcceptedEmail(): void
    {
        $email = Base::lexify('??????@????.com');

        $filepath = Base::lexify('??????/????');

        $client = $this->prophesize(Clients::class);
        $client->getEmail()->willReturn($email);
        $firstName = Base::lexify('??????');
        $client->getFirstName()->willReturn($firstName);
        $client->getLastName()->willReturn(Base::lexify('??????'));

        $acceptationsLegalDoc = new AcceptationsLegalDocs();
        $acceptationsLegalDoc->setClient($client->reveal());

        $filesystem  = $this->prophesize(FilesystemInterface::class);
        $fileContent = Lorem::sentence();
        $filesystem->read(Argument::exact($filepath))->willReturn($fileContent);

        $this->fileSystemHelper->getFileSystemForClass(Argument::exact($acceptationsLegalDoc))->willReturn($filesystem->reveal());
        $this->serviceTermsGenerator->getFilePath(Argument::exact($acceptationsLegalDoc))->willReturn($filepath);
        $this->serviceTermsGenerator->generate(Argument::exact($acceptationsLegalDoc));

        $const = new ReflectionClassConstant(ServiceTermsNotificationSender::class, 'MAIL_TYPE_SERVICE_TERMS_ACCEPTED');

        $this->messageProvider->newMessage(
            Argument::exact($const->getValue()),
            Argument::exact(['firstName' => $firstName])
        )->willReturn(new TemplateMessage(1));

        $sendResult = Base::randomDigitNotNull();

        $this->mailer->send(Argument::type(Swift_Message::class))->willReturn($sendResult);

        $serviceTermsNotificationSender = $this->createTestObject();

        $result = $serviceTermsNotificationSender->sendAcceptedEmail($acceptationsLegalDoc);

        $client->getEmail()->shouldHaveBeenCalled();
        $this->serviceTermsGenerator->generate(Argument::exact($acceptationsLegalDoc))->shouldHaveBeenCalled();
        $this->mailer->send(Argument::type(Swift_Message::class))->shouldHaveBeenCalled();
        $filesystem->read(Argument::exact($filepath))->shouldHaveBeenCalled();
        static::assertSame($sendResult, $result);

        /** @var Call[] $mailerSendCall */
        $mailerSendCall = $this->mailer->findProphecyMethodCalls('send', new ArgumentsWildcard([Argument::type(Swift_Message::class)]));
        /** @var Call $mailerSendCall */
        $mailerSendCall = reset($mailerSendCall);

        $arguments = $mailerSendCall->getArguments();

        /** @var Swift_Message $mail */
        $mail = reset($arguments);
        static::assertSame([$email], array_keys($mail->getTo()));

        $mailChildren = $mail->getChildren();
        static::assertNotEmpty($mailChildren);

        $attachment = reset($mailChildren);

        static::assertInstanceOf(Swift_Attachment::class, $attachment);
        static::assertSame($fileContent, $attachment->getBody());
    }

    /**
     * @return ServiceTermsNotificationSender
     */
    private function createTestObject(): ServiceTermsNotificationSender
    {
        return new ServiceTermsNotificationSender(
            $this->messageProvider->reveal(),
            $this->serviceTermsGenerator->reveal(),
            $this->fileSystemHelper->reveal(),
            $this->mailer->reveal()
        );
    }
}
