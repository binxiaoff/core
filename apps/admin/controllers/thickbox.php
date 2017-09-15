<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;

class thickboxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;
    }

    public function _loginError()
    {

    }

    public function _loginInterdit()
    {

    }

    public function _newPassword()
    {

    }

    public function _pop_up_edit_date_retrait()
    {
        $this->projects = $this->loadData('projects');
        $this->projects->get($this->params[0], 'id_project');

        $endOfPublicationDate      = \DateTime::createFromFormat('Y-m-d H:i:s', $this->projects->date_retrait);
        $this->date_retrait        = $endOfPublicationDate->format('d/m/Y');
        $this->heure_date_retrait  = $endOfPublicationDate->format('H');
        $this->minute_date_retrait = $endOfPublicationDate->format('i');
    }

    public function _popup_confirmation_send_email()
    {

    }

    public function _project_history()
    {
        $oProjects = $this->loadData('projects');

        $this->aHistory = array();

        if (isset($this->params[0]) && $oProjects->get($this->params[0])) {
            $oProjectsStatusHistory = $this->loadData('projects_status_history');
            $aProjectHistory        = $oProjectsStatusHistory->select('id_project = ' . $oProjects->id_project, 'added ASC, id_project_status_history ASC');

            if (false === empty($aProjectHistory)) {
                /** @var \projects_status $oProjectsStatus */
                $oProjectsStatus = $this->loadData('projects_status');
                /** @var \projects_status_history_details $oProjectsStatusHistoryDetails */
                $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');
                /** @var \users $oUsers */
                $oUsers = $this->loadData('users');

                $this->aProjectHistoryDetails = $oProjectsStatusHistoryDetails->select(
                    'id_project_status_history IN (' . implode(', ', array_column($aProjectHistory, 'id_project_status_history')) . ')',
                    'id_project_status_history ASC',
                    '',
                    '',
                    'id_project_status_history'
                );

                foreach ($aProjectHistory as $aHistory) {
                    $oProjectsStatus->get($aHistory['id_project_status']);
                    $this->aHistory[] = array(
                        'status'        => $oProjectsStatus->label,
                        'date'          => $aHistory['added'],
                        'user'          => $oUsers->getName($aHistory['id_user']),
                        'decision_date' => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) || '0000-00-00' === $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['date'] ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['date'],
                        'receiver'      => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['receiver'],
                        'mail_content'  => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['mail_content'],
                        'site_content'  => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['site_content'],
                    );
                }
            }
        }
    }

    public function _project_status_update()
    {
        $this->projects_status = $this->loadData('projects_status');
        $this->projects_status->get($this->params[1], 'status');

        $this->projectId = $this->params[0];

        switch ($this->params[1]) {
            case \projects_status::PROBLEME:
                /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
                $translator                 = $this->get('translator');
                $this->sInfoStatusChange    = trim($translator->trans('projet_info-passage-statut-probleme'));
                $this->mailInfoStatusChange = trim($translator->trans('projet_mail-info-passage-statut-probleme'));
                break;
        }
    }

    public function _company_status_update()
    {
        $this->clientId        = filter_var($this->params[0], FILTER_VALIDATE_INT);
        $this->companyId       = filter_var($this->params[1], FILTER_VALIDATE_INT);
        $this->companyStatusId = filter_var($this->params[2], FILTER_VALIDATE_INT);

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus $companyStatusEntity */
        $companyStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')->find($this->companyStatusId);
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->companyId);

        if (null === $companyStatus || null === $company || $company->getIdClientOwner() != $this->clientId) {
            return;
        }
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager companyManager */
        $this->companyManager = $this->get('unilend.service.company_manager');
        $this->statusLabel    = $this->companyManager->getCompanyStatusNameByLabel($companyStatus->getLabel());

        if (in_array($companyStatus->getLabel(), array(CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION))) {
            $companyStatusHistory   = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory')
                ->findOneBy(['idCompany' => $this->companyId], ['added' => 'DESC']);
            $this->previousReceiver = '';
            if (null !== $companyStatusHistory) {
                $this->previousReceiver = $companyStatusHistory->getReceiver();
            }
        }
    }

    public function _confirm_tax_exemption()
    {
        if ('uncheck' === $this->params[1]) {
            $this->message = 'Le préteur ne sera pas exonéré pour l\'année ' . $this->params[0] . '<br>une fois les modifications sauvegardées';
        } elseif ('check' === $this->params[1]) {
            $this->message = 'Le préteur sera exonéré pour l\'année ' . $this->params[0] . '<br>une fois les modifications sauvegardées';
        }
    }
}
