<?php

class lender_evaluation_log extends lender_evaluation_log_crud
{
    const EVENT_ADVICE                = 'advice';
    const EVENT_BID_EVALUATION_NEEDED = 'bid_evaluation_needed';
    const EVENT_BID_ADVICE            = 'bid_advice';

    public function __construct($bdd, $params = '')
    {
        parent::lender_evaluation_log($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `lender_evaluation_log`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `lender_evaluation_log`' . $where));
    }

    public function exist($id, $field = 'id_lender_evaluation_log')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `lender_evaluation_log` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

    /**
     * @param int $idLender
     *
     * @return bool
     */
    public function hasLenderLog($idLender)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder->select('COUNT(*)')
            ->from('lender_evaluation_log', 'log')
            ->innerJoin('log', 'lender_evaluation', 'eval', 'log.id_lender_evaluation = eval.id_lender_evaluation')
            ->where('eval.id_lender = :id_lender')
            ->setParameter('id_lender', $idLender);

        return $queryBuilder->execute()->fetchColumn() > 0;
    }
}
