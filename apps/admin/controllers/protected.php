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
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $loan          = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($this->params[1]);

        if (null === $loan || empty($loan->getProject()) || empty($loan->getIdLender())) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $namePdfClient = 'CONTRAT-UNILEND-' . $loan->getProject()->getSlug() . '-' . $loan->getIdLoan();
        $filePath      = $this->path . 'protected/pdf/contrat/contrat-' . $loan->getIdLender()->getIdClient()->getHash() . '-' . $loan->getIdLoan() . '.pdf';

        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $namePdfClient . '";');
            @readfile($filePath);
            die();
        } else {
            header('location: ' . $this->url . '/protected/document_not_found');
            die;
        }
    }

    public function _declaration_de_creances()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($this->params[0]) && isset($this->params[1])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $client */
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findByHash($this->params[0]);
            $loan   = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($this->params[1]);

            if (
                null === $client
                || null === $loan
                || $client !== $loan->getIdLender()->getIdClient()
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
}
