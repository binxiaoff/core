<?php

class bootstrap extends Controller
{
    public function initialize()
    {
        parent::initialize();

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

        $this->nb_lignes = 100;
    }
}
