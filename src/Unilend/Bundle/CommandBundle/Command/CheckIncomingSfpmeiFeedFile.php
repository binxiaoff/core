<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Unilend\Service\Simulator\EntityManager;
use Unilend\core\Loader;

class CheckIncomingSfpmeiFeedFile extends ContainerAwareCommand
{
    CONST FILE_ROOT_NAME = 'UNILEND-00040631007-';
    /** @var  string */
    private $sRootPath;
    protected function configure()
    {
        $this
            ->setName('check-incoming-sfpmei-file')
            ->setDescription('Check if incoming feeds file exists, otherwise send an alert email')
            ->addOption('message-limit', 0, InputOption::VALUE_OPTIONAL, 'The maximum number of messages to send.')
            ->addOption('time-limit', 0, InputOption::VALUE_OPTIONAL, 'The time limit for sending messages (in seconds).')
            ->setHelp(<<<EOF
The <info>check-incoming-sfpmei-file</info> command sends an email if the incoming feeds file does not exists. Usually the file must be available at 10:00 AM.
In order to be useful, this command could run once a day (from monday to friday) at 10:20 AM
<info>php bin/console check-incoming-sfpmei-file</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sFilePath = $this->sRootPath . 'sfpmei/reception/' . self::FILE_ROOT_NAME . date('Ymd') . '.txt';
        if (false === file_exists($sFilePath)) {
            $this->sendMissingReceptionFileMail();
        }
    }

    public function setPath($sPath)
    {
        $this->sRootPath = $sPath;
    }

    /**
     * The file must be available before 10:15 AM otherwise send a notice email
     */
    private function sendMissingReceptionFileMail()
    {
        $aConfig = Loader::loadConfig();
        /** @var EntityManager $oEntityManager */
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $oSettings */
        $oSettings = $oEntityManager->getRepository('settings');
        $oSettings->get('Adresse notification aucun virement', 'type');
        $sTo = $oSettings->value;

        $varMail = array(
            '$surl' => $aConfig['url'][$aConfig['env']]['default'],
            '$url'  => $aConfig['url'][$aConfig['env']]['default']
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-aucun-virement', $varMail, false);
        $message->setTo($sTo);
        $mailer = $this->getContainer()->get('mailer');
        $result = $mailer->send($message);
        echo $result;
    }
}