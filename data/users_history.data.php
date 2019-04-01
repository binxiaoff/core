<?php

use Unilend\Entity\UsersHistory as UsersHistoryEntity;

class users_history extends users_history_crud
{
    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `users_history`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `users_history` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_user_history')
    {
        $sql    = 'SELECT * FROM `users_history` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function histo($id_form, $nom_form, $id_user, $serialize)
    {
        $this->id_form   = $id_form;
        $this->nom_form  = $nom_form;
        $this->id_user   = $id_user;
        $this->serialize = $serialize;
        $this->create();
    }

    /**
     * @param $clientId
     * @return array
     */
    public function getTaxExemptionHistoryAction($clientId)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder    = $this->bdd->createQueryBuilder();
        $clientIdPattern = '\"id_client\";s:' . strlen($clientId) . ':\"' . $clientId . '\"';
        $queryBuilder->select('*')
            ->from('users_history')
            ->where('id_form = :id_form')
            ->andWhere('nom_form = :form_name')
            ->andWhere('serialize like \'%' . $clientIdPattern . '%\'')
            ->setParameter('id_form', UsersHistoryEntity::FORM_ID_LENDER, \PDO::PARAM_INT)
            ->setParameter('form_name', UsersHistoryEntity::FORM_NAME_TAX_EXEMPTION, \PDO::PARAM_STR)
            ->orderBy('added', 'DESC');

        /** @var \Doctrine\DBAL\Driver\Statement $statement */
        $statement = $queryBuilder->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
