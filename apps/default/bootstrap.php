<?php

class bootstrap extends Controller
{
    /**
     * @object data\crud\companies
     * @desc object for Companies infos
     */
    public $companies;

    /**
     * @object data\crud\projects
     * @des obecjt for Projects infos
     */
    public $projects;

    /**
     * @var array
     */
    public $aDataLayer = array();

    protected function initialize()
    {
        parent::initialize();

        // @todo kept for temporary backward compatibility
        $this->tabProjectDisplay = [\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE];
        $this->tabOrdreProject   = [
            [],
            [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC],
            [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_ASC]
        ];

        if ($this->current_function != 'login') {
            $_SESSION['redirection_url'] = $_SERVER['REQUEST_URI'];
        }
        $this->upload = $this->loadLib('upload');

        $this->settings                = $this->loadData('settings');
        $this->tree_elements           = $this->loadData('tree_elements');
        $this->blocs_elements          = $this->loadData('blocs_elements');
        $this->tree                    = $this->loadData('tree', array('url' => $this->url, 'surl' => $this->surl, 'tree_elements' => $this->tree_elements, 'blocs_elements' => $this->blocs_elements, 'upload' => $this->upload, 'spath' => $this->spath));
        $this->templates               = $this->loadData('templates');
        $this->elements                = $this->loadData('elements');
        $this->blocs_templates         = $this->loadData('blocs_templates');
        $this->blocs                   = $this->loadData('blocs');
        $this->mail_template           = $this->loadData('mail_templates');
        $this->ln                      = $this->loadData('translations');
        $this->clients                 = $this->loadData('clients');
        $this->clients_adresses        = $this->loadData('clients_adresses');
        $this->clients_history         = $this->loadData('clients_history');
        $this->villes                  = $this->loadData('villes');
        $this->clients_status          = $this->loadData('clients_status');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->convert_api_compteur    = $this->loadData('convert_api_compteur');
        $this->accept_cookies          = $this->loadData('accept_cookies');
        $this->companies               = $this->loadData('companies');
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');

        $this->ficelle = $this->loadLib('ficelle');
        $this->photos  = $this->loadLib('photos', array($this->spath, $this->surl));
        $this->dates   = $this->loadLib('dates');

        // Recuperation de la liste des langue disponibles
        $this->lLangues = ['fr' => 'Francais'];

        $this->loadCss('default/izicom');
        $this->loadCss('default/colorbox');
        $this->loadCss('default/fonts');
        $this->loadCss('default/jquery.c2selectbox');
        $this->loadCss('default/jquery-ui-1.10.3.custom');
        $this->loadCss('default/custom-theme/jquery-ui-1.10.3.custom');//datepicker
        $this->loadCss('default/style', 0, 'all', 'css', date('Ymd')); // permet d'avoir un nouveau cache de js par jour chez l'utilisateur
        $this->loadCss('default/style-edit', 0, 'all', 'css', date('Ymd'));

        $this->loadJs('default/jquery/jquery-1.10.2.min');
        $this->loadJs('default/bootstrap-tooltip');
        $this->loadJs('default/jquery.c2selectbox');
        $this->loadJs('default/livevalidation_standalone.compressed');
        $this->loadJs('default/jquery.colorbox-min');
        $this->loadJs('default/jqueryui-1.10.3.min');
        $this->loadJs('default/functions', 0, date('Ymd'));
        $this->loadJs('default/main', 0, date('YmdH'));
        $this->loadJs('default/ajax', 0, date('Ymd'));

        $this->meta_title       = '';
        $this->meta_description = '';
        $this->meta_keywords    = '';

        // XSS protection
        if (false === empty($_POST)) {
            foreach ($_POST as $key => $value) {
                if (is_string($value)) {
                    $_POST[$key] = htmlspecialchars(strip_tags($value));
                }
            }
        }

        if (false === empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (is_string($value)) {
                    $_GET[$key] = htmlspecialchars(strip_tags($value));
                }
            }
        }

        $this->setSessionSource();

        $urlParams = explode('/', $_SERVER['REQUEST_URI']);
        $this->handlePartenaire($urlParams);

        $oCachePool  = $this->get('memcache.default');
        $oCachedItem = $oCachePool->getItem('Settings_GoogleTools_Analytics_BaseLine_FB_Twitter_Cookie');

        if (false === $oCachedItem->isHit()) {
            $this->settings->get('Google Webmaster Tools', 'type');
            $this->google_webmaster_tools = $this->settings->value;

            $this->settings->get('Google Analytics', 'type');
            $this->google_analytics = $this->settings->value;

            $this->settings->get('Google Tag Manager', 'type');
            $this->google_tag_manager = $this->settings->value;

            $this->settings->get('Baseline Title', 'type');
            $this->baseline_title = $this->settings->value;

            $this->settings->get('Facebook', 'type');
            $this->like_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $this->twitter = $this->settings->value;

            $this->settings->get('id page cookies', 'type');
            $this->id_tree_cookies = $this->settings->value;

            $aElements = array(
                'GoogleTools'      => $this->google_webmaster_tools,
                'GoogleAnalytics'  => $this->google_analytics,
                'GoogleTagManager' => $this->google_tag_manager,
                'BaselineTitle'    => $this->baseline_title,
                'Facebook'         => $this->like_fb,
                'Twitter'          => $this->twitter,
                'TreeCookies'      => $this->id_tree_cookies
            );
            $oCachedItem->set($aElements)
                ->expiresAfter(3600);
            $oCachePool->save($oCachedItem);
        } else {
            $aElements   = $oCachedItem->get();
            $this->google_webmaster_tools = $aElements['GoogleTools'];
            $this->google_analytics       = $aElements['GoogleAnalytics'];
            $this->google_tag_manager     = $aElements['GoogleTagManager'];
            $this->baseline_title         = $aElements['BaselineTitle'];
            $this->like_fb                = $aElements['Facebook'];
            $this->twitter                = $aElements['Twitter'];
            $this->id_tree_cookies        = $aElements['TreeCookies'];
        }

        $this->lNotes = array(
            'A' => 'etoile1',
            'B' => 'etoile2',
            'C' => 'etoile3',
            'D' => 'etoile4',
            'E' => 'etoile5',
            'F' => 'etoile6',
            'G' => 'etoile7',
            'H' => 'etoile8',
            'I' => 'etoile9',
            'J' => 'etoile10'
        );

        //Recuperation des element de traductions
        $oCachedItem = $oCachePool->getItem('Trad_Header_Footer_home');

        if (false === $oCachedItem->isHit()) {
            $aElements = array(
                'TradHeader' => $this->ln->selectFront('header','fr_FR', $this->App),
                'TradFooter' => $this->ln->selectFront('footer', 'fr_FR', $this->App),
                'TradHome'   => $this->ln->selectFront('home', 'fr_FR', $this->App)
            );

            $oCachedItem->set($aElements)
                        ->expiresAfter(3600);
            $oCachePool->save($oCachedItem);
        } else {
            $aElements = array(
                'TradHeader' => $this->ln->selectFront('header','fr_FR', $this->App),
                'TradFooter' => $this->ln->selectFront('footer', 'fr_FR', $this->App),
                'TradHome'   => $this->ln->selectFront('home', 'fr_FR', $this->App)
            );        }

        $this->lng['header'] = $aElements['TradHeader'];
        $this->lng['footer'] = $aElements['TradFooter'];
        $this->lng['home']   = $aElements['TradHome'];


        //gestion du captcha --> moved to LoginAuthenticator

        $this->bAccountClosed      = false;
        $this->displayCaptchaError = null;


        if ($this->clients->checkAccess()) {

            $this->addDataLayer('uid', md5($this->clients->email));

        }
        $this->setSessionMail();

        false === isset($_SESSION['email']) || $_SESSION['email'] == '' ? $this->addDataLayer('unique_id', '') : $this->addDataLayer('unique_id', md5($_SESSION['email']));



        $this->create_cookies = true;
        if (isset($_COOKIE['acceptCookies'])) {
            $this->create_cookies = false;
        }

        if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr') {
            if (
                $this->Command->Name == 'root' && $this->Command->Function == 'capital'
                || $this->Command->Name == 'root' && $this->Command->Function == 'default'
                || $this->Command->Name == 'projects' && $this->Command->Function == 'detail'
                || $this->Command->Name == 'ajax'
            ) {
            } else {
                header('location: http://prets-entreprises-unilend.capital.fr/capital/');
                die;
            }
        } elseif ($this->lurl == 'http://partenaire.unilend.challenges.fr') {
            if (
                $this->Command->Name == 'root' && $this->Command->Function == 'challenges'
                || $this->Command->Name == 'root' && $this->Command->Function == 'default'
                || $this->Command->Name == 'projects' && $this->Command->Function == 'detail'
                || $this->Command->Name == 'ajax'
            ) {
            } else {
                header('location: http://partenaire.unilend.challenges.fr/challenges/');
                die;
            }
        }
    }

    public function handlePartenaire($params)
    {
        $partenaires       = $this->loadData('partenaires');
        $partenaires_clics = $this->loadData('partenaires_clics');

        // On check les params pour voir si on a un partenaire
        if (count($params) > 0) {

            // Variable pour savoir s'il a trouvé un p
            $getta = false;

            $i = 0;
            foreach ($params as $p) {

                // Si on detecte un p en params
                if ($p == 'p') {

                    // Youpi il a trouvé
                    $getta = true;

                    $indexPart = $i + 1;

                    // On regarde si on trouve un partenaire
                    if ($partenaires->get($params[ $indexPart ], 'hash')) {
                        // on controle qu'on a pas un double clique
                        if (!isset($_SESSION['partenaire_click'][ $partenaires->id_partenaire ]) || $_SESSION['partenaire_click'][ $partenaires->id_partenaire ] != $partenaires->id_partenaire) {
                            $_SESSION['partenaire_click'][ $partenaires->id_partenaire ] = $partenaires->id_partenaire;

                            // On ajoute un clic
                            if ($partenaires_clics->get(array('id_partenaire' => $partenaires->id_partenaire, 'date' => date('Y-m-d')))) {
                                $partenaires_clics->nb_clics = $partenaires_clics->nb_clics + 1;
                                $partenaires_clics->update(array('id_partenaire' => $partenaires->id_partenaire, 'date' => date('Y-m-d')));
                            } else {
                                $partenaires_clics->id_partenaire = $partenaires->id_partenaire;
                                $partenaires_clics->date          = date('Y-m-d');
                                $partenaires_clics->ip_adress     = $_SERVER['REMOTE_ADDR'];
                                $partenaires_clics->nb_clics      = 1;
                                $partenaires_clics->create(array('id_partenaire' => $partenaires->id_partenaire, 'date' => date('Y-m-d')));
                            }
                        }
                        $_SESSION['partenaire']['id_partenaire'] = $partenaires->id_partenaire;

                        // On enregistre le partenaire en cookie
                        setcookie('izicom_partenaire', $partenaires->hash, time() + 3153600, '/');

                        // On regarde si le dernier param commence par ?
                        if (substr($params[ count($params) - 1 ], 0, 1) == '?') {
                            $gogole = $params[ count($params) - 1 ];
                        }

                        // On rebidouille l'url
                        $params = array_slice($params, 0, count($params) - 3);
                        $reeurl = implode('/', $params);

                        // On renvoi
                        header('Location:' . $this->url . $reeurl . ($gogole != '' ? '/' . $gogole : ''));
                        die;
                    }
                }

                $i++;
            }

            // Si il a rien trouvé on regarde si on a un cookie et pas de session
            if (!isset($_SESSION['partenaire']['id_partenaire']) && isset($_COOKIE['izicom_partenaire']) && !$getta) {
                // On regarde si on trouve toujours un partenaire
                if ($partenaires->get($_COOKIE['izicom_partenaire'], 'hash')) {
                    // On met le partenaire en session
                    $_SESSION['partenaire']['id_partenaire'] = $partenaires->id_partenaire;
                }
            }
        }
    }

    /**
     * @param string $sKey DataLayer parameter name
     * @param mixed $mValue Parameter value
     */
    protected function addDataLayer($sKey, $mValue)
    {
        $this->aDataLayer[$sKey] = $mValue;
    }

    /**
     * Set the source details in session and push into dataLayer
     */
    private function setSessionSource()
    {
        $aAvailableUtm = $this->getUTM();

        if (false === empty($aAvailableUtm)) {
            $_SESSION['source']                 = $aAvailableUtm;
            $_SESSION['source']['slug_origine'] = $this->getSlug();
        } elseif (true === empty($_SESSION['source'])) {
            $_SESSION['source'] = array(
                'utm_source'   => 'Directe',
                'slug_origine' => $this->getSlug()
            );
        }

        foreach ($_SESSION['source'] as $mKey => $mValue) {
            $this->addDataLayer($mKey, $mValue);
        }
    }

    /**
     * This looks for UTMs in GET and POST parameters and returns them
     * @return array
     */
    private function getUTM()
    {
        $aUTM = array();
        if (false === empty($_POST)) {
            foreach ($_POST as $mKey => $mValue) {
                if ('utm_' === strtolower(substr($mKey, 0, 4))) {
                    $aUTM[$mKey] = $this->filterPost($mKey);
                }
            }
        } elseif (false === empty($_GET)) {
            foreach ($_GET as $mKey => $mValue) {
                if ('utm_' === strtolower(substr($mKey, 0, 4))) {
                    $aUTM[$mKey] = $this->filterGet($mKey);
                }
            }
        }
        return $aUTM;
    }

    /**
     * @return string
     */
    private function getSlug()
    {
        if (false === empty($_POST['slug_origine'])) {
            $sSlugOrigine = $this->filterPost('slug_origine');
        } elseif (false === empty($_GET['slug_origine'])) {
            $sSlugOrigine = $this->filterGet('slug_origine');
        } elseif (false === empty($this->tree->slug)) {
            $sSlugOrigine = trim($this->tree->slug);
        } else {
            $sSlugOrigine = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if ('/' === $sSlugOrigine) {
                $sSlugOrigine = '';
            }
        }
        return $sSlugOrigine;
    }

    /**
     * Filter and sanitize POST field
     * @param string $sFieldName
     * @param int $iFilter
     * @return string
     */
    protected function filterPost($sFieldName, $iFilter = FILTER_SANITIZE_STRING)
    {
        if (false !== ($mValue = filter_input(INPUT_POST, $sFieldName, $iFilter))) {
            return trim($mValue);
        }
        return '';
    }

    /**
     * Filter and sanitize GET field
     * @param string $sFieldName
     * @param int $iFilter
     * @return string
     */
    protected function filterGet($sFieldName, $iFilter = FILTER_SANITIZE_STRING)
    {
        if (false !== ($mValue = filter_input(INPUT_GET, $sFieldName, $iFilter))) {
            return trim($mValue);
        }
        return '';
    }


    /**
     * Set the source keys of the given object : UTMs + slug_origine
     * @param \clients|\prospects $oClient object
     */
    protected function setSource(&$oClient)
    {
        $aSourceColumn = array(
            'source'       => 'utm_source',
            'source2'      => 'utm_source2',
            'source3'      => 'utm_campaign',
            'slug_origine' => 'slug_origine'
        );

        foreach ($aSourceColumn as $sObjectField => $sUtmKey) {
            if (true === isset($_SESSION['source'][$sUtmKey])) {
                $oClient->$sObjectField = $_SESSION['source'][$sUtmKey];
            }
        }
    }


    /**
     * This looks for email address in SESSION, GET and POST parameters then add it to SESSION
     */
    private function setSessionMail()
    {
        if (isset($this->clients->email) && false === empty($this->clients->email)) {
            $_SESSION['email'] = $this->clients->email;
        } elseif (false === empty($_POST['email']) && $this->ficelle->isEmail($_POST['email'])) {
            $_SESSION['email'] = $_POST['email'];
        } elseif (false === empty($_GET['email']) && $this->ficelle->isEmail($_GET['email'])) {
            $_SESSION['email'] = $_GET['email'];
        }
    }
}
