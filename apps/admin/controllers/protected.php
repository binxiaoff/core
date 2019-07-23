<?php

use Unilend\Service\Document\LoanContractGenerator;

class protectedController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->hideDecoration();

        $this->autoFireView = false;

        $this->users->checkAccess();
    }

    public function _mandats()
    {
        if (file_exists($this->path . 'protected/pdf/mandat/' . $this->params[0])) {
            $url = ($this->path . 'protected/pdf/mandat/' . $this->params[0]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
            die();
        } else {
            header('location: ' . $this->url . '/protected/document_not_found');
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
            header('location: ' . $this->url . '/protected/document_not_found');
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
            header('location: ' . $this->url . '/protected/document_not_found');
            die;
        }
    }

    public function _contrat()
    {
        $this->autoFireView = false;

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $loan          = $entityManager->getRepository(Loans::class)->find($this->params[1]);

        if (null === $loan || empty($loan->getProject()) || empty($loan->getWallet())) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var LoanContractGenerator $loanContractGenerator */
        $loanContractGenerator = $this->get(LoanContractGenerator::class);

        try {
            $loanContractGenerator->generate($loan);

            $filePath = $loanContractGenerator->getPath($loan);

            header('Content-Type: ' . $loanContractGenerator->getContentType());
            header('Content-Length: ' . filesize($filePath));
            header('Content-Disposition: attachment; filename="CONTRAT-UNILEND-' . $loan->getProject()->getSlug() . '-' . $loan->getIdLoan() . '.pdf"');
            readfile($filePath);
        } catch (\Exception $exception) {
            header('Location: ' . $this->url . '/protected/document_not_found');
        }
    }

    public function _declaration_de_creances()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($this->params[0]) && isset($this->params[1])) {
            /** @var \Unilend\Entity\Clients $client */
            $client = $entityManager->getRepository(Clients::class)->findByHash($this->params[0]);
            $loan   = $entityManager->getRepository(Loans::class)->find($this->params[1]);

            if (
                null === $client
                || null === $loan
                || $client !== $loan->getWallet()->getIdClient()
            ) {
                header('location: ' . $this->url . '/protected/document_not_found');
                die;
            }

            $filePath = $this->path . 'protected/pdf/declaration_de_creances/' . $loan->getProject()->getIdProject() . '/';
            $filePath = ($loan->getProject()->getIdProject() == '1456') ? $filePath : $filePath . $client->getIdClient() . '/';
            $filePath = $filePath . 'declaration-de-creances' . '-' . $this->params[0] . '-' . $this->params[1] . '.pdf';
            $fileName = 'DECLARATION-DE-CREANCES-UNILEND-' . $client->getHash() . '-' . $loan->getIdLoan();

            if (file_exists($filePath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '";');
                @readfile($filePath);
                die();
            } else {
                header('location: ' . $this->url . '/protected/document_not_found');
                die;
            }
        } else {
            header('location: ' . $this->url . '/protected/document_not_found');
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

    public function _beneficiaires_effectifs()
    {
        $path = $this->path . 'protected/pdf/beneficial_owner/' . $this->params[0];

        if (false === file_exists($path)) {
            header('location: ' . $this->url . '/protected/document_not_found');
            die;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($this->params[0]) . '"');
        @readfile($path);
        die;
    }
}
