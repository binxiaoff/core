<?php
class user_preferences extends user_preferences_crud
{
    function user_preferences($bdd, $params='')
    {
        parent::user_preferences($bdd, $params);
    }

    /**
     * @param int $clientId
     * @param string $pageName
     * @return array mixed
     */
    public function getUserPreferencesByPage($clientId, $pageName)
    {
        $sql = '
        SELECT *
        FROM user_preferences up
        WHERE up.id_client = :id_client
        AND up.page_name = :page_name
        ORDER BY up.panel_order ASC
        ';

        $result =  $this->bdd->executeQuery(
            $sql,
            ['id_client' => $clientId, 'page_name' => $pageName],
            ['id_client' => \PDO::PARAM_INT, 'page_name' => \PDO::PARAM_STR]
        )->fetchAll(\PDO::FETCH_ASSOC);
        $data = [];

        foreach ($result as $key => $item) {
            $data[$item['panel_name']] = $item;
        }
        return $data;
    }
}