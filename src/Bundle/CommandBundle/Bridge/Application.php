<?php
namespace Unilend\Bundle\CommandBundle\Bridge;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Filesystem\LockHandler;

class Application extends BaseApplication
{
    /** @var KernelInterface  */
    private $kernel;
    /** @var bool  */
    private $commandsRegistered = false;

    /**
     * Application constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct('Unilend Console', '1.0');

        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $kernel->getEnvironment()));
        $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));
        $this->getDefinition()->addOption(new InputOption('--multi-process', '-m', InputOption::VALUE_NONE, 'This is a multi process or a single process.'));
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return KernelInterface A KernelInterface instance
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->boot();
        
        if (! $this->commandsRegistered) {
            $this->registerCommands();

            $this->commandsRegistered = true;
        }
        /** @var ContainerBuilder $container */
        $container = $this->kernel->getContainer();

        foreach ($this->all() as $command) {
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($container);
            }
        }

        $this->setDispatcher($container->get('event_dispatcher'));

        if (! $input->hasParameterOption(array('--multi-process', '-m'), false)) {
            $lock = new LockHandler($this->getName());
            if (! $lock->lock()) {
                $output->writeln('The command is already running in another process.');

                return 0;
            }
        }

        $return = parent::doRun($input, $output);

        if (isset($lock) && $lock instanceof LockHandler) {
            $lock->release();
        }

        return $return;
    }

    /**
     * Register all the commands declared as a service (that has the unilend.command tag.
     */
    protected function registerCommands()
    {
        /** @var ContainerBuilder $container */
        $container = $this->kernel->getContainer();

        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof Bundle) {
                $bundle->registerCommands($this);
            }
        }

        if ($container->hasParameter('console.command.ids')) {
            foreach ($container->getParameter('console.command.ids') as $id) {
                $this->add($container->get($id));
            }
        }
    }
}
