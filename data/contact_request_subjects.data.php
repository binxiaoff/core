<?php

class contact_request_subjects extends contact_request_subjects_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::contact_request_subjects($bdd, $params);
    }

    public function getAllSubjects($locale)
    {
        $result   = array();
        $resultat = $this->bdd->query('SELECT * FROM contact_request_subjects');

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[$record['id_contact_request_subject']] = $record;
        }

        $oTranslations = new \translations($this->bdd);
        $aTranslations = $oTranslations->getAllTranslationsForSection('borrower-contact', $locale);

        $aSubjects = array_map(
            function($aSubject) use ($aTranslations) {
                if ($index = array_search('subject-option-' . $aSubject['id_contact_request_subject'], array_column($aTranslations, 'name'))) {
                    $aSubject['trans'] = $aTranslations[$index]['translation'];
                }

                return $aSubject;
            },
            $result
        );

        return $aSubjects;
    }
}
