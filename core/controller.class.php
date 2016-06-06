<?php
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Controller implements ContainerAwareInterface
{
    var $Command;
    var $Config;
    var $App;
    var $autoFireHead   = true;
    var $autoFireHeader = true;
    var $autoFireView   = true;
    var $autoFireFooter = true;
    var $autoFireDebug  = true;
    var $catchAll       = false;
    var $bdd;
    var $js;
    var $css;
    var $view;
    var $included_js;
    var $included_css;
    /**
     * @var ContainerInterface
     *
     * @api
     */
    protected $container;

    public $current_template = '';

    final public function __construct(&$command, $config, $app)
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');

        //Variables de session pour la fenetre de debug
        if (isset($_SESSION)) {
            unset($_SESSION['error']);
            unset($_SESSION['debug']);
            unset($_SESSION['msg']);
        }

        $this->Command      = $command;
        $this->Config       = $config;
        $this->App          = $app;
    }

    protected function initialize()
    {
        $this->bdd = $this->get('database_connection');

        $this->included_js  = array();
        $this->included_css = array();

        // Langue et controller
        $this->language           = $this->Command->Language;
        $this->current_controller = $this->Command->getControllerName();
        $this->current_function   = $this->Command->getfunction();

        // Mise en place des chemins
        $this->path       = $this->get('kernel')->getRootDir() . '/../';
        $this->spath      = $this->get('kernel')->getRootDir() . '/../public/default/var/';
        $this->staticPath = $this->get('kernel')->getRootDir() . '/../public/default/';
        $this->logPath    = $this->get('kernel')->getLogDir();
        $this->surl       = $this->get('assets.packages')->getUrl('');
        $this->url        = $this->Config['url'][$this->Config['env']][$this->App];
        $this->lurl       = $this->Config['url'][$this->Config['env']][$this->App] . ($this->Config['multilanguage']['enabled'] ? '/' . $this->language : '');

        //admin
        $this->aurl = $this->Config['url'][$this->Config['env']]['admin'];
        //fo
        $this->furl = $this->Config['url'][$this->Config['env']]['default'];

        // Recuperation du type de plateforme
        $this->cms = $this->Config['cms'];

        //*** SESSION IS DEAD ***//
        if (isset($_POST['killsession'])) {
            //unset ca marche pas, mais ca oui
            $_SESSION = array();
        }
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
        if (!isset($this->params[0])) {
            trigger_error('ASPARTAM - ' . $msg, E_USER_ERROR);
        }
    }

    public function _404()
    {
        header("HTTP/1.0 404 Not Found");
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
        if (!is_callable(array($this, '_' . $FunctionToCall))) {
            if ($this->catchAll == true) {
                $current_params = $this->Command->getParameters();
                $arr            = array(0 => $FunctionToCall);
                $arr            = array_merge($arr, $current_params);
                $this->Command->setParameters($arr);
                $FunctionToCall = 'default';
            } else {
                $FunctionToCall = 'error';
            }
        }
        $this->setView($FunctionToCall);
        $this->params = $this->Command->getParameters();
        call_user_func(array($this, '_' . $FunctionToCall));

        //Affiche le contenu(view) avant le menu(header) si on est en mode seo_optimize
        if ($this->Config['params']['seo_optimize']) {
            if ($this->autoFireHead) {
                $this->fireHead();
            }
            if ($this->autoFireView) {
                $this->fireView();
            }
            if ($this->autoFireHeader) {
                $this->fireHeader();
            }
            if ($this->autoFireFooter) {
                $this->fireFooter();
            }
        } else {
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

        //Affiche une fentre de debug/error si l'option est activée dans le config.php
        if ($this->getParameter('kernel.debug')) {
            $this->fireDebug();
        }
    }


    //Gere l'affichage de l'entete
    public function fireHead($head = '')
    {
        if (empty($head) && ! empty($this->head)) {
            $head = $this->head;
        } elseif (empty($head)) {
            $head = 'head';
        }

        if (!file_exists($this->path . 'apps/' . $this->App . '/views/' . $head . '.php')) {
            call_user_func(array($this, '_error'), 'head not found : views/' . $head . '.php');
        } else {
            include($this->path . 'apps/' . $this->App . '/views/' . $head . '.php');
        }
    }

    //Gere l'affichage du corps de la page
    public function fireView($view = '')
    {
        if (empty($view) && ! empty($this->view)) {
            $view = $this->view;
        }

        if ($view != '') {
            if (!file_exists($this->path . 'apps/' . $this->App . '/views/' . $this->Command->getControllerName() . '/' . $view . '.php')) {
                call_user_func(array(
                    $this, '_error'
                ), 'view not found : views/' . $this->Command->getControllerName() . '/' . $view . '.php');
            } else {
                if ($this->is_view_template && file_exists($this->path . 'apps/' . $this->App . '/controllers/templates/' . $view . '.php')) {
                    include($this->path . 'apps/' . $this->App . '/controllers/templates/' . $view . '.php');
                }

                include($this->path . 'apps/' . $this->App . '/views/' . $this->Command->getControllerName() . '/' . $view . '.php');
            }
        }
    }

    //Gere l'affichage du menu
    public function fireHeader($header = '')
    {
        if (empty($header) && ! empty($this->header)) {
            $header = $this->header;
        } elseif (empty($header)) {
            $header = 'header';
        }
        if (!file_exists($this->path . 'apps/' . $this->App . '/views/' . $header . '.php')) {
            call_user_func(array($this, '_error'), 'header not found : views/' . $header . '.php');
        } else {
            include($this->path . 'apps/' . $this->App . '/views/' . $header . '.php');
        }
    }

    //Gere l'affichage du pied de page
    public function fireFooter($footer = '', $morestats = '')
    {
        $footer = empty($footer) ? (empty($this->footer) ? 'footer' : $this->footer) : $footer;

        if (! file_exists($this->path . 'apps/' . $this->App . '/views/' . $footer . '.php')) {
            call_user_func(array($this, '_error'), 'footer not found : views/' . $footer . '.php');
        } else {
            include $this->path . 'apps/' . $this->App . '/views/' . $footer . '.php';
        }
    }

    //Affiche une fenetre contenant les erreurs eventuelles
    public function fireDebug()
    {
        echo '
            <div style="display: none; overflow:auto; position:fixed; top:95%; left:0px; background-color:#F1EDED;font-size:11px; width:99%; height:400px; z-index:9999; padding:0 0 20px 10px;border-top: 1px solid #919191;margin:-400px auto 20px auto; " id="divdebug" >
                <div style="clear:both;"></div>
                <div style="color: black;">
                    <fieldset style="border:1px solid black; padding:5px; background-color:white;">
                        <legend style="border:1px solid black; padding:2px; background-color:white;"><strong>General:</strong></legend>
                        <table cellpadding="0" cellspacing="0" border="0" style="font-size:12px;">
                            <tr>
                                <td width="150px">Controlleur</td>
                                <td>' . $this->current_controller . '</td>
                            </tr>
                            <tr>
                                <td>Vue</td>
                                <td>' . $this->current_function . '</td>
                            </tr>
                            <tr>
                                <td>Template</td>
                                <td>' . (isset($this->current_template) ? $this->current_template : '') . '</td>
                            </tr>
                            <tr>
                                <td>Mon IP</td>
                                <td>' . $_SERVER['REMOTE_ADDR'] . '</td>
                            </tr>
                            <tr>
                                <td>Base utilis&eacute;e</td>
                                <td>' . $this->getParameter('database_name') . '</td>
                            </tr>
                        </table>
                    </fieldset>
                </div>
                <div style="margin-top: 10px; color: #066500;">
                <fieldset style="border:1px solid #066500; padding:5px; background-color:white;">
                    <legend style="border:1px solid #066500; padding:2px; background-color:white;"><strong>$this->params:</strong></legend>
            ';
        if (count($this->params) > 0) {
            foreach ($this->params as $key => $elem) {
                echo '$this->params[\'' . $key . '\'] = ' . $elem . '<br />';
            }
        }
        echo '
                </fieldset>
                </div>
                <div style="margin-top: 10px; color: #7C0CCF;">
                <fieldset style="border:1px solid #7C0CCF; padding:5px; background-color:white;">
                    <legend style="border:1px solid #7C0CCF; padding:2px; background-color:white;"><strong>$_POST:</strong></legend>
            ';
        if (count($_POST) > 0) {
            foreach ($_POST as $key => $elem) {
                if (is_array($elem)) {
                    echo '$_POST[\'' . $key . '\'] = ';
                    echo '<br />';
                    echo '<PRE>';
                    print_r($elem);
                    echo '</PRE>';
                    echo '<br />';
                } else {
                    echo '$_POST[\'' . $key . '\'] = ' . $elem . '<br />';
                }
            }
        }
        echo '
                </fieldset>
                </div>
                <div style="margin-top: 10px; color: #ff7800;">
                <fieldset style="border:1px solid #ff7800; padding:5px; background-color:white;">
                    <legend style="border:1px solid #ff7800; padding:2px; background-color:white;"><strong>setDebug:</strong></legend>
            ';
        if (isset($_SESSION['msg']) && count($_SESSION['msg']) > 0) {
            foreach ($_SESSION['msg'] as $title => $elem) {
                echo '<PRE>';
                echo($title != '' ? $title . ' : ' : '');
                print_r($elem);
                echo '</PRE>';
            }
        }
        echo '
                </fieldset>
                </div>
                <div style="margin-top: 10px; color: red;">
                    <fieldset style="border:1px solid red; padding:5px; background-color:white;">
                        <legend style="border:1px solid red; padding:2px; background-color:white;"><strong>Errors:</strong></legend>
            ';
        if (isset($_SESSION['error']) && count($_SESSION['error']) > 0) {
            foreach ($_SESSION['error'] as $elem) {
                echo '<PRE>';
                print_r($elem);
                echo '</PRE>';
            }
        }
        echo '
                </fieldset>
                </div>
                <div style="margin-top: 10px; color: #44251F;">
                    <fieldset style="border:1px solid #44251F; padding:5px; background-color:white;">
                        <legend style="border:1px solid #44251F; padding:2px; background-color:white;"><strong>Sessions:</strong></legend>
            ';
        if (count($_SESSION) > 0) {
            foreach ($_SESSION as $key => $elem) {
                if ($key != 'debug' && $key != 'msg' && $key != 'error') {
                    echo '<span style="font-weight:bold;">' . $key . '</span> : ';
                    echo '<PRE>';
                    print_r($elem);
                    echo '</PRE>';
                    echo '<br>';
                }
            }
        }
        echo '
                    </fieldset>
                </div>
                <div style="margin-top: 10px; color:#0096ff;">
                    <fieldset style="border:1px solid #0096ff; padding:5px; background-color:white;">
                        <legend style="border:1px solid #0096ff; padding:2px; background-color:white;"><strong>BDD:</strong></legend>
            ';
        if (isset($_SESSION['debug']) && count($_SESSION['debug']) > 0) {
            foreach ($_SESSION['debug'] as $i => $sQuery) {
                echo '<span>' . ($i == 0 ? '' : '<hr>') . ' ' . $sQuery . '</span>';
            }
        }
        echo '
                    </fieldset>
                </div>
            </div>
            <div style="position:fixed; top:100%; left:0px; width:100%; height:20px; background-color:#F1EDED;border-top: 1px solid #919191;font-size:12px; margin:-20px auto 0 auto;  ">
                <span style="cursor: pointer;" onclick="document.getElementById(\'divdebug\').style.display=\'block\';">[O]</span>
                <span style="cursor: pointer;" onclick="document.getElementById(\'divdebug\').style.display=\'none\';">[X]</span> |
                <span style="color: #ff7800; font-weight:bold;">' . (isset($_SESSION['msg']) ? count($_SESSION['msg']) : 0) . ' setdebug</span> |
                <span style="color: red; font-weight:bold;">' . (isset($_SESSION['error']) ? count($_SESSION['error']) : 0) . ' erreur </span> |
                <span style="color: #0096ff; font-weight:bold;">' . (isset($_SESSION['debug']) ? count($_SESSION['debug']) : 0) . ' requ&ecirc;tes </span> |
                <span style="color: #066500; font-weight:bold;">' . count($this->params) . ' params </span> |
                <span style="color: #7C0CCF; font-weight:bold;">' . count($_POST) . ' post </span> |
                <span style="color: #44251F; font-weight:bold;"> session </span> |
                <span style="color: #000000; font-weight:bold;">
                    <form method="post" style="float:right;">[<input type="submit" name="killsession" value="KILL SESSION" style="border:none; font-weight:bold; cursor:pointer;" />]</form>
                </span>
            </div>
        ';
    }

    //Ajoute une information dans la fenetre de debug
    public function setDebug($var, $title = '')
    {
        if ($title == '') {
            $title = count($_SESSION['msg']);
        }
        $_SESSION['msg'][$title] = $var;
    }

    //Change le head
    public function setHead($head)
    {
        $this->head = $head;
    }

    //Change la vue
    public function setView($view, $is_template = false)
    {
        $this->view             = $view;
        $this->is_view_template = $is_template;
    }

    //Change le header
    public function setHeader($header)
    {
        $this->header = $header;
    }

    //Change le footer
    public function setFooter($footer)
    {
        $this->footer = $footer;
    }

    protected function loadData($object, $params = array())
    {
        return $this->get('unilend.service.entity_manager')->getRepository($object, $params);
    }

    /**
     * @deprecated Each lib will be declared as a service.
     * @param string $library
     * @param array $params
     * @param bool $instanciate
     * @return bool|object
     */
    protected function loadLib($library, $params = array(), $instanciate = true)
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
        if (!array_key_exists($js, $this->included_js)) {
            $this->included_js[$js] = ($ieonly != 0 ? "<!--[if IE " . $ieonly . "]>" : "") . "<script type=\"text/javascript\" src=\"" . $this->surl . "/scripts/" . $js . ".js" . ($version != '' ? '?d=' . $version : '') . "\"></script>" . ($ieonly != 0 ? "<![endif]-->" : "");
        }
    }

    //Supprime un fichier js dans le head
    public function unLoadJs($js)
    {
        if (array_key_exists($js, $this->included_js)) {
            unset($this->included_js[$js]);
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
        if (!array_key_exists($css, $this->included_css)) {
            $this->included_css[$css] = ($ieonly != 0 ? "<!--[if IE " . $ieonly . "]>" : "") . "<link media =\"" . $media . "\" href=\"" . $this->surl . "/styles/" . $css . "." . $type . ($version != '' ? '?d=' . $version : '') . "\" type=\"text/css\" rel=\"stylesheet\" />" . ($ieonly != 0 ? "<![endif]-->" : "");
        }
    }

    //Supprime un fichier css dans le head
    public function unLoadCss($css)
    {
        if (array_key_exists($css, $this->included_css)) {
            unset($this->included_css[$css]);
        }
    }

    //appelle les css passees en param
    public function callCss()
    {
        foreach ($this->included_css as $css) {
            echo $css . "\r\n";
        }
    }

    //Cette fonction construit et renvois l'url a appeler pour passer dans la langue en parametre tout en restant sur la meme page
    //Exemple :<a href=\"<?=\$this->changeLanguage('fr');?\>\"><img src=\"flag-fr.jpg\"></a>
    public function changeLanguage($lang, $current_lang, $is_routage = false)
    {
        if (!$is_routage) {
            $requestURI = explode('/', $_SERVER['REQUEST_URI']);
            $requestURI = array_slice($requestURI, 2);

            $slug = $requestURI[0];
            $tree = $this->loadData('tree');
            $tree->get(array('slug' => $slug, 'id_langue' => $current_lang));

            if ($tree->id_tree > 0) {
                $tree2 = $this->loadData('tree');
                $tree2->get(array('id_tree' => $tree->id_tree, 'id_langue' => $lang));

                if ($tree2->id_tree > 0) {
                    $requestURI[0] = $tree2->slug;
                    $requestURI    = implode('/', $requestURI);
                    return $this->url . '/' . $lang . '/' . $requestURI;
                } else {
                    return $this->url . '/' . $lang . '/';
                }
            } else {
                $requestURI = implode('/', $requestURI);
                return $this->url . '/' . $lang . '/' . $requestURI;
            }
        } else {
            $requestURI = explode('/', $_SERVER['REQUEST_URI']);
        }
    }

    // Redirige vers une autre url avec le bon header si besoin
    public function redirection($url, $type = '')
    {
        if ($type == 301) {
            header("HTTP/1.1 301 Moved Permanently");
        }

        header('location:' . $url);
        die();
    }

    protected function hideDecoration()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }
}
