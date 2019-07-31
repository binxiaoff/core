<?php

namespace Unilend\Command;

use Exception;
use Psr\Log\LoggerInterface;
use SensioLabs\Security\SecurityChecker;
use Swift_Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Repository\SettingsRepository;
use Unilend\Service\SlackManager;
use Unilend\SwiftMailer\TemplateMessageProvider;

/**
 * @todo In order to make it work, some configuration is needed
 *     - Slack manager must be configured
 *     - Slack channel must be created and configured
 *     - "SECURITY_CHECK_NOTIFICATION_EMAIL" setting must be defined
 *     - "notification-security-check" email must be created
 */
class SecurityCheckNotificationCommand extends Command
{
    private const SLACK_CHANNEL = '#to-be-configured';

    /** @var string */
    private $projectDirectory;
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var SlackManager */
    private $slackManager;
    /** @var SettingsRepository */
    private $settingsRepository;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string                  $projectDirectory
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     * @param SlackManager            $slackManager
     * @param SettingsRepository      $settingsRepository
     * @param LoggerInterface         $consoleLogger
     */
    public function __construct(
        string $projectDirectory,
        TemplateMessageProvider $templateMessageProvider,
        Swift_Mailer $mailer,
        SlackManager $slackManager,
        SettingsRepository $settingsRepository,
        LoggerInterface $consoleLogger
    ) {
        $this->projectDirectory        = $projectDirectory;
        $this->templateMessageProvider = $templateMessageProvider;
        $this->mailer                  = $mailer;
        $this->slackManager            = $slackManager;
        $this->settingsRepository      = $settingsRepository;
        $this->logger                  = $consoleLogger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unilend:security:checker:notify')
            ->setDescription('Check known security issues in the packages managed by Composer using SensioLabs Security Advisories Checker')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $checker     = new SecurityChecker();
        $lockFile    = $this->projectDirectory . DIRECTORY_SEPARATOR . 'composer.lock';
        $checkResult = $checker->check($lockFile);

        if (0 === $checkResult->count()) {
            $output->writeln('No vulnerability detected');

            return;
        }

        $content         = 'Vulnerabilities detected in current composer.lock:' . PHP_EOL;
        $vulnerabilities = json_decode($checkResult, true);

        foreach ($vulnerabilities as $packageName => $vulnerability) {
            $content .= $packageName . ': ';
            foreach ($vulnerability['advisories'] as $advisory) {
                $content .= $advisory['title'] . '(' . $advisory['cve'] . '). ';
            }
            $content .= PHP_EOL;
        }

        $this->slackManager->sendMessage($content, self::SLACK_CHANNEL);

        $emailSetting = $this->settingsRepository->findOneBy(['type' => 'SECURITY_CHECK_NOTIFICATION_EMAIL']);

        if ($emailSetting) {
            $recipient = $emailSetting->getValue();
            $message   = $this->templateMessageProvider->newMessage('notification-security-check', ['content' => nl2br($content)]);

            try {
                $message->setTo($recipient);
                $this->mailer->send($message);
            } catch (Exception $exception) {
                $this->logger->error(sprintf('Could not send email "notification-security-check". Error: %s', $exception->getMessage()), [
                    'id_mail_template' => $message->getTemplateId(),
                    'email'            => $recipient,
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine(),
                ]);
            }
        }

        $output->writeln('Vulnerabilities detected. See Slack or email for more details.');
    }
}
