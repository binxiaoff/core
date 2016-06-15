<?php
namespace Unilend\core;

class Dispatcher
{
    private $Command;
    private $Config;
    private $Route;
    /** @var  Kernel */
    private $kernel;

    public function __construct($kernel, $name, $config,$route = array())
    {
        $this->kernel      = $kernel;
        $this->Route       = $route;
        $this->Config      = $config;
        $this->App         = $name;
        $this->environment = $this->kernel->getEnvironment();
        $this->debug       = $this->kernel->isDebug();
        $this->path        = $this->kernel->getRootDir() . '/../';

        $this->handleUrl();
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

        // Dans le cas ou le mode multilingue est activé, on va utiliser le premier élément de l'URL pour donner la langue
        if ($this->Config['multilanguage']['enabled']) {
            // Le mode litéral (le seul qui existe aujourd'hui, par opposition à un éventuel mode en session) est le mode dans lequel la langue apparait dans l'URL
            if ($this->Config['multilanguage']['mode'] == 'literal') {
                // Si le premier élément de l'URL ne fait pas partie des langues activées dans la conf, on rajoute la langue par défaut au début de l'URL
                if (! array_key_exists($commandArray[0], $this->Config['multilanguage']['allowed_languages'])) {
                    //on regarde si le sous domaine a une langue par défaut
                    if (array_key_exists($_SERVER['HTTP_HOST'], $this->Config['multilanguage']['domain_default_languages'])) {
                        $langue = $this->Config['multilanguage']['domain_default_languages'][$_SERVER['HTTP_HOST']];
                        header('location:/' . $langue . '' . $_SERVER['REQUEST_URI']);
                        die();
                    } //sinon on prend la premiere langue du tableau
                    else {
                        $array  = array_keys($this->Config['multilanguage']['allowed_languages']);
                        $langue = $array[0];
                        header('location:/' . $langue . '' . $_SERVER['REQUEST_URI']);
                        die();
                    }
                } // Sinon, le premier élément de l'URL nous donne la langue
                else {
                    $langue       = $commandArray[0];
                    $commandArray = array_slice($commandArray, 1);
                }
            }
        } else {
            //on regarde si le sous domaine a une langue par défaut
            if (array_key_exists($_SERVER['HTTP_HOST'], $this->Config['multilanguage']['domain_default_languages'])) {
                $langue = $this->Config['multilanguage']['domain_default_languages'][$_SERVER['HTTP_HOST']];
            } else {
                $array  = array_keys($this->Config['multilanguage']['allowed_languages']);
                $langue = $array[0];
            }
        }

        if (empty($commandArray[0])) {
            //si $commandArray[0] est vide c'est qu'on a aucun param dans l'url
            $controllerName     = 'root';
            $controllerFunction = 'default';
            $parameters         = array();
        } elseif (empty($commandArray[1])) {
            //si on a que le premier qui est rempli, on regarde ce que c'est
            //c'est un controller ?
            if ($this->Config['params']['routage'] == true && $this->isInRoute($langue, $commandArray[0], 'default')) {
                $controllerName     = $this->Route[$langue][$commandArray[0]]['default']['ctrl'];
                $controllerFunction = $this->Route[$langue][$commandArray[0]]['default']['fct'];
                $parameters         = array();
            } elseif ($this->Config['params']['routage'] == true && $this->isInRoute($langue, 'root', $commandArray[0])) {
                //c'est une fonction de root ?
                $controllerName     = $this->Route[$langue]['root'][$commandArray[0]]['ctrl'];
                $controllerFunction = $this->Route[$langue]['root'][$commandArray[0]]['fct'];
                $parameters         = array();
            } elseif ($this->isController($commandArray[0])) {
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
            if ($this->Config['params']['routage'] == true && $this->isInRoute($langue, $commandArray[0], $commandArray[1])) {
                //on regarde ce qu'est le premier
                //c'est un couple controller/view ?
                $controllerName     = $this->Route[$langue][$commandArray[0]][$commandArray[1]]['ctrl'];
                $controllerFunction = $this->Route[$langue][$commandArray[0]][$commandArray[1]]['fct'];
                $parameters         = array_slice($commandArray, 2);
            } elseif ($this->Config['params']['routage'] == true && $this->isInRoute($langue, $commandArray[0], 'default')) {
                //c'est un controller avec la vue default et un/des parametres ?
                $controllerName     = $this->Route[$langue][$commandArray[0]]['default']['ctrl'];
                $controllerFunction = $this->Route[$langue][$commandArray[0]]['default']['fct'];
                $parameters         = array_slice($commandArray, 2);
            } elseif ($this->Config['params']['routage'] == true && $this->isInRoute($langue, 'root', $commandArray[0])) {
                //c'est le controller root avec une vue et un/des parametres ?
                $controllerName     = $this->Route[$langue]['root'][$commandArray[0]]['ctrl'];
                $controllerFunction = $this->Route[$langue]['root'][$commandArray[0]]['fct'];
                $parameters         = array_slice($commandArray, 2);
            } elseif ($this->isController($commandArray[0])) {
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

        // Si le mode de traitement des paramètres est littéral, ce qui veut dire que le nom du paramètre et sa valeur sont présents dans l'URL, on va parcourir notre tableau de paramètres pour l'indexer avec le nom des paramètres, pour plus de simplicité à l'appel (mais des URL plus compliquées)
        if ($this->Config['params']['mode'] == 'literal' && $requestURI[1] != 'admin') {
            $i = 0;
            foreach ($parameters as $p) {
                $var = explode($this->Config['params']['separator'], $p);
                if (! empty($var[1])) {
                    $tmp[$var[0]] = $var[1];
                } else {
                    $tmp[$i] = $p;
                }

                $i++;
            }
            if ($i > 0) {
                $parameters = $tmp;
            }
        }

        // Enfin, on va construire notre commande, c'est la fin du traitement de l'URL
        $this->Command = new \Command($controllerName, $controllerFunction, $parameters, $langue);
    }

    public function dispatch()
    {
        // On inclus le bootstrap, qui va hériter du controller, et qui va regrouper toutes les actions à exécuter sur la totalité du site
        $this->fireBootstrap();

        // On récupère dans l'object command créé lors du traitement de l'URL notre controller
        $controllerName = $this->Command->getControllerName();

        // On va alors inclure le fichier du controller
        include($this->path . 'apps/' . $this->App . '/controllers/' . $controllerName . '.php');
        $controllerClass = $controllerName . "Controller";
        // Et l'executer
        $controller = new $controllerClass($this->Command, $this->Config, $this->App);
        $controller->setContainer($this->kernel->getContainer());
        $controller->execute();
    }

    //regarde si le param est dans la table de routage
    public function isInRoute($ln, $ctrl, $fct)
    {
        if ($ln == '' || $ctrl == '' || $fct == '') {
            $alors = false;
        } else {
            if ($this->Route[$ln][$ctrl][$fct]['ctrl'] != '' && $this->Route[$ln][$ctrl][$fct]['fct'] != '') {
                $alors = true;
            } else {
                $alors = false;
            }
        }

        return $alors;
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
}
