<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    ArrayInput, InputInterface
};
use Symfony\Component\Console\Output\{
    BufferedOutput, OutputInterface
};

class SecurityCheckNotificationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('unilend:security:checker:notify')
            ->setDescription('Check for known security issues for the packages managed by composer via SensioLabs Security Advisories Checker and notify the IT when finding vulnerabilities)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('security:check');

        $arguments = array(
            'command' => 'security:check',
        );

        $securityCheckInput  = new ArrayInput($arguments);
        $securityCheckOutput = new BufferedOutput();
        $returnCode          = $command->run($securityCheckInput, $securityCheckOutput);

        if ($returnCode > 0) {
            $content = $securityCheckOutput->fetch();

            $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
            $slackManager->sendMessage($content, '#it-monitoring');

            $addressEmail = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse email IT']);

            if ($addressEmail) {
                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-security-check', ['content' => nl2br($content)]);
                try {
                    $message->setTo($addressEmail->getValue());
                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->getContainer()->get('monolog.logger.console')->error(
                        'Could not send email : notification-security-check - Exception: ' . $exception->getMessage(),
                        [
                            'id_mail_template' => $message->getTemplateId(),
                            'email'            => $addressEmail->getVaue(),
                            'class'            => __CLASS__,
                            'function'         => __FUNCTION__,
                            'file'             => $exception->getFile(),
                            'line'             => $exception->getLine()
                        ]
                    );
                }
            }
        }
    }
}
