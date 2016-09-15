<?php

class lender_panel_preference extends lender_panel_preference_crud
{
    public function lender_panel_preference($bdd, $params='')
    {
        parent::lender_panel_preference($bdd, $params);
    }

    /**
     * @param int    $lenderId
     * @param string $pageName
     * @return array mixed
     */
    public function getLenderPreferencesByPage($lenderId, $pageName)
    {
        $sql = '
            SELECT *
            FROM lender_panel_preference
            WHERE id_lender = :id_lender
            AND page_name = :page_name
            ORDER BY panel_order ASC';

        $result =  $this->bdd->executeQuery(
            $sql,
            ['id_lender' => $lenderId, 'page_name' => $pageName],
            ['id_lender' => \PDO::PARAM_INT, 'page_name' => \PDO::PARAM_STR]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        foreach ($result as $key => $item) {
            $data[$item['panel_name']] = $item;
        }
        return $data;
    }
}
