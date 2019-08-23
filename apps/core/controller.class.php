<?php

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\{ContainerAwareInterface, ContainerInterface};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\{AccessDeniedException, AuthenticationCredentialsNotFoundException};
use Unilend\Security\Voter\BackOfficeZoneVoter;

abstract class Controller implements ContainerAwareInterface
{
    use ControllerTrait;

    /** @var array */
    public $params;
    /** @var Connection */
    public $bdd;
    /** @var Command */
    public $command;
    /** @var string */
    public $app;
    /** @var Request */
    public $request;
    /** @var bool */
    public $autoFireHead = true;
    /** @var bool */
    public $autoFireHeader = true;
    /** @var bool */
    public $autoFireView = true;
    /** @var bool */
    public $autoFireFooter = true;
    /** @var string */
    public $view;
    /** @var int */
    public $maxTableRows = 100;
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    protected $path;
    /** @var string */
    protected $spath;
    /** @var string */
    protected $url;
    /** @var string */
    protected $furl;
    /** @var array */
    private $includedCss = [];
    /** @var array */
    private $includedJs = [];

    /**
     * @param Command      $command
     * @param string       $app
     * @param Request|null $request
     */
    final public function __construct(Command $command, $app, $request = null)
    {
        $this->command = $command;
        $this->app     = $app;
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

    public function execute()
    {
        $this->initialize();

        $this->params = $this->command->getParameters();

        call_user_func([$this, '_' . $this->command->getFunction()]);

        if (empty($this->view)) {
            $this->setView($this->command->getFunction());
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
        if (false === file_exists($this->path . 'apps/' . $this->app . '/views/head.php')) {
            call_user_func([$this, '_error'], 'head not found : views/head.php');
        } else {
            include $this->path . 'apps/' . $this->app . '/views/head.php';
        }
    }

    public function fireView($view = '')
    {
        if (empty($view) && !empty($this->view)) {
            $view = $this->view;
        }

        if (false === empty($view)) {
            if (!file_exists($this->path . 'apps/' . $this->app . '/views/' . $this->command->getControllerName() . '/' . $view . '.php')) {
                call_user_func([
                    $this, '_error',
                ], 'view not found : views/' . $this->command->getControllerName() . '/' . $view . '.php');
            } else {
                include $this->path . 'apps/' . $this->app . '/views/' . $this->command->getControllerName() . '/' . $view . '.php';
            }
        }
    }

    public function fireHeader()
    {
        if (false === file_exists($this->path . 'apps/' . $this->app . '/views/header.php')) {
            call_user_func([$this, '_error'], 'header not found : views/header.php');
        } else {
            include $this->path . 'apps/' . $this->app . '/views/header.php';
        }
    }

    public function fireFooter()
    {
        if (false === file_exists($this->path . 'apps/' . $this->app . '/views/footer.php')) {
            call_user_func([$this, '_error'], 'footer not found : views/footer.php');
        } else {
            include $this->path . 'apps/' . $this->app . '/views/footer.php';
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
        if (false === array_key_exists($js, $this->includedJs)) {
            $this->includedJs[$js] = '<script src="' . $this->url . '/scripts/' . $js . '.js' . ($cacheKey ? '?' . $cacheKey : '') . '"></script>';
        }
    }

    public function callJs(): void
    {
        foreach ($this->includedJs as $js) {
            echo $js . "\n";
        }
    }

    /**
     * @param string      $css
     * @param string|null $cacheKey
     */
    public function loadCss(string $css, ?string $cacheKey = null): void
    {
        if (false === array_key_exists($css, $this->includedCss)) {
            $this->includedCss[$css] = '<link href="' . $this->url . '/styles/' . $css . '.css' . ($cacheKey ? '?' . $cacheKey : '') . '" type="text/css" rel="stylesheet">';
        }
    }

    public function callCss(): void
    {
        foreach ($this->includedCss as $css) {
            echo $css . "\n";
        }
    }

    protected function initialize()
    {
        $this->bdd = $this->get('database_connection');

        $this->path  = $this->get('kernel')->getProjectDir() . DIRECTORY_SEPARATOR;
        $this->spath = $this->path . 'public/default/var/';

        $this->url  = $this->getParameter('router.request_context.scheme') . '://' . getenv('HOST_ADMIN_URL');
        $this->furl = $this->getParameter('router.request_context.scheme') . '://' . getenv('HOST_APP_URL');

        try {
            $this->denyAccessUnlessGranted(BackOfficeZoneVoter::ATTRIBUTE_VIEW, $this->command);
        } catch (AuthenticationCredentialsNotFoundException | AccessDeniedException $exception) {
            // Throw only the AccessDeniedException for login user, as it can also be thrown for anonymous (has anonymous token), in this case, we redirect to login.
            if ($exception instanceof AccessDeniedException && $this->getUser()) {
                throw $exception;
            }

            $this->get('session')->set('_security.default.target_path', $this->request->getUri());

            header('Location: ' . $this->furl . '/login');
            exit;
        }

        $staticsKey = (string) filemtime(__FILE__);

        $this->loadJs('jquery');
        $this->loadJs('jquery-ui/jquery-ui.min');
        $this->loadJs('jquery-ui/jquery-ui.datepicker-fr');
        $this->loadJs('freeow/jquery.freeow.min');
        $this->loadJs('colorbox/jquery.colorbox-min');
        $this->loadJs('tablesorter/jquery.tablesorter.min');
        $this->loadJs('tablesorter/jquery.tablesorter.pager');
        $this->loadJs('main', $staticsKey);

        $this->loadCss('bootstrap');
        $this->loadCss('../scripts/freeow/freeow');
        $this->loadCss('../scripts/colorbox/colorbox');
        $this->loadCss('../scripts/tablesorter/style');
        $this->loadCss('../scripts/jquery-ui/jquery-ui.min');
        $this->loadCss('main', $staticsKey);
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

    /**
     * @param string $string
     *
     * @return string
     */
    protected function generateSlug(string $string): string
    {
        $string = strip_tags($string);

        return URLify::filter($string);
    }
}
