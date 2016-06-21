<?php

class project_need extends project_need_crud
{
    public function __construct($oDatabase, $aParameters = '')
    {
        parent::project_need($oDatabase, $aParameters);
    }

    public function select($sWhere = null, $sOrder = null)
    {
        $sQuery = 'SELECT * FROM project_need';

        if (null !== $sWhere) {
            $sQuery .= ' WHERE ' . $sWhere;
        }

        if (null !== $sOrder) {
            $sQuery .= ' ORDER BY ' . $sOrder;
        }

        $aResult = array();
        $rResult = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aResult[] = $aRecord;
        }
        return $aResult;
    }

    /**
     * Retrieve 2 levels tree of available project needs
     * @return array
     */
    public function getTree()
    {
        $aTree = array();

        foreach ($this->select(null, 'id_parent ASC, rank ASC') as $aNeed) {
            if (0 == $aNeed['id_parent']) {
                $aTree[$aNeed['id_project_need']] = $aNeed;
                $aTree[$aNeed['id_project_need']]['children'] = array();
            } else {
                $aTree[$aNeed['id_parent']]['children'][] = $aNeed;
            }
        }

        return $aTree;
    }
}
