<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
        /** @var EntityManager $oEntityManager */
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $oSettings */
        $oSettings = $oEntityManager->getRepository('settings');
        $oSettings->get('Adresse notification aucun virement', 'type');
        $sTo = $oSettings->value;

        $varMail = array(
            '$surl' => $sStaticUrl,
            '$url'  => $sUrl
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-aucun-virement', $varMail, false);
        $message->setTo($sTo);
        $mailer = $this->getContainer()->get('mailer');
        $result = $mailer->send($message);
        echo $result;
    }
}
