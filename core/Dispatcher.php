<?php
namespace Unilend\core;

use Monolog\ErrorHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class Dispatcher
{
    private $Command;
    /** @var  Request */
    private $request;
    /** @var \AppKernel */
    private $kernel;

    public function __construct($kernel, $name, $request)
    {
        $this->kernel      = $kernel;
        $this->request     = $request;
        $this->App         = $name;
        $this->environment = $this->kernel->getEnvironment();
        $this->debug       = $this->kernel->isDebug();
        $this->path        = $this->kernel->getRootDir() . '/../';

        $this->handleUrl();
        $this->handleError();
        $this->dispatch();
    }

    // Gestion de l'URL pour construire la commande : on récupère les paramètres, on met le tout dans un tableau et on essaye de déterminer ce qui parle du controlleur, ce qui parle de l'action et ce qui est à prendre en paramètres.
    public function handleUrl()
    {
        $requestURI   = explode('/', $_SERVER['REQUEST_URI']);
        $scriptName   = explode('/', $_SERVER['SCRIPT_NAME']);
        $commandArray = array_diff_assoc($requestURI, $scriptName);
        $commandArray = array_values($commandArray);

        // Si le dernier élément de l'array est vide (cas où l'on a un / à la fin de l'URL, on enlève la dernière occurence du tableau (puisqu'elle est vide et que donc on s'en fout)
        if ($commandArray[count($commandArray) - 1] == '') {
            unset($commandArray[count($commandArray) - 1]);
        }

        if (empty($commandArray[0])) {
            //si $commandArray[0] est vide c'est qu'on a aucun param dans l'url
            $controllerName     = 'root';
            $controllerFunction = 'default';
            $parameters         = array();
        } elseif (empty($commandArray[1])) {
            //si on a que le premier qui est rempli, on regarde ce que c'est
            //c'est un controller ?
            if ($this->isController($commandArray[0])) {
                $controllerName     = $commandArray[0];
                $controllerFunction = 'default';
                $parameters         = array();
            } elseif ($this->isActionInController('root', $commandArray[0]) === true) {
                //c'est une action, et dans ce cas c'est forcement une action de root
                $controllerName     = 'root';
                $controllerFunction = $commandArray[0];
                $parameters         = array();
            } else {
                //ou bien c'est un paramètre ?
                $controllerName     = 'root';
                $controllerFunction = 'default';
                $parameters         = $commandArray;
            }
        } else {
            //si on a au moins les deux premiers qui sont remplis
            if ($this->isController($commandArray[0])) {
                $controllerName = $commandArray[0];

                if ($this->isActionInController($controllerName, $commandArray[1])) {
                    //on regarde si le deuxième est une fonction
                    $controllerFunction = $commandArray[1];
                    $parameters         = array_slice($commandArray, 2);
                } else {
                    //sinon, c'est qu'on a juste un ctrl et des param
                    $controllerFunction = 'default';
                    $parameters         = array_slice($commandArray, 1);
                }
            } elseif ($this->isActionInController('root', $commandArray[0])) {
                $controllerName     = 'root';
                $controllerFunction = $commandArray[0];
                $parameters         = array_slice($commandArray, 1);
            } else {
                $controllerName     = 'root';
                $controllerFunction = 'default';
                $parameters         = $commandArray;
            }
        }

        $this->Command = new \Command($controllerName, $controllerFunction, $parameters, 'fr');
        $this->newRelic($this->Command);
    }

    public function dispatch()
    {
        $this->fireBootstrap();

        $controllerName = $this->Command->getControllerName();
        $controllerClass = $controllerName . 'Controller';

        include $this->path . 'apps/' . $this->App . '/controllers/' . $controllerName . '.php';

        $controller = new $controllerClass($this->Command, $this->App, $this->request);
        $controller->setContainer($this->kernel->getContainer());
        $controller->execute();
    }

    // Verifie qu'un controller existe (fichier)
    public function isController($controllerName)
    {
        if (file_exists($this->path . 'apps/' . $this->App . '/controllers/' . $controllerName . '.php')) {
            return true;
        } else {
            return false;
        }
    }

    // Test si une action existe dans le controller root
    public function isActionInRootController($action)
    {
        return $this->isActionInController('root', $action);
    }

    // Test si une action existe dans le controller root
    public function isActionInController($controllerName, $action)
    {
        $controller_content = file_get_contents($this->path . 'apps/' . $this->App . '/controllers/' . $controllerName . '.php');

        if (strpos($controller_content, 'function _' . $action . '(') === false && strpos($controller_content, 'function _' . $action . ' (') === false) {
            return false;
        } else {
            return true;
        }
    }

    // Execution (en fait inclusion) du bootstrap s'il existe
    public function fireBootstrap($bootstrap = '')
    {
        if ($bootstrap == '') {
            $bootstrap = 'bootstrap';
        }

        if (! file_exists($this->path . 'apps/' . $this->App . '/' . $bootstrap . '.php')) {
            call_user_func(array($this, '_error'), 'bootstrap not found : ' . $this->App . '/' . $bootstrap . '.php');
        } else {
            include($this->path . 'apps/' . $this->App . '/' . $bootstrap . '.php');
        }
    }

    private function newRelic(\Command $command)
    {
        if ($this->environment !== 'prod' || false === extension_loaded ('newrelic')) {
            return;
        }
        $container = $this->kernel->getContainer();
        $applicationName = $container->getParameter('new_relic.front_app_name');
        if ($this->App === 'admin') {
            $applicationName = $container->getParameter('new_relic.back_app_name');
        }
        $transactionName = $command->getControllerName() . '::' . $command->getFunction();

        newrelic_set_appname ($applicationName);
        newrelic_name_transaction($transactionName);
    }

    private function handleError()
    {
        $logger = $this->kernel->getContainer()->get('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($logger instanceof LoggerInterface) {
            ErrorHandler::register($logger, [], LogLevel::ERROR, LogLevel::ERROR);
        }
    }
}
