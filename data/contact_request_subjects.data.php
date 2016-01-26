<?php

class contact_request_subjects extends contact_request_subjects_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::contact_request_subjects($bdd, $params);
    }

    public function getAllSubjects($sLanguage)
    {
        $result   = array();
        $resultat = $this->bdd->query('SELECT * FROM contact_request_subjects');

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[$record['id_contact_request_subject']] = $record;
        }

        $oTextes = new \textes($this->bdd);
        $aTranslations = $oTextes->selectFront('espace-emprunteur', $sLanguage);

        $aSubjects = array_map(
            function($aSubject) use ($aTranslations) {
                $aSubject['label'] = $aTranslations['request-type-' . $aSubject['id_contact_request_subject']];
                return $aSubject;
            },
            $result
        );

        return $aSubjects;
    }
}
