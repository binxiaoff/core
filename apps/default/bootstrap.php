<?php

class bootstrap extends Controller
{
    /** @var \clients */
    public $clients;
    /** @var \companies */
    public $companies;
    /** @var \projects */
    public $projects;

    protected function initialize()
    {
        parent::initialize();

        $this->handleLegacyPartenaireRedirection();

        $this->settings                = $this->loadData('settings');
        $this->tree_elements           = $this->loadData('tree_elements');
        $this->blocs_elements          = $this->loadData('blocs_elements');
        $this->elements                = $this->loadData('elements');
        $this->blocs                   = $this->loadData('blocs');
        $this->ln                      = $this->loadData('translations');
        $this->clients                 = $this->loadData('clients');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->companies               = $this->loadData('companies');
        $this->projects                = $this->loadData('projects');

        $this->ficelle = $this->loadLib('ficelle');

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
}
