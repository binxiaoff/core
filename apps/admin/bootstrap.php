<?php

class bootstrap extends Controller
{
    private const ZONE_LABEL_CONFIGURATION = 'configuration';
    private const ZONE_LABEL_EDITION       = 'edition';

    /** @var string */
    protected $staticsKey;

    public function initialize()
    {
        parent::initialize();

        $this->staticsKey = (string) filemtime(__FILE__);

        $this->loadJs('jquery');
        $this->loadJs('jquery-ui/jquery-ui.min');
        $this->loadJs('jquery-ui/jquery-ui.datepicker-fr');
        $this->loadJs('freeow/jquery.freeow.min');
        $this->loadJs('colorbox/jquery.colorbox-min');
        $this->loadJs('tablesorter/jquery.tablesorter.min');
        $this->loadJs('tablesorter/jquery.tablesorter.pager');
        $this->loadJs('main', $this->staticsKey);

        $this->loadCss('bootstrap');
        $this->loadCss('../scripts/freeow/freeow');
        $this->loadCss('../scripts/colorbox/colorbox');
        $this->loadCss('../scripts/tablesorter/style');
        $this->loadCss('../scripts/jquery-ui/jquery-ui.min');
        $this->loadCss('main', $this->staticsKey);

        $this->nb_lignes = 100;
    }
}
