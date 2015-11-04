<?php

class mouvementController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);
        $this->catchAll = true;
    }

    public function _default()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->setView('../root/404');
    }

    public function _detail_transac()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->setView('../root/404');
    }

    public function _histo_transac()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->setView('../root/404');
    }
}
