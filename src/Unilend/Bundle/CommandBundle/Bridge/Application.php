<?php
namespace Unilend\Bundle\CommandBundle\Bridge;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkBundleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Filesystem\LockHandler;

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

        $container = $this->getKernel()->getContainer();

        foreach ($this->all() as $command) {
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($container);
            }
        }

        $this->setDispatcher($container->get('event_dispatcher'));

        if (! $input->hasParameterOption(array('--multi-process', '-m'), false)) {
            $lock = new LockHandler($this->getCommandName($input));
            if (! $lock->lock()) {
                $container->get('monolog.logger.console')->warning('The command ' . $this->getCommandName($input) . ' is already running in another process.');
                $output->writeln('The command ' . $this->getCommandName($input) . ' is already running in another process.');
                return 0;
            }
        }

        $return = parent::doRun($input, $output);

        if (isset($lock) && $lock instanceof LockHandler) {
            $lock->release();
        }

        return $return;
    }
}
