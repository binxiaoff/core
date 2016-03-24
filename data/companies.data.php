<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class companies extends companies_crud
{
    const CLIENT_STATUS_MANAGER             = 1;
    const CLIENT_STATUS_DELEGATION_OF_POWER = 2;
    const CLIENT_STATUS_EXTERNAL_CONSULTANT = 3;

    public function __construct($bdd, $params = '')
    {
        parent::companies($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `companies`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `companies` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_company')
    {
        $result = $this->bdd->query('SELECT * FROM `companies` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    /**
     * gets all projects for one company with our without status
     * @param null|int $iCompanyId
     * @param null|array $aStatus
     * @return array
     */
    public function getProjectsForCompany($iCompanyId = null, $aStatus = null)
    {
        if (null === $iCompanyId) {
            $iCompanyId = $this->id_company;
        }

        if (isset($aStatus)) {
            $sStatus = (is_array($aStatus))? ' AND ps.status IN ('.implode(',', $aStatus).')': ' AND ps.status IN ('.$aStatus.')';
        } else {
            $sStatus = '';
        }

        $sql = '
            SELECT
                p.*,
                ps.status as project_status
            FROM projects p
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE p.id_company = '.$iCompanyId.$sStatus.'
            ORDER BY project_status DESC';

        $resultat  = $this->bdd->query($sql);
        $aProjects = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $aProjects[] = $record;
        }
        return $aProjects;
    }
}
