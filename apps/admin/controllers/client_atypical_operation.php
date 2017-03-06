<?php

use \Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation;
use \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientVigilanceStatusManager;

class client_atypical_operationController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->users->checkAccess('transferts');

        $this->menu_admin = 'transferts';
    }

    public function _default()
    {
        header('Location: /client_atypical_operation/detections');
        die;
    }

    public function _detections()
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em                                    = $this->get('doctrine.orm.entity_manager');
        $this->atypicalOperation['pending']    = $em->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => ClientAtypicalOperation::STATUS_PENDING],
                ['added' => 'ASC', 'client' => 'ASC']
            );
        $this->atypicalOperation['waitingACK'] = $em->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => ClientAtypicalOperation::STATUS_WAITING_ACK],
                ['added' => 'ASC', 'client' => 'ASC']
            );
        $this->atypicalOperation['treated']    = $em->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
            ->findBy(
                ['detectionStatus' => ClientAtypicalOperation::STATUS_TREATED],
                ['added' => 'ASC', 'client' => 'ASC']
            );
        $this->showActions = true;
        $this->userEntity = $em->getRepository('UnilendCoreBusinessBundle:Users');
        $this->lendersAccount = $em->getRepository('UnilendCoreBusinessBundle:LendersAccounts');
    }

    public function _process_detection_box()
    {
        $this->hideDecoration();
        /** @var \Doctrine\ORM\EntityManager $em */
        $em                          = $this->get('doctrine.orm.entity_manager');
        $this->atypicalOperation     = $em->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->find($this->params[1]);
        $this->clientVigilanceStatus = $em->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')
            ->findOneBy(
                ['client' => $this->atypicalOperation->getClient()],
                ['added' => 'DESC']
            );
        $this->action                = $this->params[0];

        switch ($this->params[0]) {
            case 'doubt':
                $this->title          = 'Levée du doute';
                $this->vigilanceRules = $em->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findAll();
                break;
            case 'ack':
                $this->title = 'Soummetre à SFPMEI';
                break;
            default:
                header('Location: /client_atypical_operation/detections');
                die;
        }
    }

    public function _process_detection()
    {
        $this->hideDecoration();
        /** @var \Doctrine\ORM\EntityManager $em */
        $em                = $this->get('doctrine.orm.entity_manager');
        $atypicalOperation = $em->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->find($this->params[1]);

        switch ($this->params[0]) {
            case 'doubt':
                $this->liftingOfDoubt($atypicalOperation);
                break;
            case 'ack':
                $this->askAcknowledgment($atypicalOperation);
                break;
            default:
                header('Location: /client_atypical_operation/detections');
                die;
        }
    }

    /**
     * @param ClientAtypicalOperation $atypicalOperation
     */
    private function liftingOfDoubt(ClientAtypicalOperation $atypicalOperation)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var ClientVigilanceStatusManager $clientVigilanceStatusManager */
        $clientVigilanceStatusManager = $this->get('unilend.service.client_vigilance_status_manager');
        $clientVigilanceStatus        = $em->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')
            ->findOneBy(
                ['client' => $atypicalOperation->getClient()],
                ['added' => 'DESC']
            );

        if (
            true === isset($_POST['vigilance_status']) &&
            array_key_exists($_POST['vigilance_status'], VigilanceRule::$vigilanceStatusLabel) &&
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
                    $newAtypicalOperation = new ClientAtypicalOperation();

                    $newAtypicalOperation->setClient($clientVigilanceStatus->getClient())
                        ->setDetectionStatus(ClientAtypicalOperation::STATUS_TREATED)
                        ->setRule($em->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->find($_POST['vigilance_rule']))
                        ->setIdUser($_SESSION['user']['id_user'])
                        ->setUserComment($_POST['user_comment']);
                    $em->persist($newAtypicalOperation);
                    $em->flush($newAtypicalOperation);

                    $clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory(
                        $clientVigilanceStatus->getClient(),
                        $_POST['vigilance_status'],
                        $_SESSION['user']['id_user'],
                        $newAtypicalOperation,
                        $_POST['user_comment']
                    );
                    $atypicalOperation->setDetectionStatus(ClientAtypicalOperation::STATUS_TREATED);
                    $result = 'OK';
                } else {
                    $result = 'KO';
                }
            }
        } else {
            $result = 'KO';
        }
        $em->flush($atypicalOperation);
        echo json_encode(['message' => $result]);
    }

    /**
     * @param ClientAtypicalOperation $atypicalOperation
     */
    private function askAcknowledgment(ClientAtypicalOperation $atypicalOperation)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        if (false === empty($_POST['user_comment'])) {
            $atypicalOperation->setDetectionStatus(ClientAtypicalOperation::STATUS_WAITING_ACK)
                ->setIdUser($_SESSION['user']['id_user'])
                ->setUserComment($_POST['user_comment']);
            $em->flush($atypicalOperation);
            echo json_encode(['message' => 'OK']);
        } else {
            echo json_encode(['message' => 'KO']);
        }
    }
}
