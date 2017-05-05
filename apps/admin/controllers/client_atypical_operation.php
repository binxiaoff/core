<?php

use \Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use \Unilend\Bundle\CoreBusinessBundle\Entity\ClientVigilanceStatusHistory;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientVigilanceStatusManager;
use \Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;

class client_atypical_operationController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->users->checkAccess('transferts');

        $this->menu_admin = 'transferts';

        /** @var \Symfony\Component\Translation\Translator translator */
        $this->translator = $this->get('translator');
    }

    public function _default()
    {
        header('Location: /client_atypical_operation/detections');
        die;
    }

    public function _detections()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                         = $this->get('doctrine.orm.entity_manager');
        $this->atypicalOperation['pending']    = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => ClientAtypicalOperation::STATUS_PENDING],
                ['added' => 'DESC', 'client' => 'DESC']
            );
        $this->atypicalOperation['waitingACK'] = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => ClientAtypicalOperation::STATUS_WAITING_ACK],
                ['updated' => 'DESC', 'client' => 'DESC']
            );
        $this->atypicalOperation['treated']    = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => ClientAtypicalOperation::STATUS_TREATED],
                ['updated' => 'DESC', 'client' => 'DESC'],
                10
            );

        $this->showActions                  = true;
        $this->showUpdated                  = false;
        $this->userEntity                   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
        $this->clientVigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory');
    }

    public function _process_detection_box()
    {
        $this->hideDecoration();
        $this->action = $this->params[0];

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        switch ($this->action) {
            case 'add':
                $this->title               = 'Ajouter une opération atypique';
                $this->vigilanceRules      = $entityManager->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findAll();
                $this->processDetectionUrl = $this->lurl . '/client_atypical_operation/process_detection/' . $this->action . '/' . $this->params[1];
                $this->client              = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[1]);
                break;
            case 'doubt':
                $this->title          = 'Levée du doute';
                $this->vigilanceRules = $entityManager->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findAll();
                $this->getAtypicalOperationDetails($this->params[1]);
                break;
            case 'ack':
                $this->title = 'Soummetre à SFPMEI';
                $this->getAtypicalOperationDetails($this->params[1]);
                break;
            default:
                header('Location: /client_atypical_operation/detections');
                die;
        }
        $this->clientVigilanceStatus = [
            VigilanceRule::VIGILANCE_STATUS_LOW    => $this->translator->trans('client-vigilance_status-' . VigilanceRule::VIGILANCE_STATUS_LOW),
            VigilanceRule::VIGILANCE_STATUS_MEDIUM => $this->translator->trans('client-vigilance_status-' . VigilanceRule::VIGILANCE_STATUS_MEDIUM),
            VigilanceRule::VIGILANCE_STATUS_HIGH   => $this->translator->trans('client-vigilance_status-' . VigilanceRule::VIGILANCE_STATUS_HIGH),
            VigilanceRule::VIGILANCE_STATUS_REFUSE => $this->translator->trans('client-vigilance_status-' . VigilanceRule::VIGILANCE_STATUS_REFUSE),
        ];
    }

    private function getAtypicalOperationDetails($atypicalOperationId)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                     = $this->get('doctrine.orm.entity_manager');
        $atypicalOperation      = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->find($atypicalOperationId);
        $currentVigilanceStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')
            ->findOneBy(
                ['client' => $atypicalOperation->getClient()],
                ['added' => 'DESC']
            );

        if (null !== $currentVigilanceStatus && $currentVigilanceStatus instanceof ClientVigilanceStatusHistory) {
            $this->currentVigilanceStatusId = $currentVigilanceStatus->getVigilanceStatus();
        } else {
            $this->currentVigilanceStatusId = VigilanceRule::VIGILANCE_STATUS_LOW;
        }

        $this->client              = $atypicalOperation->getClient();
        $this->processDetectionUrl = $this->lurl . '/client_atypical_operation/process_detection/' . $this->action . '/' . $atypicalOperation->getId();
        $this->currentUserComment  = $atypicalOperation->getUserComment();
    }

    public function _process_detection()
    {
        $this->hideDecoration();

        switch ($this->params[0]) {
            case 'add':
                $this->addAtypicalOperation();
                break;
            case 'doubt':
                /** @var \Doctrine\ORM\EntityManager $entityManager */
                $entityManager                = $this->get('doctrine.orm.entity_manager');
                $atypicalOperation = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->find($this->params[1]);
                $this->liftingOfDoubt($atypicalOperation);
                break;
            case 'ack':
                /** @var \Doctrine\ORM\EntityManager $entityManager */
                $entityManager                = $this->get('doctrine.orm.entity_manager');
                $atypicalOperation = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->find($this->params[1]);
                $this->askAcknowledgment($atypicalOperation);
                break;
            default:
                header('Location: /client_atypical_operation/detections');
                die;
        }
    }

    public function _export()
    {
        switch ($this->params[0]) {
            case 'pending':
                $status   = ClientAtypicalOperation::STATUS_PENDING;
                $fileName = 'Opérations atypiques non traitées';
                break;
            case 'waiting':
                $status   = ClientAtypicalOperation::STATUS_WAITING_ACK;
                $fileName = 'Opérations atypiques en attente de confirmation';
                break;
            case 'treated':
                $status   = ClientAtypicalOperation::STATUS_TREATED;
                $fileName = 'Opérations atypiques traitées';
                break;
            default:
                header('Location: /client_atypical_operation/detections');
                die;
        }

        $fileName .= (new \DateTime())->format('dMY_His');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                = $this->get('doctrine.orm.entity_manager');
        $atypicalOperation = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => $status],
                ['added' => 'ASC', 'client' => 'ASC']
            );
        $this->createCSV($atypicalOperation, $fileName);
    }

    private function addAtypicalOperation()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var ClientVigilanceStatusManager $clientVigilanceStatusManager */
        $clientVigilanceStatusManager = $this->get('unilend.service.client_vigilance_status_manager');

        /** @todo add radiobutton to choose detection status */
        if (isset($_POST['detection_status'])) {
            $status = $_POST['detection_status'];
        } else {
            $status = ClientAtypicalOperation::STATUS_PENDING;
        }

        if (true === isset($_POST['clientId']) &&
            true === isset($_POST['vigilance_status']) &&
            in_array($_POST['vigilance_status'], [VigilanceRule::VIGILANCE_STATUS_LOW, VigilanceRule::VIGILANCE_STATUS_MEDIUM, VigilanceRule::VIGILANCE_STATUS_HIGH, VigilanceRule::VIGILANCE_STATUS_REFUSE]) &&
            true === isset($_POST['user_comment'])
        ) {
            try {
                $vigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findOneBy(['client' => $_POST['clientId']], ['id' => 'DESC']);

                if (false === empty($_POST['vigilance_rule'])) {
                    $rule = $entityManager->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->find($_POST['vigilance_rule']);
                } else {
                    $rule = $entityManager->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findOneBy(['label' => 'other']);
                }
                /** @var Clients $client */
                $client                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($_POST['clientId']);
                $clientAtypicalOperation = new ClientAtypicalOperation();
                $clientAtypicalOperation->setClient($client)
                    ->setDetectionStatus($status)
                    ->setRule($rule)
                    ->setIdUser($_SESSION['user']['id_user'])
                    ->setUserComment($_POST['user_comment']);
                $entityManager->persist($clientAtypicalOperation);
                $entityManager->flush($clientAtypicalOperation);

                if (false === empty($vigilanceStatusHistory) && $vigilanceStatusHistory->getVigilanceStatus() <= $_POST['vigilance_status']) {
                    $clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory(
                        $client,
                        $_POST['vigilance_status'],
                        $_SESSION['user']['id_user'],
                        $clientAtypicalOperation,
                        $_POST['user_comment']);
                } else {
                    $clientVigilanceStatusManager->retrogradeClientVigilanceStatusHistory(
                        $client,
                        $_POST['vigilance_status'],
                        $_SESSION['user']['id_user'],
                        $clientAtypicalOperation,
                        $_POST['user_comment']);
                }

                echo json_encode(['message' => 'OK']);
            } catch (\Exception $exception) {
                $this->get('logger')->error('Could not add atypical operation. id_client: ' . $_POST['clientId'], ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $_POST['clientId']]);
                echo json_encode(['message' => 'KO']);
                return;
            }
        } else {
            echo json_encode(['message' => 'KO']);
        }
    }

    /**
     * @param ClientAtypicalOperation $atypicalOperation
     */
    private function liftingOfDoubt(ClientAtypicalOperation $atypicalOperation)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var ClientVigilanceStatusManager $clientVigilanceStatusManager */
        $clientVigilanceStatusManager = $this->get('unilend.service.client_vigilance_status_manager');
        $clientVigilanceStatus        = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')
            ->findOneBy(
                ['client' => $atypicalOperation->getClient()],
                ['added' => 'DESC']
            );

        if (
            null !== $clientVigilanceStatus &&
            true === isset($_POST['vigilance_status']) &&
            in_array($_POST['vigilance_status'], [VigilanceRule::VIGILANCE_STATUS_LOW, VigilanceRule::VIGILANCE_STATUS_MEDIUM, VigilanceRule::VIGILANCE_STATUS_HIGH, VigilanceRule::VIGILANCE_STATUS_REFUSE]) &&
            true === isset($_POST['user_comment'])
        ) {
            if ($_POST['vigilance_status'] <= $clientVigilanceStatus->getVigilanceStatus()) {
                $atypicalOperation->setDetectionStatus(ClientAtypicalOperation::STATUS_TREATED)
                    ->setUserComment($_POST['user_comment'])
                    ->setIdUser($_SESSION['user']['id_user']);

                $clientVigilanceStatusManager->retrogradeClientVigilanceStatusHistory(
                    $clientVigilanceStatus->getClient(),
                    $_POST['vigilance_status'],
                    $_SESSION['user']['id_user'],
                    $atypicalOperation,
                    $_POST['user_comment']
                );
                $result = 'OK';
            } else {
                if (false === empty($_POST['vigilance_rule'])) {
                    $rule = $entityManager->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->find($_POST['vigilance_rule']);
                } else {
                    $rule = $entityManager->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findOneBy(['label' => 'other']);
                }
                $atypicalOperation
                    ->setDetectionStatus(ClientAtypicalOperation::STATUS_TREATED)
                    ->setRule($rule)
                    ->setIdUser($_SESSION['user']['id_user'])
                    ->setUserComment($_POST['user_comment']);

                $clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory(
                    $clientVigilanceStatus->getClient(),
                    $_POST['vigilance_status'],
                    $_SESSION['user']['id_user'],
                    $atypicalOperation,
                    $_POST['user_comment']
                );
                $atypicalOperation->setDetectionStatus(ClientAtypicalOperation::STATUS_TREATED);
                $result = 'OK';
            }
        } else {
            $result = 'KO';
        }
        $entityManager->flush($atypicalOperation);
        echo json_encode(['message' => $result]);
    }

    /**
     * @param ClientAtypicalOperation $atypicalOperation
     */
    private function askAcknowledgment(ClientAtypicalOperation $atypicalOperation)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        if (false === empty($_POST['user_comment'])) {
            $atypicalOperation->setDetectionStatus(ClientAtypicalOperation::STATUS_WAITING_ACK)
                ->setIdUser($_SESSION['user']['id_user'])
                ->setUserComment($_POST['user_comment']);
            $entityManager->flush($atypicalOperation);
            echo json_encode(['message' => 'OK']);
        } else {
            echo json_encode(['message' => 'KO']);
        }
    }

    /**
     * @param ClientAtypicalOperation[] $aData
     * @param string                    $fileName
     */
    private function createCSV(array $aData, $fileName)
    {
        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator  = $this->get('translator');
        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);
        $headers     = ['ID client', 'Prénom Nom', 'Règle de vigilance', 'Statut de vigilance', 'Valeur atypique', 'Utilisateur', 'Date de l\'opération', 'Date de modification', 'Commentaire'];

        foreach ($headers as $iIndex => $sColumnName) {
            $activeSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName);
        }

        foreach ($aData as $rowIndex => $row) {
            $coleIndex = 0;
            $opData    = [
                $row->getClient()->getIdClient(),
                $row->getClient()->getPrenom() . ' ' . $row->getClient()->getNom(),
                $row->getRule()->getName(),
                $translator->trans('client-vigilance_status-' . $row->getRule()->getVigilanceStatus()),
                $row->getAtypicalValue(),
                $this->getUserNameByID($row->getIdUser()),
                $row->getAdded()->format('d/m/Y H\hi'),
                empty($row->getUpdated()) ? null : $row->getUpdated()->format('d/m/Y H\hi'),
                $row->getUserComment()
            ];

            foreach ($opData as $cellValue) {
                $activeSheet->setCellValueByColumnAndRow($coleIndex++, $rowIndex + 2, $cellValue);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $fileName . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save('php://output');

        die;
    }

    /**
     * @param int $userId
     * @return string
     */
    private function getUserNameByID($userId)
    {
        if (Users::USER_ID_CRON === $userId) {
            return 'Cron';
        } elseif (Users::USER_ID_FRONT === $userId) {
            return 'Front';
        } else {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users $user */
            $user = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Users')->find($userId);
            return $user->getName() . ' ' . $user->getFirstname();
        }
    }
}
