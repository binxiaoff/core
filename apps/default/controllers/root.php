<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Clients, ClientsStatus, Wallet};

class rootController extends bootstrap
{
    public function _default()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->hideDecoration();
        $this->setView('../root/404');
    }

    public function _pdf_cgv_preteurs()
    {
        $this->autoFireView = false;

        include_once $this->path . '/apps/default/controllers/pdf.php';

        // hack the symfony guard token
        $session = $this->get('session');

        /** @var \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken $token */
        $token =  unserialize($session->get('_security_default'));
        if (!$token instanceof \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $client = $token->getUser();
        if (!$client instanceof Clients) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if ($client->isInSubscription()) {
            header('Location: ' . $this->lurl . '/inscription-preteurs');
            exit;
        }

        $listeAccept = $this->acceptations_legal_docs->select('id_client = ' . $client->getIdClient(), 'added DESC', 0, 1);
        $listeAccept = array_shift($listeAccept);

        $id_tree_cgu = $listeAccept['id_legal_doc'];

        $contenu = $this->tree_elements->select('id_tree = "' . $id_tree_cgu . '" AND id_langue = "' . $this->language . '"');
        foreach ($contenu as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug]    = $elt['value'];
            $this->complement[$this->elements->slug] = $elt['complement'];
        }

        // si c'est un ancien cgv de la liste on lance le pdf
        if (in_array($id_tree_cgu, array(92, 95, 93, 254, 255))) {
            header("Content-disposition: attachment; filename=" . $this->content['pdf-cgu']);
            header("Content-Type: application/force-download");
            @readfile($this->surl . '/var/fichiers/' . $this->content['pdf-cgu']);
        } else {
            $oCommandPdf    = new \Command('pdf', 'cgv_preteurs', array($client->getHash()), $this->language);
            $oPdf           = new \pdfController($oCommandPdf, 'default', $this->request);
            $oPdf->setContainer($this->container);
            $oPdf->initialize();
            $path           = $this->path . 'protected/pdf/cgv_preteurs/' . $client->getIdClient() . '/';
            $sNamePdf       = 'cgv_preteurs-' . $client->getHash() . '-' . $id_tree_cgu;
            $sNamePdfClient = 'CGV-UNILEND-PRETEUR-' . $client->getIdClient() . '-' . $id_tree_cgu;

            if (false  === file_exists($path . $sNamePdf)) {
                $this->cgv_preteurs(true, $oPdf, [$client->getHash()]);
                $oPdf->WritePdf($path . $sNamePdf, 'cgv_preteurs');
            }

            $oPdf->ReadPdf($path . $sNamePdf, $sNamePdfClient);
        }
    }

    /**
     * @param bool               $bPdf
     * @param pdfController|null $oPdf
     * @param array|null         $aParams
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function cgv_preteurs(bool $bPdf = false, pdfController $oPdf = null, array $aParams = null): void
    {
        $this->params = (false === is_null($aParams)) ? $aParams : $this->params;

        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->companies               = $this->loadData('companies');

        $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
        $id_tree_cgu = $this->settings->value;

        foreach ($this->tree_elements->select('id_tree = "' . $id_tree_cgu . '" AND id_langue = "' . $this->language . '"') as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug]    = $elt['value'];
            $this->complement[$this->elements->slug] = $elt['complement'];
        }

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {
            if (isset($this->params[0]) && $this->params[0] != 'morale' && $this->params[0] != 'nosign') {
                $this->autoFireHeader = false;
                $this->autoFireHead   = true;
                $this->autoFireFooter = false;
            }

            if (isset($this->params[0]) && $this->params[0] == 'nosign') {
                $dateAccept = '';
            } else {
                $listeAccept = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client, 'added DESC', 0, 1);
                $listeAccept = array_shift($listeAccept);

                $dateAccept  = 'Sign&eacute; &eacute;lectroniquement le ' . date('d/m/Y', strtotime($listeAccept['added']));
            }

            $this->settings->get('Date nouvelles CGV avec 2 mandats', 'type');
            $sNewTermsOfServiceDate = $this->settings->value;

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var Wallet $wallet */
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->clients->id_client, \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType::LENDER);

            /** @var \loans $oLoans */
            $oLoans      = $this->loadData('loans');
            $iLoansCount = $oLoans->counter('id_wallet = ' . $wallet->getId() . ' AND added < "' . $sNewTermsOfServiceDate . '"');

            if (in_array($this->clients->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                $clientAddress = $wallet->getIdClient()->getIdAddress();
                if (null === $clientAddress) {
                    $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                        ->findLastModifiedNotArchivedAddressByType($wallet->getIdClient(), AddressType::TYPE_MAIN_ADDRESS);
                }

                $aReplacements = [
                    '[Civilite]'            => $this->clients->civilite,
                    '[Prenom]'              => $this->clients->prenom,
                    '[Nom]'                 => $this->clients->nom,
                    '[date]'                => date('d/m/Y', strtotime($this->clients->naissance)),
                    '[ville_naissance]'     => $this->clients->ville_naissance,
                    '[adresse_fiscale]'     => $clientAddress->getAddress() . ', ' . $clientAddress->getZip() . ', ' . $clientAddress->getCity() . ', ' . $clientAddress->getIdCountry()->getFr(),
                    '[date_validation_cgv]' => $dateAccept
                ];

                $this->mandat_de_recouvrement           = str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement']);
                $this->mandat_de_recouvrement_avec_pret = $iLoansCount > 0 ? str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement-avec-pret']) : '';
            } else {
                $this->companies->get($this->clients->id_client, 'id_client_owner');
                $companyEntity  = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->companies->id_company);
                $companyAddress = $companyEntity->getIdAddress();

                if (null === $companyAddress) {
                    $companyAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                        ->findLastModifiedNotArchivedAddressByType($companyEntity, AddressType::TYPE_MAIN_ADDRESS);
                }

                $aReplacements = [
                    '[Civilite]'            => $this->clients->civilite,
                    '[Prenom]'              => $this->clients->prenom,
                    '[Nom]'                 => $this->clients->nom,
                    '[Fonction]'            => $this->clients->fonction,
                    '[Raison_sociale]'      => $this->companies->name,
                    '[SIREN]'               => $this->companies->siren,
                    '[adresse_fiscale]'     => $companyAddress->getAddress() . ', ' . $companyAddress->getZip() . ', ' . $companyAddress->getCity() . ', ' . $companyAddress->getIdCountry()->getFr(),
                    '[date_validation_cgv]' => $dateAccept
                ];

                $this->mandat_de_recouvrement           = str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement-personne-morale']);
                $this->mandat_de_recouvrement_avec_pret = $iLoansCount > 0 ? str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement-avec-pret-personne-morale']) : '';
            }
        } elseif (isset($this->params[0]) && $this->params[0] == 'morale') {
            $variables                              = ['[Civilite]', '[Prenom]', '[Nom]', '[Fonction]', '[Raison_sociale]', '[SIREN]', '[adresse_fiscale]', '[date_validation_cgv]'];
            $tabVariables                           = explode(';', $this->content['contenu-variables-par-defaut-morale']);
            $contentVariables                       = $tabVariables;
            $this->mandat_de_recouvrement           = str_replace($variables, $contentVariables, $this->content['mandat-de-recouvrement-personne-morale']);
            $this->mandat_de_recouvrement_avec_pret = '';
        } else {
            $variables                              = ['[Civilite]', '[Prenom]', '[Nom]', '[date]', '[ville_naissance]', '[adresse_fiscale]', '[date_validation_cgv]'];
            $tabVariables                           = explode(';', $this->content['contenu-variables-par-defaut']);
            $contentVariables                       = $tabVariables;
            $this->mandat_de_recouvrement           = str_replace($variables, $contentVariables, $this->content['mandat-de-recouvrement']);
            $this->mandat_de_recouvrement_avec_pret = '';
        }

        if (true === $bPdf && false === is_null($oPdf)) {
            $this->content['mandatRecouvrement']         = $this->mandat_de_recouvrement;
            $this->content['mandatRecouvrementAvecPret'] = $this->mandat_de_recouvrement_avec_pret;
            $oPdf->setDisplay('cgv_preteurs', $this->content);
        }
    }
}
