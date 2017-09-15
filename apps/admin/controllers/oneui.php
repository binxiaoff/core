<?php

class oneuiController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();

        $this->menu_admin = 'oneui';
    }

    public function _default()
    {
        $this->render();
    }

    // For demo purposes only
    public function _editable()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        // If the request value is numeric (e.g. 1000),
        // but we want to keep the formatting
        if ($this->request->isXmlHttpRequest()) {
            $value = $this->request->request->get('value');
            echo json_encode([
                'success' => true,
                'error'   => ['Error 1', 'Error 2'],
                'newValue' => $value.'.00 €'
            ]);
        }
    }

    // For demo purposes only
    public function _editor_table()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        // If the request value is numeric (e.g. 1000),
        // but we want to keep the formatting
        if ($this->request->isXmlHttpRequest()) {
            echo json_encode([
                'success' => true,
                'error'   => ['Error 1', 'Error 2'],
                'id' => 2334 // New client or project id or whatever - required for js to work
            ]);
        }
    }
}
