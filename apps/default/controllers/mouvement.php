<?php

class mouvementController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();
        $this->catchAll = true;
    }

    public function _default()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->Command->setControllerName('root');
        $this->setView('../root/404');
    }

    public function _detail_transac()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->Command->setControllerName('root');
        $this->setView('../root/404');
    }

    public function _histo_transac()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->Command->setControllerName('root');
        $this->setView('../root/404');
    }
}
