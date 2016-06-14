<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\DatabaseSpool;

class SendMailCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('mailer:spool:send')
            ->setDescription('Sends emails from the spool')
            ->addOption('message-limit', null, InputOption::VALUE_OPTIONAL, 'The maximum number of messages to send.')
            ->addOption('time-limit', null, InputOption::VALUE_OPTIONAL, 'The time limit for sending messages (in seconds).')
            ->setHelp(<<<EOF
The <info>mailer:spool:send</info> command sends all emails from the spool.
<info>php bin/console mailer:spool:send --message-limit=10 --time-limit=10</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailer    = $this->getContainer()->get('mailer');
        $transport = $mailer->getTransport();
        if ($transport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $transport->getSpool();
            if ($spool instanceof DatabaseSpool) {
                $spool->setMessageLimit($input->getOption('message-limit'));
                $spool->setTimeLimit($input->getOption('time-limit'));
            }
            $sent = $spool->flushQueue($this->getContainer()->get('swiftmailer.transport.real'));
            $output->writeln(sprintf('sent %s emails', $sent));
        }
    }
}
