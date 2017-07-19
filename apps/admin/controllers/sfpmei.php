<?php

class sfpmeiController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll   = true;
        $this->menu_admin = 'sfpmei';
        $this->pagination = 25;


        $this->users->checkAccess('sfpmei');
    }

    /**
     * Homepage
     */
    public function _default()
    {

    }

    /**
     * Lender search
     */
    public function _preteurs()
    {
        if (false === empty($_POST)) {
            if (empty($_POST['id']) && empty($_POST['email']) && empty($_POST['lastname']) && empty($_POST['company'])) {
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $clientId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $clientId) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][]  = 'Le format de l\'email n\'est pas valide';
            }

            $lastName = empty($_POST['lastname']) ? '' : filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][]  = 'Le format du nom n\'est pas valide';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/preteurs');
                die;
            }

            /** @var \clients $clients */
            $clients       = $this->get('unilend.service.entity_manager')->getRepository('clients');
            $this->lenders = $clients->searchPreteurs($clientId, $lastName, $email, '', $companyName, 3);

            if (false === empty($this->lenders) && 1 === count($this->lenders)) {
                header('Location: ' . $this->lurl . '/sfpmei/preteur/' . $this->lenders[0]['id_client']);
                die;
            }
        }
    }

    /**
     * Borrower search
     */
    public function _emprunteurs()
    {
        if (false === empty($_POST)) {
            if (empty($_POST['siren']) && empty($_POST['company']) && empty($_POST['lastname']) && empty($_POST['email'])) {
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $siren = empty($_POST['siren']) ? '' : filter_var(str_replace(' ', '', $_POST['siren']), FILTER_SANITIZE_STRING);
            if (false === $siren) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            $lastName = empty($_POST['lastname']) ? '' : filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][]  = 'Le format du nom n\'est pas valide';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][]  = 'Le format de l\'email n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/emprunteurs');
                die;
            }

            /** @var \clients $clients */
            $clients         = $this->get('unilend.service.entity_manager')->getRepository('clients');
            $this->borrowers = $clients->searchEmprunteurs('AND', $lastName, '', $email, $companyName, $siren);

            if (false === empty($this->borrowers) && 1 === count($this->borrowers)) {
                header('Location: ' . $this->lurl . '/sfpmei/emprunteur/' . $this->borrowers[0]['id_client']);
                die;
            }

            foreach ($this->borrowers as $index => $borrower) {
                $this->borrowers[$index]['total_amount'] = $clients->totalmontantEmprunt($borrower['id_client']);
            }
        }
    }

    /**
     * Projects search
     */
    public function _projets()
    {
        if (false === empty($_POST)) {
            if (empty($_POST['id']) && empty($_POST['siren']) && empty($_POST['company'])) {
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $projectId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $projectId) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $siren = empty($_POST['siren']) ? '' : filter_var(str_replace(' ', '', $_POST['siren']), FILTER_SANITIZE_STRING);
            if (false === $siren) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/projets');
                die;
            }

            /** @var \projects $projects */
            $projects       = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $this->projects = $projects->searchDossiers('', '', '', '', '', '', $siren, $projectId, $companyName);

            array_shift($this->projects);

            if (false === empty($this->projects) && 1 === count($this->projects)) {
                header('Location: ' . $this->lurl . '/sfpmei/projet/' . $this->projects[0]['id_project']);
                die;
            }
        }
    }

    /**
     * Ajax for company name autocomplete
     */
    public function _autocompleteCompanyName()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $companies = [];

        if ($search = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)) {
            /** @var \companies $company */
            $company   = $this->loadData('companies');
            $companies = $company->searchByName($search);
        }

        echo json_encode($companies);
    }
}
