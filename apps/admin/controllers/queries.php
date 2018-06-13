<?php

use Box\Spout\{
    Common\Type, Writer\Style\StyleBuilder, Writer\WriterFactory
};
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class queriesController extends bootstrap
{
    /** @var \queries */
    protected $queries;

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_STATISTICS);

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
        $this->lRequetes = $this->queries->select('', 'executed DESC');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (isset($_POST['form_edit_requete']) && $userManager->isGrantedIT($this->userEntity)) {
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

        if (isset($_POST['form_add_requete']) && $userManager->isGrantedIT($this->userEntity)) {
            $this->queries->name   = $_POST['name'];
            $this->queries->paging = $_POST['paging'];
            $this->queries->sql    = $_POST['sql'];
            $this->queries->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'une requ&ecirc;te';
            $_SESSION['freeow']['message'] = 'La requ&ecirc;te a bien &eacute;t&eacute; ajout&eacute;e !';

            header('Location:' . $this->lurl . '/queries');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete' && $userManager->isGrantedIT($this->userEntity)) {
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

        preg_match_all('/@[_a-zA-Z1-9]+@/', $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);
    }

    public function _execute()
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1200);

        $this->queries = $this->loadData('queries');
        $this->queries->get($this->params[0], 'id_query');
        $this->queries->sql = trim(str_replace(
            array('[ID_USER]'),
            array($this->sessionIdUser),
            $this->queries->sql
        ));

        if (
            1 !== preg_match('/^SELECT\s/i', $this->queries->sql)
            || 1 === preg_match('/[^A-Z](ALTER|INSERT|DELETE|DROP|TRUNCATE|UPDATE)[^A-Z]/i', $this->queries->sql)
        ) {
            $this->result    = array();
            $this->sqlParams = array();
            trigger_error('Stat query may be dangerous: ' . $this->queries->sql, E_USER_WARNING);
            return;
        }

        preg_match_all('/@[_a-zA-Z1-9]+@/', $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);

        foreach ($this->sqlParams as $param) {
            $this->queries->sql = str_replace($param[0], $this->bdd->quote($_POST['param_' . str_replace('@', '', $param[0])]), $this->queries->sql);
        }

        $this->result = $this->queries->run($this->params[0], $this->queries->sql);
    }

    /**
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function _export(): void
    {
        $this->hideDecoration();
        $this->autoFireview = false;

        $this->_execute();

        if (is_array($this->result) && count($this->result) > 0) {
            $filename = $this->bdd->generateSlug($this->queries->name) . '.xlsx';
            $writer   = WriterFactory::create(Type::XLSX);

            $titleStyle = (new StyleBuilder())
                ->setFontBold()
                ->setFontColor(\Box\Spout\Writer\Style\Color::WHITE)
                ->setBackgroundColor('2672A2')
                ->build();

            $writer
                ->openToBrowser($filename)
                ->addRowWithStyle([$this->queries->name], $titleStyle)
                ->addRow(array_keys($this->result[0]))
                ->addRows($this->result)
                ->close();
        }

        die;
    }
}
