<?php

class protectedController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->hideDecoration();

        $this->autoFireView = false;
        $this->catchAll     = true;

        $this->users->checkAccess();
    }

    public function _templates()
    {
        if (file_exists($this->path . 'protected/templates/' . $this->params[0])) {
            $url = ($this->path . 'protected/templates/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passeport()
    {
        if (file_exists($this->path . 'protected/clients/cni_passeport/' . $this->params[0])) {
            $url = ($this->path . 'protected/clients/cni_passeport/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _signature()
    {
        if (file_exists($this->path . 'protected/clients/signature/' . $this->params[0])) {
            $url = ($this->path . 'protected/clients/signature/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _autres()
    {
        if (file_exists($this->path . 'protected/projects/autres/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/autres/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _delegation_pouvoir()
    {
        if (file_exists($this->path . 'protected/projects/delegation_pouvoir/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/delegation_pouvoir/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _dernier_bilan_certifie()
    {
        if (file_exists($this->path . 'protected/projects/dernier_bilan_certifie/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/dernier_bilan_certifie/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _extrait_kbis()
    {
        if (file_exists($this->path . 'protected/projects/extrait_kbis/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/extrait_kbis/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _logo()
    {
        if (file_exists($this->path . 'protected/projects/logo/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/logo/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _photo_dirigeant()
    {
        if (file_exists($this->path . 'protected/projects/photo_dirigeant/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/photo_dirigeant/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _rib()
    {
        if (file_exists($this->path . 'protected/projects/rib/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/rib/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _annexes_rapport_special_commissaire_compte()
    {
        if (file_exists($this->path . 'protected/projects/annexes_rapport_special_commissaire_compte/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/annexes_rapport_special_commissaire_compte/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _arret_comptable_recent()
    {
        if (file_exists($this->path . 'protected/projects/arret_comptable_recent/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/arret_comptable_recent/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _budget_exercice_en_cours_a_venir()
    {
        if (file_exists($this->path . 'protected/projects/budget_exercice_en_cours_a_venir/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/budget_exercice_en_cours_a_venir/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passeport_emprunteur()
    {
        if (file_exists($this->path . 'protected/projects/cni_passeport/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/cni_passeport/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _derniere_liasse_fiscale()
    {
        if (file_exists($this->path . 'protected/projects/derniere_liasse_fiscale/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/derniere_liasse_fiscale/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _derniers_comptes_approuves()
    {
        if (file_exists($this->path . 'protected/projects/derniers_comptes_approuves/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/derniers_comptes_approuves/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _derniers_comptes_consolides_groupe()
    {
        if (file_exists($this->path . 'protected/projects/derniers_comptes_consolides_groupe/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/derniers_comptes_consolides_groupe/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _notation_banque_france()
    {
        if (file_exists($this->path . 'protected/projects/notation_banque_france/' . $this->params[0])) {
            $url = ($this->path . 'protected/projects/notation_banque_france/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passeport_lender()
    {
        if (file_exists($this->path . 'protected/lenders/cni_passeport/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/cni_passeport/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passeport_verso_lender()
    {
        if (file_exists($this->path . 'protected/lenders/cni_passeport_verso/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/cni_passeport_verso/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _document_fiscal_preteur()
    {

        if (file_exists($this->path . 'protected/lenders/document_fiscal/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/document_fiscal/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passeport_dirigent_lender()
    {
        if (file_exists($this->path . 'protected/lenders/cni_passeport_dirigent/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/cni_passeport_dirigent/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _autre_lender()
    {
        if (file_exists($this->path . 'protected/lenders/autre/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/autre/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _autre2_lender()
    {
        if (file_exists($this->path . 'protected/lenders/autre2/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/autre2/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _autre3_lender()
    {
        if (file_exists($this->path . 'protected/lenders/autre3/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/autre3/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _dispense_prelevement_2014_lender()
    {
        if (file_exists($this->path . 'protected/lenders/dispense_prelevement_2014/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/dispense_prelevement_2014/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _dispense_prelevement_2015_lender()
    {
        if (file_exists($this->path . 'protected/lenders/dispense_prelevement_2015/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/dispense_prelevement_2015/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _dispense_prelevement_2016_lender()
    {
        if (file_exists($this->path . 'protected/lenders/dispense_prelevement_2016/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/dispense_prelevement_2016/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _dispense_prelevement_2017_lender()
    {
        if (file_exists($this->path . 'protected/lenders/dispense_prelevement_2017/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/dispense_prelevement_2017/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _delegation_pouvoir_lender()
    {
        if (file_exists($this->path . 'protected/lenders/delegation_pouvoir/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/delegation_pouvoir/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _extrait_kbis_lender()
    {
        if (file_exists($this->path . 'protected/lenders/extrait_kbis/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/extrait_kbis/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _justificatif_domicile_lender()
    {
        if (file_exists($this->path . 'protected/lenders/justificatif_domicile/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/justificatif_domicile/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _rib_lender()
    {
        if (file_exists($this->path . 'protected/lenders/rib/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/rib/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _attestation_hebergement_tiers_lender()
    {
        if (file_exists($this->path . 'protected/lenders/attestation_hebergement_tiers/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/attestation_hebergement_tiers/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passport_tiers_hebergeant_lender()
    {
        if (file_exists($this->path . 'protected/lenders/cni_passport_tiers_hebergeant/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/cni_passport_tiers_hebergeant/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _cni_passport_dirigeant_lender()
    {
        if (file_exists($this->path . 'protected/lenders/cni_passport_dirigeant/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/cni_passport_dirigeant/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _statuts_lender()
    {
        if (file_exists($this->path . 'protected/lenders/statuts/' . $this->params[0])) {
            $url = ($this->path . 'protected/lenders/statuts/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _mandats()
    {
        if (file_exists($this->path . 'protected/mandats/' . $this->params[0])) {
            $url = ($this->path . 'protected/mandats/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _mandat_preteur()
    {
        if (file_exists($this->path . 'protected/pdf/mandat/' . $this->params[0])) {
            $url = ($this->path . 'protected/pdf/mandat/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _pouvoir_project()
    {
        if (file_exists($this->path . 'protected/pdf/pouvoir/' . $this->params[0])) {
            $url = ($this->path . 'protected/pdf/pouvoir/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _pouvoir()
    {
        if (file_exists($this->path . 'protected/pouvoir/' . $this->params[0])) {
            $url = ($this->path . 'protected/pouvoir/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location:' . $this->lurl);
            die;
        }
    }

    public function _contrat()
    {
        if (file_exists($this->path . 'protected/pdf/contrat/contrat-' . $this->params[0] . '-' . $this->params[1] . '.pdf')) {
            $url = ($this->path . 'protected/pdf/contrat/contrat-' . $this->params[0] . '-' . $this->params[1] . '.pdf');

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location: ' . $this->lurl . '/protected/document_not_found');
            die;
        }
    }

    public function _declaration_de_creances()
    {
        if (file_exists($this->path . 'protected/pdf/declaration_de_creances/declaration-de-creances-' . $this->params[0] . '-' . $this->params[1] . '.pdf')) {
            $url = ($this->path . 'protected/pdf/declaration_de_creances/declaration-de-creances-' . $this->params[0] . '-' . $this->params[1] . '.pdf');

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location: ' . $this->lurl . '/protected/document_not_found');
            die;
        }
    }

    public function _document_not_found()
    {
        $this->menu_admin     = 'protected';
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;
        $this->autoFireView   = true;
    }
}
