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

        $this->settings       = $this->loadData('settings');
        $this->tree_elements  = $this->loadData('tree_elements');
        $this->blocs_elements = $this->loadData('blocs_elements');
        $this->elements       = $this->loadData('elements');
        $this->blocs          = $this->loadData('blocs');
        $this->ln             = $this->loadData('translations');
        $this->clients        = $this->loadData('clients');
        $this->companies      = $this->loadData('companies');
        $this->projects       = $this->loadData('projects');

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
}
