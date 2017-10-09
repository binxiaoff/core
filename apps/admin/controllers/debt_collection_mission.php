<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;

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

        if ($this->isUserTypeRisk()) {
            if (false === empty($this->params[0])) {
                $missionId = filter_var($this->params[0], FILTER_VALIDATE_INT);
                if (null !== ($debtCollectionMission = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission')->find($missionId))) {
                    try {
                        if (is_dir($this->getParameter('path.protected') . $debtCollectionMission->getAttachment())
                            || false === file_exists($this->getParameter('path.protected') . $debtCollectionMission->getAttachment())) {
                            $debtCollectionMissionManager->generateExcelFile($debtCollectionMission);
                        }
                        header('Content-Type: application/force-download; charset=utf-8');
                        header('Content-Disposition: attachment;filename=' . basename($debtCollectionMission->getAttachment()));
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Expires: 0');
                        readfile($this->getParameter('path.protected') . $debtCollectionMission->getAttachment());
                        die;
                    } catch (\Exception $exception) {
                        $this->get('logger')->warning(
                            'Could not download the Excel file for debt collection mission: ' . $debtCollectionMission->getId() . ' Error: ' . $exception->getMessage(),
                            ['file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                        header('Location: ' . $this->url . '/dossiers/details_impayes/' . $debtCollectionMission->getIdProject()->getIdProject());
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

        if (false === empty($this->params[0] && $this->isUserTypeRisk())) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager      = $this->get('doctrine.orm.entity_manager');
            $projectId          = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $debtCollector      = null;
            $debtCollectionType = null;
            $feesRate           = -1;
            $errors             = [];

            if (null === ($project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId))) {
                $errors[] = 'Le projet n\'existe pas.';
            }
            if (false === empty($_POST['debt-collector-hash'])) {
                $debtCollector = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $_POST['debt-collector-hash']]);
            }
            if (null === $debtCollector) {
                $errors[] = 'Le recouvreur n\'existe pas.';
            }
            if (empty($_POST['debt-collection-type'])
                || false === ($debtCollectionType = filter_var($_POST['debt-collection-type'], FILTER_VALIDATE_INT))
                || false === in_array($debtCollectionType, [DebtCollectionMission::TYPE_AMICABLE, DebtCollectionMission::TYPE_LITIGATION,])
            ) {
                $errors[] = 'Le type de mission est incorrect: valeures possibles (1, 2). Fournit: ' . $debtCollectionType;
            }
            if (empty($_POST['debt-collection-rate'])
                || false === ($feesRate = filter_var(str_replace(',', '.', $_POST['debt-collection-rate']), FILTER_VALIDATE_FLOAT))
                || $feesRate < 0
            ) {
                $errors[] = 'Le taux d\'honoraires est incorrect.';
            }
            $feesRate = round(bcdiv($feesRate, 100, 6), 4);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager $debtCollectionManager */
            $debtCollectionManager = $this->get('unilend.service.debt_collection_mission_manager');
            $user                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

            if (empty($errors)) {
                $newMission = $debtCollectionManager->newMission($project, $debtCollector, $debtCollectionType, $feesRate, $user);

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

            echo json_encode(['error' => $errors, 'success' => empty($errors)]);
            return;
        }

        header('Location: ' . $this->url);
        die;
    }

    public function _addCharge()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if (false === empty($this->params[0] && $this->isUserTypeRisk())) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager       = $this->get('doctrine.orm.entity_manager');
            $projectId           = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $projectChargeType   = null;
            $chargeAmountVatFree = -1;
            $chargeAmountVat     = -1;
            $chargeInvoiceDate   = null;

            $errors = [];

            if (null === ($project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId))) {
                $errors[] = 'Le projet n\'existe pas.';
            }
            if (empty($_POST['fee-type'])
                || false === ($projectChargeType = filter_var($_POST['fee-type'], FILTER_VALIDATE_INT))
                || null === ($projectChargeType = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectChargeType')->find($projectChargeType))
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
                || false === ($chargeInvoiceDate = \DateTime::createFromFormat('d/m/Y', $_POST['fee-invoice-date']))
            ) {
                $errors[] = 'Le format de la date est incorrect';
            }

            if (empty($errors)) {
                $chargeAmountVatIncl = round(bcadd($chargeAmountVatFree, $chargeAmountVat, 4), 2);

                $newCharge = new ProjectCharge();
                $newCharge->setIdProject($project)
                    ->setIdType($projectChargeType)
                    ->setStatus(ProjectCharge::STATUS_PENDING)
                    ->setAmountInclVat($chargeAmountVatIncl)
                    ->setAmountVat(round($chargeAmountVat, 2))
                    ->setInvoiceDate($chargeInvoiceDate);

                $entityManager->persist($newCharge);
                $entityManager->flush($newCharge);
            }

            echo json_encode(['error' => $errors, 'success' => empty($errors)]);
            return;
        }

        header('Location: ' . $this->url);
        die;
    }

    /**
     * @return bool
     */
    private function isUserTypeRisk()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $user          = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

        if (\users_types::TYPE_RISK == $user->getIdUserType()->getIdUserType()
            || $user->getIdUser() == \Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_ALAIN_ELKAIM
            || isset($this->params[1]) && 'risk' == $this->params[1] && in_array($user->getIdUserType()->getIdUserType(), [\users_types::TYPE_ADMIN, \users_types::TYPE_IT])
        ) {
            return true;
        }

        return false;
    }
}
