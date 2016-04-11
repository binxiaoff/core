<?php
namespace Unilend\core\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication
{
    private $commandsRegistered = false;

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
        if (! $this->commandsRegistered) {
            $this->registerCommands();

            $this->commandsRegistered = true;
        }

        //$this->addEventComsumer();

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
        $container = new ContainerBuilder();
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../Config'));
        $loader->load('commands.xml');

        $commandServices = $container->findTaggedServiceIds(
            'unilend.command'
        );

        if ($commandServices) {
            foreach ($commandServices as $service => $attributes) {
                $this->add($container->get($service));
            }
        }
    }

    public function getDefinition()
    {
        $inputDefinition =  parent::getDefinition();
        $inputDefinition->addOptions(
            [new InputOption('--multi-process', '-m', InputOption::VALUE_NONE, 'This is a multi process or a single process.')]
        );
        return $inputDefinition;
    }
}
