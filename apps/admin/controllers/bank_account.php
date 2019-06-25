<?php

use Symfony\Component\HttpFoundation\File\File;
use Unilend\Entity\{Attachment, BankAccount, Clients, EcheanciersEmprunteur, Prelevements, ProjectsStatus, UniversignEntityInterface, Zones};

class bank_accountController extends bootstrap
{
    public function _extraction_rib_lightbox()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->hideDecoration();

        $this->isImage = false;

        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager    = $this->get('doctrine.orm.entity_manager');
            $this->attachment = $entityManager->getRepository(Attachment::class)->find($this->params[0]);

            if ($this->attachment) {
                /** @var \Unilend\Service\Attachment\AttachmentManager $attachmentManager */
                $attachmentManager = $this->get('unilend.service.attachment_manager');
                /** @var \Unilend\Service\BankAccountManager $bankAccountManager */
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
                            $bankAccountManager->saveBankInformation($this->attachment->getClientOwner(), $_POST['bic'], $iban, $this->attachment);
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
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->hideDecoration();

        if (false === empty($this->params[0])) {
            $entityManager     = $this->get('doctrine.orm.entity_manager');
            $bankAccountId     = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $this->bankAccount = $entityManager->getRepository(BankAccount::class)->find($bankAccountId);
        }
    }

    public function _validate_rib()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        if ($this->request->isMethod('POST') && $this->request->request->get('id_bank_account')) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var BankAccount $bankAccount */
            $entityManager->beginTransaction();

            try {
                $bankAccount = $entityManager->getRepository(BankAccount::class)->find($this->request->request->get('id_bank_account'));
                if ($bankAccount) {
                    $currentBankAccount = $entityManager->getRepository(BankAccount::class)->getClientValidatedBankAccount($bankAccount->getIdClient());
                    $currentIban        = '';
                    if ($currentBankAccount) {
                        $currentIban = $currentBankAccount->getIban();
                    }
                    if ($bankAccount->getIdClient()->isBorrower()) {
                        $this->updateMandat($bankAccount->getIdClient());
                    }
                    /** @var \Unilend\Service\UnilendMailerManager $oMailerManager */
                    $oMailerManager = $this->get('unilend.service.email_manager');
                    $oMailerManager->sendIbanUpdateToStaff($bankAccount->getIdClient()->getIdClient(), $currentIban, $bankAccount->getIban());

                    /** @var \Unilend\Service\BankAccountManager $bankAccountManager */
                    $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                    $bankAccountManager->validate($bankAccount);
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
        $companies     = $entityManager->getRepository(Companies::class)->findBy(['idClientOwner' => $client]);

        foreach ($companies as $company) {
            $projects = $entityManager->getRepository(Projects::class)->findBy(['idCompany' => $company]);
            foreach ($projects as $project) {
                if (ProjectsStatus::STATUS_FINISHED === $project->getStatus()) {
                    continue;
                }
                $mandates = $project->getMandates();
                if (false === empty($mandates)) {
                    foreach ($mandates as $mandate) {
                        if (UniversignEntityInterface::STATUS_ARCHIVED === $mandate->getStatus()) {
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

                    $paymentSchedule = $entityManager->getRepository(EcheanciersEmprunteur::class)->findOneBy(['idProject' => $project]);
                    if (null === $paymentSchedule) {
                        continue;
                    }

                    $monthlyPayment  = round(bcdiv($paymentSchedule->getMontant() + $paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);
                    $nextDirectDebit = $entityManager->getRepository(Prelevements::class)->findOneBy(
                        ['idProject' => $project, 'status' => Prelevements::STATUS_PENDING],
                        ['dateEcheanceEmprunteur' => 'ASC']
                    );

                    if (null === $nextDirectDebit) {
                        continue;
                    }

                    $keywords = [
                        'firstName'         => $client->getFirstName(),
                        'monthlyAmount'     => $this->ficelle->formatNumber($monthlyPayment),
                        'companyName'       => $company->getName(),
                        'mandateLink'       => $this->furl . '/pdf/mandat/' . $client->getHash() . '/' . $project->getIdProject(),
                        'nextRepaymentDate' => $nextDirectDebit->getDateEcheanceEmprunteur()->format('d/m/Y'),
                    ];

                    /** @var \Unilend\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('changement-de-rib', $keywords);

                    try {
                        $message->setTo($client->getEmail());
                        $mailer = $this->get('mailer');
                        $mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->get('logger')->warning(
                            'Could not send email: changement-de-rib - Exception - Exception ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }
    }
}
