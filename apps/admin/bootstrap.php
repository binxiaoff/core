<?php

class bootstrap extends Controller
{
    public const MENU = [
        [
            'title'    => 'Edition',
            'uri'      => 'tree',
            'zone'     => self::ZONE_LABEL_EDITION,
            'children' => [
                [
                    'title' => 'Arborescence',
                    'uri'   => 'tree',
                ],
                [
                    'title' => 'Blocs',
                    'uri'   => 'blocs',
                ],
                [
                    'title' => 'Templates',
                    'uri'   => 'templates',
                ],
                [
                    'title' => 'Traductions',
                    'uri'   => 'traductions',
                ],
                [
                    'title' => 'Mails',
                    'uri'   => 'mails',
                ],
            ],
        ],
        [
            'title'    => 'Configuration',
            'uri'      => 'settings',
            'zone'     => self::ZONE_LABEL_CONFIGURATION,
            'children' => [
                [
                    'title' => 'Paramètres',
                    'uri'   => 'settings',
                ],
                [
                    'title' => 'Historique des Mails',
                    'uri'   => 'mails/emailhistory',
                ],
            ],
        ],
    ];
    private const ZONE_LABEL_CONFIGURATION = 'configuration';
    private const ZONE_LABEL_EDITION       = 'edition';

    /** @var \upload */
    protected $upload;

    /**
     * Data.
     */
    /** @var \tree_elements */
    protected $tree_elements;
    /** @var \blocs */
    protected $blocs;
    /** @var \blocs_elements */
    protected $blocs_elements;
    /** @var \elements */
    protected $elements;
    /** @var \tree */
    protected $tree;

    /**
     * Config.
     */
    /** @var string */
    protected $spath;
    /** @var string */
    protected $furl;
    /** @var string */
    protected $lurl;
    /** @var string */
    protected $url;
    /** @var string */
    protected $staticsKey;

    public function initialize()
    {
        parent::initialize();

        $this->staticsKey = (string) filemtime(__FILE__);

        $this->upload = $this->loadLib('upload');

        $this->tree_elements  = $this->loadData('tree_elements');
        $this->blocs          = $this->loadData('blocs');
        $this->blocs_elements = $this->loadData('blocs_elements');
        $this->elements       = $this->loadData('elements');
        $this->tree           = $this->loadData('tree', [
            'url'            => $this->url,
            'furl'           => $this->furl,
            'tree_elements'  => $this->tree_elements,
            'blocs_elements' => $this->blocs_elements,
            'upload'         => $this->upload,
            'spath'          => $this->spath,
        ]);

        $this->loadJs('jquery');
        $this->loadJs('jquery-ui/jquery-ui.min');
        $this->loadJs('jquery-ui/jquery-ui.datepicker-fr');
        $this->loadJs('freeow/jquery.freeow.min');
        $this->loadJs('colorbox/jquery.colorbox-min');
        $this->loadJs('treeview/jquery.treeview');
        $this->loadJs('treeview/jquery.cookie');
        $this->loadJs('treeview/tree');
        $this->loadJs('tablesorter/jquery.tablesorter.min');
        $this->loadJs('tablesorter/jquery.tablesorter.pager');
        $this->loadJs('main', $this->staticsKey);

        $this->loadCss('bootstrap');
        $this->loadCss('../scripts/freeow/freeow');
        $this->loadCss('../scripts/colorbox/colorbox');
        $this->loadCss('../scripts/treeview/jquery.treeview');
        $this->loadCss('../scripts/tablesorter/style');
        $this->loadCss('../scripts/jquery-ui/jquery-ui.min');
        $this->loadCss('main', $this->staticsKey);

        $this->nb_lignes = 100;
    }
}
