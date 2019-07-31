<?php

namespace Unilend\Command;

use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Transport_SpoolTransport;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\SwiftMailer\DatabaseSpool;

class SendMailCommand extends ContainerAwareCommand
{
    /** @var Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Swift_Mailer    $mailer
     * @param LoggerInterface $consoleLogger
     */
    public function __construct(Swift_Mailer $mailer, LoggerInterface $consoleLogger)
    {
        $this->mailer = $mailer;
        $this->logger = $consoleLogger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mailer:spool:send')
            ->setDescription('Sends emails from the spool')
            ->addOption('message-limit', null, InputOption::VALUE_OPTIONAL, 'The maximum number of messages to send.')
            ->addOption('time-limit', null, InputOption::VALUE_OPTIONAL, 'The time limit for sending messages (in seconds).')
            ->setHelp(
                <<<'EOF'
The <info>mailer:spool:send</info> command sends all emails from the spool.
<info>php bin/console mailer:spool:send --message-limit=10 --time-limit=10</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transport = $this->mailer->getTransport();

        if ($transport instanceof Swift_Transport_SpoolTransport) {
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
