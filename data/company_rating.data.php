<?php

class company_rating extends company_rating_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::company_rating($bdd, $params);
    }

    public function select($where = '', $order = '', $offset = '', $limit = '')
    {
        $query = 'SELECT * FROM company_rating' .
            (empty($where) ? '' : ' WHERE ' . $where) .
            (empty($order) ? '' : ' ORDER BY ' . $order) .
            (empty($limit) ? '' : ' LIMIT ' . $limit) .
            (empty($offset) ? '' : ' OFFSET ' . $offset);

        $result    = [];
        $statement = $this->bdd->query($query);
        while ($record = $this->bdd->fetch_assoc($statement)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $query     = 'SELECT COUNT(*) FROM company_rating' . $where;
        $statement = $this->bdd->query($query);
        return (int) $this->bdd->result($statement, 0, 0);
    }

    public function exist($id, $field = 'id_company_rating')
    {
        $query     = 'SELECT * FROM company_rating WHERE ' . $field . ' = "' . $id . '"';
        $statement = $this->bdd->query($query);
        return $this->bdd->fetch_assoc($statement) > 0;
    }

    /**
     * @param int  $companyRatingHistoryId
     * @param bool $authorDate
     * @return array
     */
    public function getHistoryRatingsByType($companyRatingHistoryId, $authorDate = false)
    {
        $result  = [];
        $ratings = $this->select('id_company_rating_history = ' . $companyRatingHistoryId);

        foreach ($ratings as $rating) {
            $result[$rating['type']] = $rating;

            if ($authorDate) {
                $result[$rating['type']] = $this->getAuthorDate($rating);
            }
        }

        return $result;
    }

    /**
     * @param array $rating
     * @return array
     */
    private function getAuthorDate(array $rating)
    {
        $statement = $this->bdd->createQueryBuilder()
            ->select('crh.id_user, IF(u.id_user IS NULL, "", CONCAT(u.firstname, " ", u.name)) AS user, crh.action, crh.added, cr.value')
            ->from('company_rating_history', 'origin')
            ->innerJoin('origin', 'company_rating_history', 'crh', 'origin.id_company = crh.id_company AND crh.added <= origin.added')
            ->innerJoin('crh', 'company_rating', 'cr', 'crh.id_company_rating_history = cr.id_company_rating_history AND cr.type = :ratingType')
            ->leftJoin('crh', 'users', 'u', 'crh.id_user = u.id_user')
            ->where('origin.id_company_rating_history = :ratingHistoryId')
            ->setParameter('ratingType', $rating['type'])
            ->setParameter('ratingHistoryId', $rating['id_company_rating_history'])
            ->orderBy('crh.added', 'DESC')
            ->execute();

        foreach ($statement->fetchAll() as $history) {
            if ($history['value'] !== $rating['value']) {
                break;
            }

            $rating['id_user'] = $history['id_user'];
            $rating['user']    = $history['user'];
            $rating['action']  = $history['action'];
            $rating['added']   = \DateTime::createFromFormat('Y-m-d H:i:s', $history['added']);
        }

        return $rating;
    }
}
