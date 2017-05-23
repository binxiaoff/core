<?php

use Symfony\Component\HttpFoundation\File\File;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;

class bank_accountController extends bootstrap
{
    public function _extraction_rib_lightbox()
    {
        $this->hideDecoration();

        $this->isImage = false;

        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager    = $this->get('doctrine.orm.entity_manager');
            $this->attachment = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->find($this->params[0]);

            if ($this->attachment) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
                $attachmentManager = $this->get('unilend.service.attachment_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
                $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                try {
                    $file = new File($attachmentManager->getFullPath($this->attachment));
                    if (in_array($file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png', 'image/bmp'])) { // The 4 formats supported by most of the web browser
                        $this->isImage = true;
                    }
                } catch (Exception $exception) {
                    $this->isImage = false;
                }

                if ($this->request->isMethod('POST')) {
                    $iban = $this->request->request->get('iban1')
                        . $this->request->request->get('iban2')
                        . $this->request->request->get('iban3')
                        . $this->request->request->get('iban4')
                        . $this->request->request->get('iban5')
                        . $this->request->request->get('iban6')
                        . $this->request->request->get('iban7');
                    if (trim($iban) && $this->request->request->get('bic')) {
                        try {
                            $bankAccountManager->saveBankInformation($this->attachment->getClient(), $_POST['bic'], $iban, $this->attachment);
                        } catch (Exception $exception) {
                            $_SESSION['freeow']['title']   = 'Erreur RIB';
                            $_SESSION['freeow']['message'] = $exception->getMessage();
                        }
                    }
                    header('Location: ' . $this->request->server->get('HTTP_REFERER'));
                    die;
                }
            }
        }
    }

    public function _validate_rib_lightbox()
    {
        $this->hideDecoration();
        if (false === empty($this->params[0])) {
            $entityManager     = $this->get('doctrine.orm.entity_manager');
            $bankAccountId     = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $this->bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($bankAccountId);
        }
    }

    public function _validate_rib()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if ($this->request->isMethod('POST') && $this->request->request->get('id_bank_account')) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var BankAccount $bankAccount */
            $entityManager->beginTransaction();
            try {
                $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($this->request->request->get('id_bank_account'));
                if ($bankAccount) {
                    $currentBankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($bankAccount->getIdClient());
                    $currentIban        = '';
                    if ($currentBankAccount) {
                        $currentIban = $currentBankAccount->getIban();
                    }
                    if ($bankAccount->getIdClient()->isBorrower()) {
                        $this->updateMandat($bankAccount->getIdClient());
                    }
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $oMailerManager */
                    $oMailerManager = $this->get('unilend.service.email_manager');
                    $oMailerManager->sendIbanUpdateToStaff($bankAccount->getIdClient()->getIdClient(), $currentIban, $bankAccount->getIban());

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
                    $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                    $bankAccountManager->validateBankAccount($bankAccount);
                }
                $entityManager->commit();
            } catch (Exception $exception) {
                $entityManager->rollback();
                $_SESSION['freeow']['title']   = 'Erreur RIB';
                $_SESSION['freeow']['message'] = $exception->getMessage();
            }
            header('Location: ' . $this->request->server->get('HTTP_REFERER'));
            die;
        }
        header('Location: ' . $this->lurl);
        die;
    }

    /**
     * @param Clients $client
     */
    private function updateMandat(Clients $client)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $companies     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['idClientOwner' => $client->getIdClient()]);
        foreach ($companies as $company) {
            $projects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $company]);
            foreach ($projects as $project) {
                if (in_array($project->getStatus(), [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE])) {
                    continue;
                }
                $mandates = $project->getMandates();
                if (false === empty($mandates)) {
                    foreach ($mandates as $mandate) {
                        if ($mandate->getStatus() === UniversignEntityInterface::STATUS_ARCHIVED) {
                            continue;
                        }
                        $nouveauNom    = str_replace('mandat', 'mandat-' . $mandate->getId(), $mandate->getName());
                        $chemin        = $this->path . 'protected/pdf/mandat/' . $mandate->getName();
                        $nouveauChemin = $this->path . 'protected/pdf/mandat/' . $nouveauNom;

                        if (file_exists($chemin)) {
                            rename($chemin, $nouveauChemin);
                        }

                        $mandate->setName($nouveauNom);
                        $mandate->setStatus(UniversignEntityInterface::STATUS_ARCHIVED);
                        $entityManager->flush($mandate);
                    }
                    // No need to create the new mandat, it will be created in pdf::_mandat()

                    $paymentSchedule = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
                    if (null === $paymentSchedule) {
                        continue;
                    }
                    $monthlyPayment = round(bcdiv($paymentSchedule->getMontant() + $paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);
                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $nextDirectDebit = $entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements')->findOneBy(
                        ['idProject' => $project, 'status' => Prelevements::STATUS_PENDING],
                        ['dateEcheanceEmprunteur' => 'DESC']
                    );

                    if (null === $nextDirectDebit) {
                        continue;
                    }

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->lurl,
                        'prenom_e'               => $client->getPrenom(),
                        'nom_e'                  => $company->getName(),
                        'mensualite'             => $this->ficelle->formatNumber($monthlyPayment),
                        'montant'                => $this->ficelle->formatNumber($project->getAmount(), 0),
                        'link_compte_emprunteur' => $this->lurl . '/projects/detail/' . $project->getIdProject(),
                        'link_mandat'            => $this->furl . '/pdf/mandat/' . $client->getHash() . '/' . $project->getIdProject(),
                        'link_pouvoir'           => $this->furl . '/pdf/pouvoir/' . $client->getHash() . '/' . $project->getIdProject(),
                        'projet'                 => $project->getTitle(),
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw,
                        'date_echeance'          => $nextDirectDebit->getDateEcheanceEmprunteur()->format('d/m/Y')
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('changement-de-rib', $varMail);
                    $message->setTo($client->getEmail());
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }
            }
        }
    }
}
