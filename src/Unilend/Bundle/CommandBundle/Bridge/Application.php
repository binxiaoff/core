<?php

namespace Unilend\Bundle\CommandBundle\Bridge;

use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkBundleApplication;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\{Factory, Lock, Store\SemaphoreStore};

class Application extends FrameworkBundleApplication
{
    /**
     * @inheritDoc
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->getDefinition()->addOption(new InputOption('--multi-process', '-m', InputOption::VALUE_NONE, 'This is a multi process or a single process.'));
    }

    /**
     * @inheritDoc
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->getKernel()->boot();

        if (false === $input->hasParameterOption(['--multi-process', '-m'], false)) {
            $semaphoreStore = new SemaphoreStore();
            $factory        = new Factory($semaphoreStore);
            $commandName    = $this->getCommandName($input);

            if ($commandName) {
                $lock = $factory->createLock($commandName);

                if (false === $lock->acquire()) {
                    $this->getKernel()->getContainer()->get('monolog.logger.console')->warning('The command ' . $this->getCommandName($input) . ' is already running in another process.');
                    $output->writeln('The command ' . $this->getCommandName($input) . ' is already running in another process.');

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
