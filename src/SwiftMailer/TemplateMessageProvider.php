<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NotFloran\MjmlBundle\Renderer\BinaryRenderer;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\MailTemplate;
use Unilend\Repository\MailTemplateRepository;

class TemplateMessageProvider
{
    /** @var EntityManagerInterface */
    private $mailTemplateRepository;
    /** @var string */
    private $defaultLocale;
    /** @var LoggerInterface */
    private $logger;
    /** @var Environment */
    private $twig;
    /** @var BinaryRenderer */
    private $mjmlRenderer;

    /**
     * @param MailTemplateRepository $mailTemplateRepository
     * @param BinaryRenderer         $mjmlRenderer
     * @param Environment            $twig
     * @param string                 $defaultLocale
     */
    public function __construct(
        MailTemplateRepository $mailTemplateRepository,
        BinaryRenderer $mjmlRenderer,
        Environment $twig,
        string $defaultLocale
    ) {
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->defaultLocale          = $defaultLocale;
        $this->twig                   = $twig;
        $this->mjmlRenderer           = $mjmlRenderer;
    }

    /**
     * @required
     *
     * @param LoggerInterface|null $logger
     *
     * @return $this
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $templateName
     * @param array  $context
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return TemplateMessage
     */
    public function newMessage(string $templateName, array $context = []): TemplateMessage
    {
        $mailTemplate = $this->mailTemplateRepository->findOneBy(['name' => $templateName, 'locale' => $this->defaultLocale]);

        if (null === $mailTemplate) {
            throw new InvalidArgumentException(sprintf('The mail template %s for the language %s is not found.', $templateName, $this->defaultLocale));
        }

        return $this->newMessageByTemplate($mailTemplate, $context);
    }

    /**
     * @param MailTemplate $mailTemplate
     * @param array        $context
     *
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     *
     * @return TemplateMessage
     */
    public function newMessageByTemplate(MailTemplate $mailTemplate, array $context = []): TemplateMessage
    {
        return $this->setMessageAttributes($mailTemplate, $context);
    }

    /**
     * @param MailTemplate $mailTemplate
     * @param array        $context
     *
     *@throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     *
     * @return TemplateMessage
     */
    private function setMessageAttributes(MailTemplate $mailTemplate, array $context = []): TemplateMessage
    {
        $senderName = $this->twig->createTemplate($mailTemplate->getSenderName())->render($context);
        $subject    = $this->twig->createTemplate($mailTemplate->getSubject())->render($context);
        $body       = $this->mjmlRenderer->render($this->twig->render($mailTemplate->getName(), $context));

        /** @var TemplateMessage $message */
        $message = new TemplateMessage($mailTemplate);
        $message
            ->setVariables($context)
            ->setFrom($mailTemplate->getSenderEmail(), $senderName)
            ->setReplyTo($mailTemplate->getSenderEmail(), $senderName)
            ->setSubject($subject)
            ->setBody($body, 'text/html')
        ;

        if ($this->logger instanceof LoggerInterface) {
            $message->setLogger($this->logger);
        }

        return $message;
    }
}
