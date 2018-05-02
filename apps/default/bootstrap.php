<?php

class bootstrap extends Controller
{
    /** @var \clients */
    public $clients;
    /** @var \companies */
    public $companies;
    /** @var \projects */
    public $projects;

    /**
     * @var array
     */
    public $aDataLayer = array();

    protected function initialize()
    {
        parent::initialize();

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
        $this->blocs                   = $this->loadData('blocs');
        $this->ln                      = $this->loadData('translations');
        $this->clients                 = $this->loadData('clients');
        $this->villes                  = $this->loadData('villes');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
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
        $this->loadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/style', date('Ymd'));
        $this->loadCss('default/style-edit', date('Ymd'));

        $this->loadJs('default/jquery/jquery-1.10.2.min');
        $this->loadJs('default/bootstrap-tooltip');
        $this->loadJs('default/jquery.c2selectbox');
        $this->loadJs('default/livevalidation_standalone.compressed');
        $this->loadJs('default/jquery.colorbox-min');
        $this->loadJs('default/jqueryui-1.10.3.min');
        $this->loadJs('default/functions', date('Ymd'));
        $this->loadJs('default/main', date('YmdH'));
        $this->loadJs('default/ajax', date('Ymd'));

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

        $this->handleLegacyPartenaireRedirection();

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

        if ($this->clients->checkAccess()) {
            $this->addDataLayer('uid', md5($this->clients->email));
        }

        $this->setSessionMail();

        false === isset($_SESSION['email']) || $_SESSION['email'] == '' ? $this->addDataLayer('unique_id', '') : $this->addDataLayer('unique_id', md5($_SESSION['email']));
    }

    /**
     * Handle legacy redirection of old "partenaires" (marketing campaigns)
     * Such URL are redirected to the same URL without the last two parameters
     * During the redirection, data was saved to DB in order to count clicks but it's not used anymore
     *
     * URL pattern is https://www.unilend.fr/..../p/a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6
     * where "a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6 "represents campaigns' hash
     */
    private function handleLegacyPartenaireRedirection()
    {
        $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (is_string($urlPath)) {
            $parameters = explode('/', $urlPath);

            foreach ($parameters as $index => $parameter) {
                if (
                    $parameter === 'p'
                    && isset($parameters[$index + 1])
                    && 1 === preg_match('/^[0-9a-f]{32}$/', $parameters[$index + 1])
                ) {
                    array_splice($parameters, -2);

                    $redirectUrl = $this->lurl . implode('/', $parameters);
                    $urlQuery    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

                    if (false === empty($urlQuery)) {
                        $redirectUrl .= '?' . $urlQuery;
                    }

                    header('Location: ' . $redirectUrl);
                    die;
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
