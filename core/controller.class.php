<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

use Unilend\librairies\Cache;

class Controller
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

    public $current_template = '';

    /**
     * @var Cache
     */
    public $oCache;

    public function __construct(&$command, $config, $app)
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');

        $this->initUnilendAutoload();
        $this->oCache = Cache::getInstance();

        //Variables de session pour la fenetre de debug
        unset($_SESSION['error']);
        unset($_SESSION['debug']);
        unset($_SESSION['msg']);

        // Construction
        $this->Command      = $command;
        $this->Config       = $config;
        $this->App          = $app;
        $this->included_js  = array();
        $this->included_css = array();
        $this->bdd          = new bdd($this->Config['bdd_config'][$this->Config['env']], $this->Config['bdd_option'][$this->Config['env']]);

        // Initialisation des propriétés nécessaires au cache
        $this->enableCache      = false;
        $this->cacheDuration    = $this->Config['cacheDuration'][$this->Config['env']];
        $this->cacheCurrentPage = false;

        // Langue et controller
        $this->language           = $this->Command->Language;
        $this->current_controller = $this->Command->getControllerName();
        $this->current_function   = $this->Command->getfunction();

        // Mise en place des chemins
        $this->path       = $this->Config['path'][$this->Config['env']];
        $this->spath      = $this->Config['user_path'][$this->Config['env']];
        $this->staticPath = $this->Config['static_path'][$this->Config['env']];
        $this->logPath    = $this->Config['log_path'][$this->Config['env']];
        $this->surl       = $this->Config['static_url'][$this->Config['env']];
        $this->url        = $this->Config['url'][$this->Config['env']][$this->App];
        $this->lurl       = $this->Config['url'][$this->Config['env']][$this->App] . ($this->Config['multilanguage']['enabled'] ? '/' . $this->language : '');

        //admin
        $this->aurl = $this->Config['url'][$this->Config['env']]['admin'];
        //fo
        $this->furl = $this->Config['url'][$this->Config['env']]['default'];

        // Bypass le htaccess
        $this->bp_url = $this->Config['bypass_htaccess_url'][$this->Config['env']];

        // Recuperation du type de plateforme
        $this->cms = $this->Config['cms'];

        //*** SESSION IS DEAD ***//
        if (isset($_POST['killsession'])) {
            //unset ca marche pas, mais ca oui
            $_SESSION = array();
        }
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

    public function initErrorHandling()
    {

        if (file_exists($this->path . 'core/errorhandler.class.php')) {
            include($this->path . 'core/errorhandler.class.php');
            $this->ErrorHandler = new ErrorHandler($this->Config, $this->App, $this->bdd);
            set_error_handler(array($this->ErrorHandler, 'errorHandler'));
        }

    }

    public function execute()
    {
        $FunctionToCall = $this->Command->getFunction();
        if ($FunctionToCall == '') {
            $FunctionToCall = 'default';
        }
        if (!is_callable(array(&$this, '_' . $FunctionToCall))) {
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
        call_user_func(array(&$this, '_' . $FunctionToCall));

        // Si la page courante doit être cachée, on cherche la page en cache ou on initie le processus de création de la version en cache
        if ($this->cacheCurrentPage) {
            $this->initCache();
        }

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

        // Si la page courante doit être cachée, termine le boulot de création du cache
        if ($this->cacheCurrentPage) {
            $this->completeCache();
        }

        //Affiche une fentre de debug/error si l'option est activée dans le config.php
        if (($this->Config['bdd_option'][$this->Config['env']]['DEBUG_DISPLAY'] || $this->Config['bdd_option'][$this->Config['env']]['DISPLAY_ERREUR']) && in_array($_SERVER['REMOTE_ADDR'], $this->Config['ip_admin'][$this->Config['env']]) && $this->autoFireDebug) {
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
            call_user_func(array(&$this, '_error'), 'head not found : views/' . $head . '.php');
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
                    &$this, '_error'
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
            call_user_func(array(&$this, '_error'), 'header not found : views/' . $header . '.php');
        } else {
            include($this->path . 'apps/' . $this->App . '/views/' . $header . '.php');
        }
    }

    //Gere l'affichage du pied de page
    public function fireFooter($footer = '', $morestats = '')
    {
        $footer = empty($footer) ? (empty($this->footer) ? 'footer' : $this->footer) : $footer;

        if (! file_exists($this->path . 'apps/' . $this->App . '/views/' . $footer . '.php')) {
            call_user_func(array(&$this, '_error'), 'footer not found : views/' . $footer . '.php');
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
								<td>' . $this->Config['bdd_config'][$this->Config['env']]['BDD'] . '</td>
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
        if (count($_SESSION['debug']) > 0) {
            foreach ($_SESSION['debug'] as $i => $elem) {
                echo '<span title="Time = ' . $elem['time'] . '" ' . (($elem['time']) > $this->Config['bdd_option'][$this->Config['env']]['BDD_PANIC_SEUIL'] ? 'style="color:red;font-weight:bold"' : '') . '>' . ($i == 0 ? '' : '<hr>') . ' ' . $elem['requete'] . '</span>';
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

    //Cree une nouvelle instance d'un objet
    public function loadData($object, $params = '', $db = '')
    {
        if ($db == '') {
            $db = $this->bdd;
        }

        if ($params == '') {
            $params = array();
        }

        //On regarde si la classe mere existe, si elle n'existe pas, on la genere
        if (!file_exists($this->path . 'data/crud/' . $object . '.crud.php')) {
            //generation de la classe mere
            if (!$this->generateCRUD($object)) {
                return;
            }
        }
        //On include la classe mere
        include_once($this->path . 'data/crud/' . $object . '.crud.php');

        //On regarde si la classe fille existe, si elle n'existe pas, on la genere
        if (!file_exists($this->path . 'data/' . $object . '.data.php')) {
            //generation de la classe mere
            $this->generateDATA($object);
        }
        //On include la classe fille
        include_once($this->path . 'data/' . $object . '.data.php');

        return new $object($db, $params);
    }

    //Cree une nouvelle instance d'une librairie
    public function loadLib($library, $params = '', $instanciate = true)
    {
        if ($params == '') {
            $params = array();
        }
        $path    = '';
        $tableau = explode("/", $library);
        if (count($tableau) > 1) {
            $library = $tableau[count($tableau) - 1];
            unset($tableau[count($tableau) - 1]);
            $path = implode("/", $tableau) . '/';
        }
        if (!file_exists($this->path . 'librairies/' . $path . $library . '.class.php')) {
            call_user_func(array(
                &$this, '_error'
            ), 'library not found : ' . $this->path . 'librairies/' . $path . $library . '.class.php');
            return false;
        } else {
            include_once($this->path . 'librairies/' . $path . $library . '.class.php');

            if ($instanciate) {
                return new $library($params);
            }
        }
    }

    //Charge un fichier js dans le tableau des js
    public function loadJs($js, $ieonly = 0, $version = '')
    {
        if (!array_key_exists($js, $this->included_js)) {
            $this->included_js[$js] = ($ieonly != 0 ? "<!--[if IE " . $ieonly . "]>" : "") . "<script type=\"text/javascript\" src=\"" . $this->Config['static_url'][$this->Config['env']] . "/scripts/" . $js . ".js" . ($version != '' ? '?d=' . $version : '') . "\"></script>" . ($ieonly != 0 ? "<![endif]-->" : "");
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
            $this->included_css[$css] = ($ieonly != 0 ? "<!--[if IE " . $ieonly . "]>" : "") . "<link media =\"" . $media . "\" href=\"" . $this->Config['static_url'][$this->Config['env']] . "/styles/" . $css . "." . $type . ($version != '' ? '?d=' . $version : '') . "\" type=\"text/css\" rel=\"stylesheet\" />" . ($ieonly != 0 ? "<![endif]-->" : "");
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

    //Genere un fichier CRUD a partir d'une table
    public function generateCRUD($table)
    {
        //On recupere la structure de la table
        $sql    = "desc " . $table;
        $result = $this->bdd->query($sql);

        if ($result) {
            //On compte le nombre de cle primaire
            $nb_cle = 0;
            while ($record = $this->bdd->fetch_array($result)) {
                if ($record['Key'] == 'PRI') {
                    $nb_cle++;
                }
            }

            //On recupere la structure de la table
            $sql    = "desc " . $table;
            $result = $this->bdd->query($sql);

            //initialisation
            $slug           = false;
            $declaration    = '';
            $initialisation = '';
            $remplissage    = '';
            $escapestring   = '';
            $updatefields   = '';
            $clist          = '';
            $cvalues        = '';

            while ($record = $this->bdd->fetch_array($result)) {
                $declaration    .= "\tpublic \$" . $record['Field'] . ";\r\n";
                $initialisation .= "\t\t\$this->" . $record['Field'] . " = '';\r\n";
                $remplissage    .= "\t\t\t\$this->" . $record['Field'] . " = \$record['" . $record['Field'] . "'];\r\n";
                $escapestring   .= "\t\t\$this->" . $record['Field'] . " = \$this->bdd->escape_string(\$this->" . $record['Field'] . ");\r\n";

                //On stock les clé primaire dans un tableau
                if ($record['Key'] == 'PRI') {
                    $id[] = $record['Field'];
                }

                if ($record['Key'] != 'PRI' && $record['Field'] != 'updated') {
                    $updatefields .= "`" . $record['Field'] . "`=\"'.\$this->" . $record['Field'] . ".'\",";
                } elseif ($record['Field'] == 'updated') {
                    $updatefields .= "`" . $record['Field'] . "`=NOW(),";
                }

                //On check si il y a un slug present dans les champs
                if ($record['Field'] == 'slug') {
                    $slug = true;
                }

                //Si la clé primaire est unique, c'est un autoincrémente donc on l'exclus de la liste
                if ($nb_cle == 1) {
                    if ($record['Key'] != 'PRI') {
                        $clist .= "`" . $record['Field'] . "`,";
                    }

                    if ($record['Key'] != 'PRI' && $record['Field'] != 'updated' && $record['Field'] != 'added' && $record['Field'] != 'hash') {
                        $cvalues .= "\"'.\$this->" . $record['Field'] . ".'\",";
                    } elseif ($record['Field'] == 'updated' || $record['Field'] == 'added') {
                        $cvalues .= "NOW(),";
                    } elseif ($record['Field'] == 'hash') {
                        $cvalues .= "md5(UUID()),";
                    }
                } else {
                    $clist .= "`" . $record['Field'] . "`,";

                    if ($record['Field'] != 'updated' && $record['Field'] != 'added' && $record['Field'] != 'hash') {
                        $cvalues .= "\"'.\$this->" . $record['Field'] . ".'\",";
                    } elseif ($record['Field'] == 'updated' || $record['Field'] == 'added') {
                        $cvalues .= "NOW(),";
                    } elseif ($record['Field'] == 'hash') {
                        $cvalues .= "md5(UUID()),";
                    }
                }
            }

            $updatefields = substr($updatefields, 0, strlen($updatefields) - 1);
            $clist        = substr($clist, 0, strlen($clist) - 1);
            $cvalues      = substr($cvalues, 0, strlen($cvalues) - 1);

            //chargement du sample en fonction du nombre de clé primaires
            if ($nb_cle == 1) {
                $dao = file_get_contents($this->path . 'core/crud.sample.php');

                if ($slug) {
                    $controleslug      = "\$this->bdd->controlSlug('--table--',\$this->slug,'--id--',\$this->--id--);";
                    $controleslugmulti = "\$this->bdd->controlSlugMultiLn('--table--',\$this->slug,\$this->--id--,\$list_field_value,\$this->id_langue);";
                } else {
                    $controleslug      = "";
                    $controleslugmulti = "";
                }

                $dao = str_replace('--controleslug--', $controleslug, $dao);
                $dao = str_replace('--controleslugmulti--', $controleslugmulti, $dao);
            } else {
                $dao = file_get_contents($this->path . 'core/crud2.sample.php');

                if ($slug) {
                    $controleslugmulti = "\$this->bdd->controlSlugMultiLn('--table--',\$this->slug,\$this->--id--,\$list_field_value,\$this->id_langue);";
                } else {
                    $controleslugmulti = "";
                }

                $dao = str_replace('--controleslugmulti--', $controleslugmulti, $dao);
            }

            $dao = str_replace('--id--', $id[0], $dao);
            $dao = str_replace('--declaration--', $declaration, $dao);
            $dao = str_replace('--initialisation--', $initialisation, $dao);
            $dao = str_replace('--remplissage--', $remplissage, $dao);
            $dao = str_replace('--escapestring--', $escapestring, $dao);
            $dao = str_replace('--updatefields--', $updatefields, $dao);
            $dao = str_replace('--clist--', $clist, $dao);
            $dao = str_replace('--cvalues--', $cvalues, $dao);
            $dao = str_replace('--table--', $table, $dao);
            $dao = str_replace('--classe--', $table . '_crud', $dao);

            touch($this->path . 'data/crud/' . $table . '.crud.php');
            chmod($this->path . 'data/crud/' . $table . '.crud.php', 0766);
            $c = fopen($this->path . 'data/crud/' . $table . '.crud.php', 'r+');

            fputs($c, $dao);
            fclose($c);

            return true;
        } else {
            return false;
        }
    }

    //Genere un fichier DATA a partir d'une table
    public function generateDATA($table)
    {
        $sql    = "desc " . $table;
        $result = $this->bdd->query($sql);

        if ($result) {
            while ($record = $this->bdd->fetch_array($result)) {
                if ($record['Key'] == 'PRI') {
                    $id[] = $record['Field'];
                }
            }

            //si la clé primaire est unique
            if (count($id) == 1) {
                $dao = file_get_contents($this->path . 'core/data.sample.php');
            } else {
                $dao = file_get_contents($this->path . 'core/data2.sample.php');
            }

            $dao = str_replace('--table--', $table, $dao);
            $dao = str_replace('--classe--', $table, $dao);
            $dao = str_replace('--id--', $id[0], $dao);

            touch($this->path . 'data/' . $table . '.data.php');
            chmod($this->path . 'data/' . $table . '.data.php', 0766);
            $c = fopen($this->path . 'data/' . $table . '.data.php', 'r+');

            fputs($c, $dao);
            fclose($c);

            return true;
        } else {
            return false;
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

    // Fonction qui déclenche le caching d'une page
    public function fireCache()
    {
        if ($this->enableCache) {
            $this->cacheCurrentPage = true;
        }
    }

    public function initUnilendAutoload()
    {
        require_once __DIR__ . '/../Autoloader.php';
        Autoloader::register();
    }

    // Initialisation du cache
    public function initCache()
    {
        $this->cacheFile = $this->path . 'tmp/cache/' . md5($_SERVER['REQUEST_URI']);
        // On recherche un fichier de cache suffisament récent
        if (file_exists($this->cacheFile) && (time() - $this->cacheDuration * 60 < filemtime($this->cacheFile))) {
            // Si on le trouve, on l'output
            include($this->cacheFile);
            echo "<!-- From cache generated " . date('H:i', filemtime($this->cacheFile)) . " -->";
            exit;
        }
        // Sinon, on ouvre le buffer
        ob_start();
    }

    public function completeCache()
    {
        // Ecriture du fichier de cache
        $fp = fopen($this->cacheFile, 'w');
        // Contenu du buffer
        fwrite($fp, ob_get_contents());

        fclose($fp);

        ob_end_flush();

        // Cassos
        exit;
    }

    public function clearCache($page = '')
    {
        $cacheFile   = $this->path . 'tmp/cache/' . md5($page);
        $cacheFolder = $this->path . 'tmp/cache/';
        if ($page == '') {
            $dossier = opendir($cacheFolder);
            while ($fichier = readdir($dossier)) {
                if ($fichier != "." && $fichier != "..") {
                    $Vidage = $cacheFolder . $fichier;
                    @unlink($Vidage);
                }
            }
            closedir($dossier);
        } else {
            @unlink($cacheFile);
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
