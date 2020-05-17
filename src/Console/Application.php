<?php

declare(strict_types=1);

namespace Unilend\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkBundleApplication;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\{Lock, LockFactory, Store\SemaphoreStore};

class Application extends FrameworkBundleApplication
{
    /**
     * {@inheritdoc}
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->getDefinition()->addOption(new InputOption('--multi-process', null, InputOption::VALUE_NONE, 'This is a multi process or a single process.'));
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->getKernel()->boot();

        if (false === $input->hasParameterOption(['--multi-process'], false)) {
            $semaphoreStore = new SemaphoreStore();
            $factory        = new LockFactory($semaphoreStore);
            $commandName    = $this->getCommandName($input);

            if ($commandName) {
                $lock = $factory->createLock($commandName);

                if (false === $lock->acquire()) {
                    $logger  = $this->getKernel()->getContainer()->get('monolog.logger.console');
                    $message = sprintf('The command %s is already running in another process.', $this->getCommandName($input));
                    if ($logger) {
                        $logger->warning($message);
                    }
                    $output->writeln($message);

                    return 0;
                }
            }
        }

        $return = parent::doRun($input, $output);

        if (isset($lock) && $lock instanceof Lock) {
            $lock->release();
        }

        return $return;
    }
}
