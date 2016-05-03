<?php

class queriesController extends bootstrap
{
    /**
     * @var queries
     */
    protected $queries;

    public function initialize()
    {
        parent::initialize();

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

        if (isset($_POST['form_edit_requete']) && \users_types::TYPE_ADMIN == $_SESSION['user']['id_user_type']) {
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

        if (isset($_POST['form_add_requete']) && \users_types::TYPE_ADMIN == $_SESSION['user']['id_user_type']) {
            $this->queries->name   = $_POST['name'];
            $this->queries->paging = $_POST['paging'];
            $this->queries->sql    = $_POST['sql'];
            $this->queries->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'une requ&ecirc;te';
            $_SESSION['freeow']['message'] = 'La requ&ecirc;te a bien &eacute;t&eacute; ajout&eacute;e !';

            header('Location:' . $this->lurl . '/queries');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete' && \users_types::TYPE_ADMIN == $_SESSION['user']['id_user_type']) {
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
            $this->queries->sql = str_replace($param[0], mysql_real_escape_string($_POST['param_' . str_replace('@', '', $param[0])], $this->bdd->connect_id), $this->queries->sql);
        }

        $this->result = $this->queries->run($this->params[0], $this->queries->sql);
    }

    public function _excel()
    {
        $oDocument    = $this->exportDocument();
        $oActiveSheet = $oDocument->getActiveSheet();
        $oActiveSheet->insertNewRowBefore(1, 2)
            ->setCellValueByColumnAndRow(0, 1, $this->queries->name)
            ->mergeCells('A1:' . $oActiveSheet->getHighestColumn() . '1')
            ->getStyle('A1')
            ->applyFromArray(array(
                'font' => array(
                    'bold' => true,
                    'size' => 16
                )
            ))
            ->getAlignment()
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // As long as we use $this->queries in order to name file, headers must be sent after calling $this->export()
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $this->bdd->generateSlug($this->queries->name) . '.xls');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_Excel5 $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'Excel5');
        $oWriter->save('php://output');
    }

    public function _export()
    {
        $oDocument = $this->exportDocument();

        // As long as we use $this->queries in order to name file, headers must be sent after calling $this->export()
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $this->bdd->generateSlug($this->queries->name) . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        $oWriter->save('php://output');
    }

    /**
     * @return PHPExcel
     * @throws PHPExcel_Exception
     */
    private function exportDocument()
    {
        $this->hideDecoration();

        $this->autoFireview = false;

        $this->_execute();

        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (is_array($this->result) && count($this->result) > 0) {
            $aHeaders       = array_keys($this->result[0]);
            $sLastColLetter = PHPExcel_Cell::stringFromColumnIndex(count($aHeaders) - 1);
            $oActiveSheet->getStyle('A1:' . $sLastColLetter . '1')
                ->applyFromArray(array(
                    'fill' => array(
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '2672A2')
                    ),
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => 'FFFFFF')
                    )
                ))
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            foreach ($aHeaders as $iIndex => $sColumnName) {
                $oActiveSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName)
                    ->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($iIndex))
                    ->setAutoSize(true);
            }

            foreach ($this->result as $iRowIndex => $aRow) {
                $iColIndex = 0;
                foreach ($aRow as $sCellValue) {
                    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $sCellValue);
                }
            }
        }

        return $oDocument;
    }
}
