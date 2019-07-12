<?php

use Symfony\Component\HttpFoundation\Request;
use Unilend\Entity\{DebtCollectionMission, ProjectCharge, ProjectChargeType, Zones};
use Unilend\Service\DebtCollectionMissionManager;

class debt_collection_missionController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _downloadCreditorDetailsFile()
    {
        /** @var DebtCollectionMissionManager $debtCollectionMissionManager */
        $debtCollectionMissionManager = $this->get('unilend.service.debt_collection_mission_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if ($userManager->isGrantedRisk($this->userEntity) || $userManager->isUserGroupIT($this->userEntity)) {
            if (false === empty($this->params[0])) {
                $missionId = filter_var($this->params[0], FILTER_VALIDATE_INT);
                if (null !== ($debtCollectionMission = $entityManager->getRepository(DebtCollectionMission::class)->find($missionId))) {
                    try {
                        if (is_dir($this->getParameter('directory.protected') . $debtCollectionMission->getAttachment())
                            || false === file_exists($this->getParameter('directory.protected') . $debtCollectionMission->getAttachment())) {
                            $debtCollectionMissionManager->generateExcelFile($debtCollectionMission);
                        }
                        header('Content-Type: application/force-download; charset=utf-8');
                        header('Content-Disposition: attachment;filename=' . basename($debtCollectionMission->getAttachment()));
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Expires: 0');
                        readfile($this->getParameter('directory.protected') . $debtCollectionMission->getAttachment());
                        die;
                    } catch (\Exception $exception) {
                        $this->get('logger')->warning(
                            'Could not download the Excel file for debt collection mission: ' . $debtCollectionMission->getId() . ' Error: ' . $exception->getMessage(),
                            ['file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                        header('Location: ' . $this->url . '/remboursement/projet/' . $debtCollectionMission->getIdProject()->getIdProject());
                        die;
                    }
                }
            }
        }

        header('Location: ' . $this->url);
        die;
    }

    public function _add()
    {
        $this->hideDecoration();
        $this->autoFireView = false;
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (false === empty($this->params[0]) && $userManager->isGrantedRisk($this->userEntity) && $this->request->isMethod(Request::METHOD_POST)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager      = $this->get('doctrine.orm.entity_manager');
            $projectId          = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $debtCollector      = null;
            $debtCollectionType = null;
            $feesRate           = -1;
            $errors             = [];

            if (null === ($project = $entityManager->getRepository(Projects::class)->find($projectId))) {
                $errors[] = 'Le projet n\'existe pas.';
            }
            if (false === empty($this->request->request->get('debt-collector-hash'))) {
                $debtCollector = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $this->request->request->get('debt-collector-hash')]);
            }
            if (null === $debtCollector) {
                $errors[] = 'Le recouvreur n\'existe pas.';
            }
            if (empty($this->request->request->get('debt-collection-type'))
                || false === ($debtCollectionType = $this->request->request->filter('debt-collection-type', null, FILTER_VALIDATE_INT))
                || false === in_array($debtCollectionType, [DebtCollectionMission::TYPE_AMICABLE, DebtCollectionMission::TYPE_LITIGATION, DebtCollectionMission::TYPE_PRE_LITIGATION])
            ) {
                $errors[] = 'Le type de mission fourni (id type : ' . $debtCollectionType . ') n\'est pas valable.';
            }
            if (false === $this->request->request->getBoolean('debt-collection-zero-rate')
                && (empty($this->request->request->get('debt-collection-rate'))
                    || false === ($feesRate = filter_var(str_replace(',', '.', $this->request->request->get('debt-collection-rate')), FILTER_VALIDATE_FLOAT))
                    || $feesRate < 0)
            ) {
                $errors[] = 'Le taux d\'honoraires est incorrect.';
            }

            $feesRate = round(bcdiv($feesRate, 100, 6), 4);
            if ($this->request->request->getBoolean('debt-collection-zero-rate')) {
                $feesRate = 0;
            }

            /** @var \Unilend\Service\DebtCollectionMissionManager $debtCollectionManager */
            $debtCollectionManager = $this->get('unilend.service.debt_collection_mission_manager');

            if (empty($errors)) {
                $newMission = $debtCollectionManager->newMission($project, $debtCollector, $debtCollectionType, $feesRate, $this->userEntity);

                if (false === $newMission) {
                    $errors[] = 'Erreur à la création de la mission de recouvrement.';
                } else {
                    try {
                        $debtCollectionManager->generateExcelFile($newMission);
                    } catch (Exception $exception) {
                        $this->get('logger')->warning(
                            'Could not generate the debt collection mission Excel file for mission: ' . $newMission->getId() . ' - Error: ' . $exception->getMessage() . ' - In file: ' . $exception->getFile() . ' at line: ' . $exception->getLine(),
                            ['method' => __METHOD__, 'id_mission' => $newMission->getId(), 'id_project' => $newMission->getIdProject()->getIdProject()]
                        );
                    }
                }
            }

            $this->sendAjaxResponse(empty($errors), null, $errors);
        }

        $this->sendAjaxResponse(false, null, ['Paramètres ou requête incorrects']);
    }

    public function _terminate()
    {
        $this->hideDecoration();
        $this->autoFireView = false;
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (false === empty($this->params[0]) && $userManager->isGrantedRisk($this->userEntity) && $this->request->isMethod(Request::METHOD_POST)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager           = $this->get('doctrine.orm.entity_manager');
            $debtCollectionMissionId = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $errors                  = [];

            if (null === ($debtCollectionMission = $entityManager->getRepository(DebtCollectionMission::class)->find($debtCollectionMissionId))) {
                $errors[] = 'La mission de recouvrement n\'existe pas.';
            }

            /** @var \Unilend\Service\DebtCollectionMissionManager $debtCollectionManager */
            $debtCollectionManager = $this->get('unilend.service.debt_collection_mission_manager');

            if (empty($errors)) {
                if (false === $debtCollectionManager->endMission($debtCollectionMission, $this->userEntity)) {
                    $errors[] = 'Une erreur est survenue. Vous ne pouvez pas archiver cette mission de recouvrement.';
                }
            }

            $this->sendAjaxResponse(empty($errors), null, $errors);
        }

        $this->sendAjaxResponse(false, null, ['Paramètres ou requête incorrects']);
    }

    public function _addCharge()
    {
        $this->hideDecoration();
        $this->autoFireView = false;
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (false === empty($this->params[0]) && $userManager->isGrantedRisk($this->userEntity)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager       = $this->get('doctrine.orm.entity_manager');
            $projectId           = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $projectChargeType   = null;
            $chargeAmountVatFree = -1;
            $chargeAmountVat     = -1;
            $chargeInvoiceDate   = null;

            $errors = [];

            if (null === ($project = $entityManager->getRepository(Projects::class)->find($projectId))) {
                $errors[] = 'Le projet n\'existe pas.';
            }
            if (empty($_POST['fee-type'])
                || false === ($projectChargeType = filter_var($_POST['fee-type'], FILTER_VALIDATE_INT))
                || null === ($projectChargeType = $entityManager->getRepository(ProjectChargeType::class)->find($projectChargeType))
            ) {
                $errors[] = 'Le type de frais est incorrect';
            }
            if (empty($_POST['fee-amount-vat-free'])
                || false === ($chargeAmountVatFree = filter_var(str_replace(',', '.', $_POST['fee-amount-vat-free']), FILTER_VALIDATE_FLOAT))
                || $chargeAmountVatFree < 0
            ) {
                $errors[] = 'Le montant HT saisi est incorrect';
            }
            if (empty($_POST['fee-amount-vat'])
                || false === ($chargeAmountVat = filter_var(str_replace(',', '.', $_POST['fee-amount-vat']), FILTER_VALIDATE_FLOAT))
                || $chargeAmountVat < 0
            ) {
                $errors[] = 'Le montant TVA saisi est incorrect';
            }
            if (empty($_POST['fee-invoice-date'])
                || false === ($chargeInvoiceDate = \DateTime::createFromFormat('d/m/y', $_POST['fee-invoice-date']))
            ) {
                $errors[] = 'Le format de la date est incorrect';
            }
            /** @var \Doctrine\Common\Collections\ArrayCollection $mission */
            $mission = $project->getDebtCollectionMissions();
            if (0 === $mission->count()) {
                $errors[] = 'Il n\'y a pas de mission de recouvrement en cours sur ce projet';
            } elseif ($mission->count() > 1) {
                $errors[] = 'Il y a plusieurs missions de recouvrement en cours sur ce projet';
            }

            if (empty($errors)) {
                $chargeAmountVatIncl = round(bcadd($chargeAmountVatFree, $chargeAmountVat, 4), 2);

                $newCharge = new ProjectCharge();
                $newCharge->setIdProject($project)
                    ->setIdMission($mission->first())
                    ->setIdType($projectChargeType)
                    ->setStatus(ProjectCharge::STATUS_PAID_BY_UNILEND)
                    ->setAmountInclVat($chargeAmountVatIncl)
                    ->setAmountVat(round($chargeAmountVat, 2))
                    ->setInvoiceDate($chargeInvoiceDate);

                $entityManager->persist($newCharge);
                $entityManager->flush($newCharge);
            }

            $this->sendAjaxResponse(empty($errors), null, $errors);
        }

        $this->sendAjaxResponse(false, null, ['Paramètres ou requête incorrects']);
    }
}
