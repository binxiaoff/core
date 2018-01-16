<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;

class prelevements extends prelevements_crud
{
    const STATUS_PENDING             = 0;
    const STATUS_SENT                = 1;
    const STATUS_VALID               = 2;
    const STATUS_TERMINATED          = 3;
    const STATUS_TEMPORARILY_BLOCKED = 4;

    const CLIENT_TYPE_LENDER   = 1;
    const CLIENT_TYPE_BORROWER = 2;

    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `prelevements`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `prelevements` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_prelevement')
    {
        $result = $this->bdd->query('SELECT * FROM `prelevements` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function sum($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT SUM(montant) FROM `prelevements` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    /**
     * @param int $daysInterval
     * @return array
     */
    public function getUpcomingRepayments($daysInterval)
    {
        $sql = '
            SELECT pre.id_project, pre.num_prelevement, pre.date_echeance_emprunteur, pre.montant
            FROM prelevements pre
            INNER JOIN projects pro ON pro.id_project = pre.id_project
            INNER JOIN echeanciers_emprunteur ee ON ee.ordre = pre.num_prelevement
            WHERE pro.status = :projectStatus
              AND ee.status_emprunteur = ' . EcheanciersEmprunteur::STATUS_PENDING . '
              AND pre.id_project = ee.id_project
              AND pre.type = :directDebitStatus
              AND DATE_ADD(CURDATE(), INTERVAL :daysInterval DAY) = DATE(pre.date_echeance_emprunteur)';

        $paramValues = array('daysInterval' => $daysInterval, 'projectStatus' => \projects_status::REMBOURSEMENT, 'directDebitStatus' => \prelevements::CLIENT_TYPE_BORROWER);
        $paramTypes  = array('daysInterval' => \PDO::PARAM_INT, 'projectStatus' => \PDO::PARAM_INT, 'directDebitStatus' => \PDO::PARAM_INT);

        $statement = $this->bdd->executeQuery($sql, $paramValues, $paramTypes);
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
}
