<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;

class greenpoint_attachment_detail extends greenpoint_attachment_detail_crud
{
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
        if (false === in_array($documentType, [AttachmentType::CNI_PASSPORTE, AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT])) {
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
            WHERE a.id_client = :id_client';

        /** @var \Doctrine\DBAL\Driver\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, ['id_client' => $clientId], ['id_client' => \PDO::PARAM_INT]);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
}
