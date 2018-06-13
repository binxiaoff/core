<?php

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Controller implements ContainerAwareInterface
{
    /** @var array */
    public $params;
    /** @var Command */
    public $Command;
    /** @var string */
    public $App;
    /** @var bool */
    public $autoFireHead = true;
    /** @var bool */
    public $autoFireHeader = true;
    /** @var bool */
    public $autoFireView = true;
    /** @var bool */
    public $autoFireFooter = true;
    /** @var \Unilend\Bridge\Doctrine\DBAL\Connection */
    public $bdd;
    /** @var string */
    public $view;
    /** @var array */
    private $included_css = [];
    /** @var array */
    private $included_js = [];
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    public $current_template = '';
    /** @var \Symfony\Component\HttpFoundation\Request */
    public $request;
    /** @var Twig_Environment */
    private $twigEnvironment;

    /**
     * @param Command $command
     * @param string  $app
     * @param \Symfony\Component\HttpFoundation\Request|null  $request
     */
    final public function __construct(Command &$command, $app, $request = null)
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');

        $this->Command = $command;
        $this->App     = $app;
        $this->request = $request;
    }

    protected function initialize()
    {
        $this->bdd = $this->get('database_connection');

        $this->language           = $this->Command->Language;
        $this->current_controller = $this->Command->getControllerName();
        $this->current_function   = $this->Command->getfunction();

        $this->path       = $this->get('kernel')->getRootDir() . '/../';
        $this->spath      = $this->get('kernel')->getRootDir() . '/../public/default/var/';
        $this->staticPath = $this->get('kernel')->getRootDir() . '/../public/default/';
        $this->logPath    = $this->get('kernel')->getLogDir();

        $this->surl = $this->get('assets.packages')->getUrl('');
        $this->url  = $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_' . $this->App);
        $this->aurl = $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_admin');
        $this->furl = $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default');
        $this->lurl = $this->url;
    }

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function _default()
    {

    }

    public function _error($msg = '')
    {
        if (false === isset($this->params[0])) {
            trigger_error('ASPARTAM - ' . $msg, E_USER_ERROR);
        }
    }

    public function _404()
    {
        header('HTTP/1.0 404 Not Found');
        echo 'Page not found';
        die;
    }

    public function execute()
    {
        $this->initialize();

        $this->params = $this->Command->getParameters();

        call_user_func([$this, '_' . $this->Command->getFunction()]);

        if (null === $this->twigEnvironment) {
            if (empty($this->view)) {
                $this->setView($this->Command->getFunction());
            }
            if ($this->autoFireHead) {
                $this->fireHead();
            }
            if ($this->autoFireHeader) {
                $this->fireHeader();
            }
            if ($this->autoFireView) {
                $this->fireView();
            }
            if ($this->autoFireFooter) {
                $this->fireFooter();
            }
        }
    }

    public function fireHead()
    {
        if (false === file_exists($this->path . 'apps/' . $this->App . '/views/head.php')) {
            call_user_func([$this, '_error'], 'head not found : views/head.php');
        } else {
            include $this->path . 'apps/' . $this->App . '/views/head.php';
        }
    }

    public function fireView($view = '')
    {
        if (empty($view) && ! empty($this->view)) {
            $view = $this->view;
        }

        if ($view != '') {
            if (! file_exists($this->path . 'apps/' . $this->App . '/views/' . $this->Command->getControllerName() . '/' . $view . '.php')) {
                call_user_func([
                    $this, '_error'
                ], 'view not found : views/' . $this->Command->getControllerName() . '/' . $view . '.php');
            } else {
                include $this->path . 'apps/' . $this->App . '/views/' . $this->Command->getControllerName() . '/' . $view . '.php';
            }
        }
    }

    public function fireHeader()
    {
        if (false === file_exists($this->path . 'apps/' . $this->App . '/views/header.php')) {
            call_user_func([$this, '_error'], 'header not found : views/header.php');
        } else {
            include $this->path . 'apps/' . $this->App . '/views/header.php';
        }
    }

    public function fireFooter()
    {
        if (false === file_exists($this->path . 'apps/' . $this->App . '/views/footer.php')) {
            call_user_func([$this, '_error'], 'footer not found : views/footer.php');
        } else {
            include $this->path . 'apps/' . $this->App . '/views/footer.php';
        }
    }

    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * @param string $template
     * @param array  $context
     * @param bool   $return
     *
     * @return string
     */
    public function render($template = null, array $context = [], $return = false)
    {
        $this->initializeTwig();

        if (null === $template) {
            $template = $this->Command->getControllerName() . '/' . $this->Command->getFunction() . '.html.twig';
        }

        try {
            $this->twigEnvironment->loadTemplate($template);
        } catch (\Twig_Error $exception) {
            $template = 'error.html.twig';
            $context['errorMessage'] = $exception->getMessage();
        }

        $context['app'] += [
            'environment' => $this->getParameter('kernel.environment'),
            'adminUrl'    => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_admin'),
            'frontUrl'    => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default'),
            'staticUrl'   => $this->get('assets.packages')->getUrl(''),
            'parameters'  => $this->Command->getParameters()
        ];

        $content = $this->twigEnvironment->render($template, $context);

        if ($return) {
            return $content;
        }

        echo $content;
        exit;
    }

    /**
     * @internal
     */
    private function initializeTwig()
    {
        $kernel                = $this->get('kernel');
        $loader                = new Twig_Loader_Filesystem($kernel->getRootDir() . '/../apps/' . $this->App . '/views');
        $this->twigEnvironment = new Twig_Environment($loader, [
            'autoescape' => false,
            'cache'      => $kernel->getCacheDir() . '/twig',
            'debug'      => 'prod' !== $this->get('kernel')->getEnvironment()
        ]);
        $this->twigEnvironment->addExtension(new Twig_Extension_Debug());
        $this->twigEnvironment->addExtension(new Twig_Extensions_Extension_Intl());
        $this->twigEnvironment->addExtension(new Symfony\Bridge\Twig\Extension\TranslationExtension($this->get('translator')));
        $this->twigEnvironment->addFilter(new Twig_SimpleFilter('addslashes', 'addslashes'));

    }

    protected function loadData($object, $params = [])
    {
        return $this->get('unilend.service.entity_manager')->getRepository($object, $params);
    }

    /**
     * @deprecated Each lib will be declared as a service.
     *
     * @param string $library
     * @param array  $params
     * @param bool   $instanciate
     *
     * @return bool|object
     */
    protected function loadLib($library, $params = [], $instanciate = true)
    {
        return \Unilend\core\Loader::loadLib($library, $params, $instanciate);
    }

    protected function get($service)
    {
        return $this->container->get($service);
    }

    /**
     * Gets a container configuration parameter by its name.
     *
     * @param string $name The parameter name
     *
     * @return mixed
     */
    protected function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * @param string      $js
     * @param string|null $cacheKey
     */
    public function loadJs(string $js, ?string $cacheKey = null): void
    {
        if (false === array_key_exists($js, $this->included_js)) {
            $this->included_js[$js] = '<script src="' . $this->surl . '/scripts/' . $js . '.js' . ($cacheKey ? '?' . $cacheKey : '') . '"></script>';
        }
    }

    public function callJs(): void
    {
        foreach ($this->included_js as $js) {
            echo $js . "\n";
        }
    }

    /**
     * @param string      $css
     * @param string|null $cacheKey
     */
    public function loadCss(string $css, ?string $cacheKey = null): void
    {
        if (false === array_key_exists($css, $this->included_css)) {
            $this->included_css[$css] = '<link media="all" href="' . $this->surl . '/styles/' . $css . '.css' . ($cacheKey ? '?' . $cacheKey : '') . '" type="text/css" rel="stylesheet">';
        }
    }

    public function callCss(): void
    {
        foreach ($this->included_css as $css) {
            echo $css . "\n";
        }
    }

    protected function hideDecoration()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
    }

    /**
     * @param bool       $success
     * @param mixed|null $data
     * @param array|null $errors
     * @param mixed|null $id
     */
    protected function sendAjaxResponse($success, $data = null, array $errors = null, $id = null)
    {
        header('Content-Type: application/json');

        echo json_encode([
            'success' => $success,
            'error'   => $errors,
            'id'      => $id,
            'data'    => $data
        ]);

        exit;
    }
}
