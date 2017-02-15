<?php

class company_rating extends company_rating_crud
{
    const TYPE_ALTARES_VALUE_DATE         = 'date_valeur_altares';
    const TYPE_ALTARES_SCORE_20           = 'score_altares';
    const TYPE_ALTARES_SECTORAL_SCORE_100 = 'score_sectoriel_altares';
    const TYPE_INFOLEGALE_SCORE           = 'note_infolegale';
    const TYPE_XERFI_RISK_SCORE           = 'xerfi';
    const TYPE_UNILEND_XERFI_RISK         = 'xerfi_unilend';
    const TYPE_EULER_HERMES_GRADE         = 'grade_euler_hermes';

    public static $ratingTypes = [
        self::TYPE_ALTARES_VALUE_DATE,
        self::TYPE_ALTARES_SCORE_20,
        self::TYPE_ALTARES_SECTORAL_SCORE_100,
        self::TYPE_INFOLEGALE_SCORE,
        self::TYPE_XERFI_RISK_SCORE,
        self::TYPE_UNILEND_XERFI_RISK,
        self::TYPE_EULER_HERMES_GRADE
    ];

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

        if ($authorDate) {
            $ratingHistory = new \company_rating_history($this->bdd);
            $ratingHistory->get($companyRatingHistoryId);
        }

        foreach ($ratings as $rating) {
            $result[$rating['type']] = $rating;

            if ($authorDate) {
                $result[$rating['type']] = $this->getAuthorDate($rating, $ratingHistory);
            }
        }

        return $result;
    }

    /**
     * @param array                   $rating
     * @param \company_rating_history $ratingHistory
     * @return array
     */
    private function getAuthorDate(array $rating, \company_rating_history $ratingHistory)
    {
        $statement = $this->bdd->createQueryBuilder()
            ->select('crh.id_user, IF(u.id_user IS NULL, "", CONCAT(u.firstname, " ", u.name)) AS user, crh.action, crh.added, cr.value')
            ->from('company_rating_history', 'crh')
            ->innerJoin('crh', 'company_rating', 'cr', 'crh.id_company_rating_history = cr.id_company_rating_history AND cr.type = :ratingType')
            ->leftJoin('crh', 'users', 'u', 'crh.id_user = u.id_user')
            ->where('crh.id_company = :companyId')
            ->setParameter('ratingType', $rating['type'])
            ->setParameter('companyId', $ratingHistory->id_company)
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
