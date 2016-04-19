<?php
@use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\FileLocator;
use Unilend\Libraries;

class Dispatcher
{
    private $Command;
    private $Config;
    private $Route;
    private $App;
    private $environment;
    private $debug;
    private $container;
    private $rootDir;

    function __construct($config, $app, $route = array(), $environment = 'prod')
    {
        $this->App         = $app;
        $this->Config      = $config;
        $this->Route       = $route;
        $this->path        = $this->getPath();
        $this->environment = $environment;
        $this->debug       = false;

        $this->initializeContainer();
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
        $this->Command = new Command($controllerName, $controllerFunction, $parameters, $langue);
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
        $controller->setContainer($this->container);
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

    // Récupère le chemin des fichiers à partir de la conf pour les inclusions
    public function getPath()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = realpath(dirname($r->getFileName()) . '/..') . '/';
        }

        return $this->rootDir;
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

    /**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    private function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir() . '/' . $class . '.php', $this->debug);
        $fresh = true;
        if (! $cache->isFresh()) {
            $container = $this->buildContainer();
            $container->compile();
            $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

            $fresh = false;
        }

        require_once $cache->getPath();

        $this->container = new $class();
        $this->container->set('kernel', $this);

        if (! $fresh && $this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }

    /**
     * Gets the container class.
     *
     * @return string The container class
     */
    private function getContainerClass()
    {
        return $this->App . ucfirst($this->environment) . 'ProjectContainer';
    }

    /**
     * Gets the container's base class.
     *
     * All names except Container must be fully qualified.
     *
     * @return string
     */
    private function getContainerBaseClass()
    {
        return 'Container';
    }

    public function getCacheDir()
    {
        return $this->path . '/cache/' . $this->environment;
    }

    public function getLogDir()
    {
        return $this->path . '/log';
    }

    public function getConfigDir()
    {
        return $this->path . '/Config';
    }

    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * Builds the service container.
     *
     * @return ContainerBuilder The compiled service container
     *
     * @throws \RuntimeException
     */
    private function buildContainer()
    {
        foreach (array('cache' => $this->getCacheDir(), 'logs' => $this->getLogDir()) as $name => $dir) {
            if (! is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                    throw new \RuntimeException(sprintf("Unable to create the %s directory (%s)\n", $name, $dir));
                }
            } elseif (! is_writable($dir)) {
                throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir));
            }
        }

        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $this->prepareContainer($container);

        if (null !== $cont = $this->registerContainerConfiguration($this->getContainerLoader($container))) {
            $container->merge($cont);
        }

        //$container->addCompilerPass(new AddClassesToCachePass($this));
        //$container->addResource(new EnvParametersResource('SYMFONY__'));

        return $container;
    }

    /**
     * Gets a new ContainerBuilder instance used to build the service container.
     *
     * @return ContainerBuilder
     */
    private function getContainerBuilder()
    {
        $container = new ContainerBuilder(new ParameterBag($this->getKernelParameters()));

        return $container;
    }

    /**
     * Returns the kernel parameters.
     *
     * @return array An array of kernel parameters
     */
    private function getKernelParameters()
    {
        return array_merge(
            array(
                'kernel.root_dir'        => $this->path,
                'kernel.environment'     => $this->environment,
                'kernel.debug'           => $this->debug,
                'kernel.name'            => $this->App,
                'kernel.cache_dir'       => realpath($this->getCacheDir()) ? : $this->getCacheDir(),
                'kernel.logs_dir'        => realpath($this->getLogDir()) ? : $this->getLogDir(),
                'kernel.charset'         => $this->getCharset(),
                'kernel.container_class' => $this->getContainerClass(),
            ),
            $this->getEnvParameters()
        );
    }

    /**
     * Gets the environment parameters.
     *
     * Only the parameters starting with "SYMFONY__" are considered.
     *
     * @return array An array of parameters
     */
    private function getEnvParameters()
    {
        $parameters = array();
        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'UNILEND__')) {
                $parameters[strtolower(str_replace('__', '.', substr($key, 9)))] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Prepares the ContainerBuilder before it is compiled.
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    private function prepareContainer(ContainerBuilder $container)
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $container->registerExtension($extension);
        }
        $container->getCompilerPassConfig()->setMergePass(new \Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass($extension));
    }

    private function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getConfigDir() . '/config_' . $this->environment . '.yml');
    }

    /**
     * Returns a loader for the container.
     *
     * @param ContainerInterface $container The service container
     *
     * @return DelegatingLoader The loader
     */
    private function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator($this->getConfigDir());
        $resolver = new LoaderResolver(array(
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            // Not used for now
            //new IniFileLoader($container, $locator),
            //new PhpFileLoader($container, $locator),
            //new ClosureLoader($container),
        ));

        return new DelegatingLoader($resolver);
    }

    /**
     * Dumps the service container to PHP code in the cache.
     *
     * @param ConfigCache      $cache     The config cache
     * @param ContainerBuilder $container The service container
     * @param string           $class     The name of the class to generate
     * @param string           $baseClass The name of the container's base class
     */
    private function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass)
    {
        // cache the container
        $dumper = new PhpDumper($container);

        $content = $dumper->dump(array('class' => $class, 'base_class' => $baseClass, 'file' => $cache->getPath()));
        if (!$this->debug) {
            $content = static::stripComments($content);
        }

        $cache->write($content, $container->getResources());
    }

    /**
     * Removes comments from a PHP source string.
     *
     * We don't use the PHP php_strip_whitespace() function
     * as we want the content to be readable and well-formatted.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the comments removed
     */
    public static function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $rawChunk = '';
        $output = '';
        $tokens = token_get_all($source);
        $ignoreSpace = false;
        for (reset($tokens); false !== $token = current($tokens); next($tokens)) {
            if (is_string($token)) {
                $rawChunk .= $token;
            } elseif (T_START_HEREDOC === $token[0]) {
                $output .= $rawChunk.$token[1];
                do {
                    $token = next($tokens);
                    $output .= $token[1];
                } while ($token[0] !== T_END_HEREDOC);
                $rawChunk = '';
            } elseif (T_WHITESPACE === $token[0]) {
                if ($ignoreSpace) {
                    $ignoreSpace = false;

                    continue;
                }

                // replace multiple new lines with a single newline
                $rawChunk .= preg_replace(array('/\n{2,}/S'), "\n", $token[1]);
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $ignoreSpace = true;
            } else {
                $rawChunk .= $token[1];

                // The PHP-open tag already has a new-line
                if (T_OPEN_TAG === $token[0]) {
                    $ignoreSpace = true;
                }
            }
        }

        $output .= $rawChunk;

        return $output;
    }

    private function getExtensions()
    {
        return [
            new Libraries\Doctrine\DBAL\DependencyInjection\DoctrineExtension(),
        ];
    }
}
