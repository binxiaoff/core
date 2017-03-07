<?php

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Controller implements ContainerAwareInterface
{
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
    /** @var bool */
    public $catchAll = false;
    /** @var \Unilend\Bridge\Doctrine\DBAL\Connection */
    public $bdd;
    /** @var string */
    public $view;
    /** @var array */
    public $included_css;
    /** @var array */
    public $included_js;
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    public $current_template = '';
    /** @var  \Symfony\Component\HttpFoundation\Request */
    public $request;

    /**
     * Controller constructor.
     *
     * @param Command $command
     * @param string  $app
     */
    final public function __construct(Command &$command, $app, $request)
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

        $this->included_js  = [];
        $this->included_css = [];

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

        $FunctionToCall = $this->Command->getFunction();

        if ($FunctionToCall == '') {
            $FunctionToCall = 'default';
        }

        if (false === is_callable([$this, '_' . $FunctionToCall])) {
            if ($this->catchAll == true) {
                $current_params = $this->Command->getParameters();
                $arr            = [0 => $FunctionToCall];
                $arr            = array_merge($arr, $current_params);
                $this->Command->setParameters($arr);
                $FunctionToCall = 'default';
            } else {
                $FunctionToCall = 'error';
            }
        }

        $this->setView($FunctionToCall);
        $this->params = $this->Command->getParameters();

        call_user_func([$this, '_' . $FunctionToCall]);

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
                if ($this->is_view_template && file_exists($this->path . 'apps/' . $this->App . '/controllers/templates/' . $view . '.php')) {
                    include $this->path . 'apps/' . $this->App . '/controllers/templates/' . $view . '.php';
                }

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

    public function setView($view, $is_template = false)
    {
        $this->view             = $view;
        $this->is_view_template = $is_template;
    }


    protected function loadData($object, $params = [])
    {
        return $this->get('unilend.service.entity_manager')->getRepository($object, $params);
    }

    /**
     * @deprecated Each lib will be declared as a service.
     * @param string $library
     * @param array  $params
     * @param bool   $instanciate
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

    //Charge un fichier js dans le tableau des js
    public function loadJs($js, $ieonly = 0, $version = '')
    {
        if (! array_key_exists($js, $this->included_js)) {
            $this->included_js[$js] = ($ieonly != 0 ? "<!--[if IE " . $ieonly . "]>" : "") . "<script type=\"text/javascript\" src=\"" . $this->surl . "/scripts/" . $js . ".js" . ($version != '' ? '?d=' . $version : '') . "\"></script>" . ($ieonly != 0 ? "<![endif]-->" : "");
        }
    }

    //appelle les js passees en param
    public function callJs()
    {
        foreach ($this->included_js as $js) {
            echo $js . "\r\n";
        }
    }

    //Charge un fichier css dans le tableau des css
    public function loadCss($css, $ieonly = 0, $media = 'all', $type = 'css', $version = '')
    {
        if (! array_key_exists($css, $this->included_css)) {
            $this->included_css[$css] = ($ieonly != 0 ? "<!--[if IE " . $ieonly . "]>" : "") . "<link media =\"" . $media . "\" href=\"" . $this->surl . "/styles/" . $css . "." . $type . ($version != '' ? '?d=' . $version : '') . "\" type=\"text/css\" rel=\"stylesheet\" />" . ($ieonly != 0 ? "<![endif]-->" : "");
        }
    }

    //appelle les css passees en param
    public function callCss()
    {
        foreach ($this->included_css as $css) {
            echo $css . "\r\n";
        }
    }

    protected function hideDecoration()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
    }
}
