<?php

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Border, Color, Fill};
use PhpOffice\PhpSpreadsheet\Writer\Exception as PhpSpreadsheetWriterException;
use Unilend\Entity\Queries;
use Unilend\Repository\QueriesRepository;

class queriesController extends Controller
{
    /** @var QueriesRepository */
    protected $queriesRepository;
    /** @var Queries */
    protected $query;

    public function initialize()
    {
        parent::initialize();

        $this->menu_admin        = 'stats';
        $this->queriesRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Queries::class);
    }

    public function _add()
    {
        $this->hideDecoration();
    }

    public function _edit()
    {
        $this->hideDecoration();

        $this->query = $this->queriesRepository->find($this->params[0]);
    }

    public function _default()
    {
        $this->queries = $this->queriesRepository->findBy([], ['executed' => 'DESC']);

        if (isset($_POST['form_edit_requete'])) {
            $query = $this->queriesRepository->find($this->params[0]);
            $query
                ->setName($_POST['name'])
                ->setPaging($_POST['paging'])
                ->setQuery($_POST['sql'])
            ;

            $this->queriesRepository->save($query);

            $_SESSION['freeow']['title']   = 'Modification d\'une requête';
            $_SESSION['freeow']['message'] = 'La requête a bien été modifiée';

            header('Location:' . $this->url . '/queries');
            die;
        }

        if (isset($_POST['form_add_requete'])) {
            $query = new Queries();
            $query
                ->setName($_POST['name'])
                ->setPaging((int) $_POST['paging'])
                ->setQuery($_POST['sql'])
            ;

            $this->queriesRepository->save($query);

            $_SESSION['freeow']['title']   = 'Ajout d\'une requête';
            $_SESSION['freeow']['message'] = 'La requête a bien été ajoutée';

            header('Location:' . $this->url . '/queries');
            die;
        }

        if (isset($this->params[0], $this->params[1]) && 'delete' === $this->params[0]) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $query         = $this->queriesRepository->find($this->params[1]);

            $entityManager->remove($query);
            $entityManager->flush();

            $_SESSION['freeow']['title']   = 'Suppression d\'une requête';
            $_SESSION['freeow']['message'] = 'La requête a bien été supprimée';

            header('Location:' . $this->url . '/queries');
            die;
        }
    }

    public function _params()
    {
        $this->hideDecoration();

        $this->query = $this->queriesRepository->find($this->params[0]);

        preg_match_all('/@[_a-zA-Z1-9]+@/', $this->query->getQuery(), $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->super_unique($this->sqlParams);
    }

    public function _execute()
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1200);

        $this->query = $this->queriesRepository->find($this->params[0]);
        $sql         = $this->query->getQuery();

        if (
            1 !== preg_match('/^SELECT\s/i', $sql)
            || 1 === preg_match('/[^A-Z](ALTER|INSERT|DELETE|DROP|TRUNCATE|UPDATE)[^A-Z]/i', $sql)
        ) {
            $this->result    = [];
            $this->sqlParams = [];
            trigger_error('Stat query may be dangerous: ' . $sql, E_USER_WARNING);

            return;
        }

        preg_match_all('/@[_a-zA-Z1-9]+@/', $sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->super_unique($this->sqlParams);

        foreach ($this->sqlParams as $param) {
            $sql = str_replace($param[0], $this->bdd->quote($_POST['param_' . str_replace('@', '', $param[0])]), $sql);
        }

        $statement    = $this->bdd->query($sql);
        $this->result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->query->setExecutions($this->query->getExecutions() + 1);
        $this->queriesRepository->save($this->query);
    }

    /**
     * @throws PhpSpreadsheetException
     * @throws PhpSpreadsheetWriterException
     */
    public function _export(): void
    {
        $this->hideDecoration();
        $this->autoFireview = false;

        $this->_execute();

        if (is_array($this->result) && count($this->result) > 0) {
            $filename = $this->generateSlug($this->query->getName()) . '.xlsx';

            $spreadsheet = new Spreadsheet();
            $writer      = PhpSpreadsheetIOFactory::createWriter($spreadsheet, 'Xlsx');
            $sheet       = $spreadsheet->getActiveSheet();
            $lastColumn  = Coordinate::stringFromColumnIndex(count($this->result[0]));

            $sheet->getStyle('A1:' . $lastColumn . (count($this->result) + 2))->applyFromArray($this->getExportDefaultStyle());
            $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($this->getExportTitleRowStyle());
            $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray($this->getExportColumnNamesRowStyle());
            $sheet->mergeCells('A1:' . $lastColumn . '1');
            $sheet->setCellValue('A1', $this->query->getName());
            $sheet->fromArray(array_keys($this->result[0]), null, 'A2');
            $sheet->fromArray($this->result, null, 'A3');

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            $writer->save('php://output');
        }

        die;
    }

    private function super_unique($array)
    {
        $result = array_map('unserialize', array_unique(array_map('serialize', $array)));

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->super_unique($value);
            }
        }

        return $result;
    }

    private function getExportDefaultStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => [
                        'rgb' => '787679',
                    ],
                ],
            ],
        ];
    }

    private function getExportTitleRowStyle(): array
    {
        return [
            'font' => [
                'bold'  => true,
                'color' => [
                    'argb' => Color::COLOR_WHITE,
                ],
                'size'  => 18,
            ],
            'alignment' => [
                'horizontal' => 'center',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '787679',
                ],
            ],
        ];
    }

    private function getExportColumnNamesRowStyle(): array
    {
        return [
            'font' => [
                'color' => [
                    'rgb' => '787679',
                ],
                'size'  => 14,
            ],
            'alignment' => [
                'horizontal' => 'center',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'DEDCDF',
                ],
            ],
        ];
    }
}
