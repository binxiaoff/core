<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIncomingSfpmeiFeedFileCommand extends ContainerAwareCommand
{
    const FILE_ROOT_NAME = 'UNILEND-00040631007-';

    protected function configure()
    {
        $this
            ->setName('check:incoming_sfpmei_file')
            ->setDescription('Check if incoming feeds file exists, otherwise send an alert email')
            ->setHelp(<<<EOF
The <info>check-incoming-sfpmei-file</info> command sends an email if the incoming feeds file does not exists. Usually the file must be available at 10:00 AM.
In order to be useful, this command could run once a day (from monday to friday) at 10:20 AM
<info>php bin/console check-incoming-sfpmei-file</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sFilePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/receptions/' . self::FILE_ROOT_NAME . date('Ymd') . '.txt';
        if (false === file_exists($sFilePath)) {
            $this->sendMissingReceptionFileMail();
        }
    }

    /**
     * The file must be available before 10:15 AM otherwise send a notice email
     */
    private function sendMissingReceptionFileMail()
    {
        $sUrl       = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $sStaticUrl = $this->getContainer()->get('assets.packages')->getUrl('');
        /** @var \settings $settings */
        $settings = $settings = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Adresse notification aucun virement', 'type');

        $varMail = array(
            '$surl' => $sStaticUrl,
            '$url'  => $sUrl
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-aucun-virement', $varMail, false);

        try {
            $message->setTo($settings->value);
            $mailer = $this->getContainer()->get('mailer');
            $result = $mailer->send($message);
            echo $result;
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning(
                'Could not send email : notification-aucun-virement - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'email address' => $settings->value, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }
}
