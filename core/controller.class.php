<?php

use Symfony\Component\DependencyInjection\{ContainerAwareInterface, ContainerInterface};
use Symfony\Component\HttpFoundation\Request;
use Unilend\Doctrine\DBAL\Connection;

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
    /** @var Connection */
    public $bdd;
    /** @var string */
    public $view;
    /** @var string */
    public $current_template = '';
    /** @var Request */
    public $request;
    /** @var ContainerInterface */
    protected $container;
    /** @var array */
    private $included_css = [];
    /** @var array */
    private $included_js = [];

    /**
     * @param Command      $command
     * @param string       $app
     * @param Request|null $request
     */
    final public function __construct(Command &$command, $app, $request = null)
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');

        $this->Command = $command;
        $this->App     = $app;
        $this->request = $request;
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
        if (empty($view) && !empty($this->view)) {
            $view = $this->view;
        }

        if ('' != $view) {
            if (!file_exists($this->path . 'apps/' . $this->App . '/views/' . $this->Command->getControllerName() . '/' . $view . '.php')) {
                call_user_func([
                    $this, '_error',
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

    /**
     * Since it can be replaced by Twig filter once the template is migrated to Twig, I create it as a temporary solution to replace the dates::formatDate.
     *
     * @param string $date
     * @param string $format
     *
     * @return string
     */
    public function formatDate(?string $date, $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '';
        }

        $formattedDate = date($format, strtotime($date));

        return $formattedDate ? $formattedDate : '';
    }

    protected function initialize()
    {
        $this->bdd = $this->get('database_connection');

        $this->language           = $this->Command->Language;
        $this->current_controller = $this->Command->getControllerName();
        $this->current_function   = $this->Command->getfunction();

        $this->path  = $this->get('kernel')->getProjectDir() . DIRECTORY_SEPARATOR;
        $this->spath = $this->path . 'public/default/var/';

        $this->url  = $this->getParameter('router.request_context.scheme') . '://' . getenv('HOST_ADMIN_URL');
        $this->furl = $this->getParameter('router.request_context.scheme') . '://' . getenv('HOST_DEFAULT_URL');
        $this->surl = $this->furl;
    }

    protected function loadData($object, $params = [])
    {
        return $this->get('unilend.service.entity_manager')->getRepository($object, $params);
    }

    /**
     * @deprecated each lib will be declared as a service
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
            'data'    => $data,
        ]);

        exit;
    }
}
