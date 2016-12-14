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
class greenpoint_attachment_detail extends greenpoint_attachment_detail_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::greenpoint_attachment_detail($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `greenpoint_attachment_detail`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `greenpoint_attachment_detail` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_greenpoint_attachment_detail')
    {
        $result = $this->bdd->query('SELECT * FROM `greenpoint_attachment_detail` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    /**
     * @param int $clientId
     * @param int $documentType
     * @return array
     */
    public function getIdentityData($clientId, $documentType)
    {
        if (false === in_array($documentType, [\attachment_type::CNI_PASSPORTE, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT])) {
            return [];
        }
        $sql = '
            SELECT 
              gad.identity_birthdate,
              gad.identity_civility,
              gad.identity_document_number,
              gad.identity_document_type_id,
              gad.identity_expiration_date,
              gad.identity_issuing_authority,
              gad.identity_issuing_country,
              gad.identity_nationality
            FROM greenpoint_attachment_detail gad
            INNER JOIN greenpoint_attachment ga ON ga.id_greenpoint_attachment = gad.id_greenpoint_attachment
            INNER JOIN attachment a ON a.id = ga.id_attachment AND a.id_type =  ' . $documentType . '
            WHERE ga.id_client = :id_client
        ';
        /** @var \Doctrine\DBAL\Driver\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, ['id_client' => $clientId], ['id_client' => \PDO::PARAM_INT]);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
}
