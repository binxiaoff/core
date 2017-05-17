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

    public function _contrat()
    {
        /** @var \clients $clients */
        $clients = $this->loadData('clients');
        /** @var \loans $loans */
        $loans = $this->loadData('loans');
        /** @var \lenders_accounts $lendersAccounts */
        $lendersAccounts = $this->loadData('lenders_accounts');
        /** @var \projects $projects */
        $projects = $this->loadData('projects');

        if (false === $loans->get($this->params[1], 'id_loan')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $lendersAccounts->get($loans->id_lender, 'id_lender_account')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $projects->get($loans->id_project, 'id_project')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $clients->get($lendersAccounts->id_client_owner, 'id_client')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $namePdfClient = 'CONTRAT-UNILEND-' . $projects->slug . '-' . $loans->id_loan . '.pdf';
        $filePath      = $this->path . 'protected/pdf/contrat/contrat-' . $clients->hash . '-' . $loans->id_loan . '.pdf';

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
        /** @var \clients $clients */
        $clients = $this->loadData('clients');

        if (isset($this->params[0]) && $clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            /** @var \loans $loans */
            $loans = $this->loadData('loans');
            /** @var \lenders_accounts $lendersAccounts */
            $lendersAccounts = $this->loadData('lenders_accounts');
            $lendersAccounts->get($clients->id_client, 'id_client_owner');

            if ($loans->get($lendersAccounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {
                $filePath = $this->path . 'protected/pdf/declaration_de_creances/' . $loans->id_project . '/';
                $filePath = ($loans->id_project == '1456') ? $filePath : $filePath . $clients->id_client . '/';
                $filePath = $filePath . 'declaration-de-creances' . '-' . $this->params[0] . '-' . $this->params[1] . '.pdf';
                $fileName = 'DECLARATION-DE-CREANCES-UNILEND-' . $clients->hash . '-' . $loans->id_loan . '.pdf';

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
