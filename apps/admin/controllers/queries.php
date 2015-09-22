<?php

class queriesController extends bootstrap
{
    /**
     * @var queries
     */
    protected $queries;

    public function __construct(&$command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->users->checkAccess('stats');
        $this->menu_admin = 'stats';
    }

    public function _add()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->lurl;
    }

    public function _edit()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->lurl;

        $this->queries = $this->loadData('queries');
        $this->queries->get($this->params[0], 'id_query');
    }

    public function _default()
    {
        $this->queries   = $this->loadData('queries');
        $this->lRequetes = $this->queries->select(($this->cms == 'iZinoa' ? 'cms = "iZinoa" || cms = ""' : ''), 'executed DESC');

        // Formulaire édition d'une requête
        if (isset($_POST['form_edit_requete'])) {
            $this->queries->get($this->params[0], 'id_query');
            $this->queries->name   = $_POST['name'];
            $this->queries->paging = $_POST['paging'];
            $this->queries->sql    = $_POST['sql'];
            $this->queries->update();

            $_SESSION['freeow']['title']   = 'Modification d\'une requ&ecirc;te';
            $_SESSION['freeow']['message'] = 'La requ&ecirc;te a bien &eacute;t&eacute; modifi&eacute;e !';

            header('Location:' . $this->lurl . '/queries');
            die;
        }

        // Formulaire d'ajout d'une requête
        if (isset($_POST['form_add_requete'])) {
            $this->queries->name   = $_POST['name'];
            $this->queries->paging = $_POST['paging'];
            $this->queries->sql    = $_POST['sql'];
            $this->queries->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'une requ&ecirc;te';
            $_SESSION['freeow']['message'] = 'La requ&ecirc;te a bien &eacute;t&eacute; ajout&eacute;e !';

            header('Location:' . $this->lurl . '/queries');
            die;
        }

        // Suppression d'une requête
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->queries->delete($this->params[1], 'id_query');

            $_SESSION['freeow']['title']   = 'Suppression d\'une requ&ecirc;te';
            $_SESSION['freeow']['message'] = 'La requ&ecirc;te a bien &eacute;t&eacute; supprim&eacute;e !';

            header('Location:' . $this->lurl . '/queries');
            die;
        }
    }

    public function _params()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->lurl;

        $this->queries = $this->loadData('queries');
        $this->queries->get($this->params[0], 'id_query');

        preg_match_all("/@[_a-zA-Z1-9]+@/", $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);
    }

    public function _execute()
    {
        $this->queries = $this->loadData('queries');
        $this->queries->get($this->params[0], 'id_query');
        $this->queries->sql = str_replace(
            array('[ID_USER]'),
            array($this->sessionIdUser),
            $this->queries->sql
        );

        preg_match_all("/@[_a-zA-Z1-9]+@/", $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);

        foreach ($this->sqlParams as $param) {
            $this->queries->sql = str_replace($param[0], $_POST['param_' . str_replace('@', '', $param[0])], $this->queries->sql);
        }

        $this->result = $this->queries->run($this->params[0], $this->queries->sql);
    }

    public function _excel()
    {
        $this->hideDecoration();

        $this->queries = $this->loadData('queries');
        $this->queries->get($this->params[0], 'id_query');
        $this->queries->sql = str_replace(
            array('[ID_USER]'),
            array($this->sessionIdUser),
            $this->queries->sql
        );

        preg_match_all("/@[_a-zA-Z1-9]+@/", $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);

        foreach ($this->sqlParams as $param) {
            $this->queries->sql = str_replace($param[0], $_POST['param_' . str_replace('@', '', $param[0])], $this->queries->sql);
        }

        $this->result = $this->queries->run($this->params[0], $this->queries->sql);
    }

    public function _export()
    {
        $this->hideDecoration();

        $this->autoFireview = false;

        $this->queries = $this->loadData('queries');
        $this->queries->get($this->params[0], 'id_query');
        $this->queries->sql = str_replace(
            array('[ID_USER]'),
            array($this->sessionIdUser),
            $this->queries->sql
        );

        preg_match_all("/@[_a-zA-Z1-9]+@/", $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);

        foreach ($this->sqlParams as $param) {
            $this->queries->sql = str_replace($param[0], $_POST['param_' . str_replace('@', '', $param[0])], $this->queries->sql);
        }

        $this->result       = $this->queries->run($this->params[0], $this->queries->sql);
        $this->resultEntete = $this->result[0];

        // Création des colonnes
        // Entete du CSV
        $entete = '';
        $sep    = '';

        foreach ($this->resultEntete as $key => $line) {
            if (!is_numeric($key)) {
                $entete .= $sep . $key;
                $sep = ";";
            }
        }
        $entete .= " \n";

        $csv = $entete;

        foreach ($this->result as $result) {
            foreach ($result as $key => $details) {
                if (! is_numeric($key)) { //on supp les doublons d'info dans les select
                    $csv .= $sep . str_replace(';', ',', $details);
                    $sep = ";";
                }
            }
            $sep = "";
            $csv .= " \n";
        }

        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename="' . $this->bdd->generateSlug($this->queries->name) . '.csv"');

        echo utf8_decode($csv);
    }
}
