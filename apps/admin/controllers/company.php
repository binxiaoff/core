<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentity;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;

class companyController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->users->checkAccess('emprunteurs');

        $this->menu_admin = 'emprunteurs';
        $this->translator = $this->get('translator');
    }

    public function _default()
    {
        if ($this->request->request->get('siren')) {
            $siren = filter_var($this->request->request->get('siren'), FILTER_SANITIZE_STRING);
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager   = $this->get('doctrine.orm.entity_manager');
            $this->companies = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['siren' => $siren], ['idCompany' => 'DESC']);
        }
    }

    public function _add()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager $bankAccountManager */
        $bankAccountManager = $this->get('unilend.service.bank_account_manager');

        $this->sectors = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanySector')->findAll();
        $this->siren   = '';
        if (isset($this->params[0])) {
            $this->siren = $this->params[0];
        }

        if ($this->request->isMethod('POST')) {
            $siren               = substr(filter_var($this->request->request->get('siren'), FILTER_SANITIZE_STRING), 0, 9);
            $corporateName       = filter_var($this->request->request->get('corporate_name'), FILTER_SANITIZE_STRING);
            $title               = filter_var($this->request->request->get('title'), FILTER_SANITIZE_STRING);
            $name                = filter_var($this->request->request->get('name'), FILTER_SANITIZE_STRING);
            $firstName           = filter_var($this->request->request->get('firstname'), FILTER_SANITIZE_STRING);
            $email               = filter_var($this->request->request->get('email'), FILTER_SANITIZE_EMAIL);
            $phone               = filter_var($this->request->request->get('phone'), FILTER_SANITIZE_STRING);
            $address             = filter_var($this->request->request->get('address'), FILTER_SANITIZE_STRING);
            $postCode            = filter_var($this->request->request->get('postCode'), FILTER_SANITIZE_STRING);
            $city                = filter_var($this->request->request->get('city'), FILTER_SANITIZE_STRING);
            $invoiceEmail        = filter_var($this->request->request->get('invoice_email'), FILTER_SANITIZE_EMAIL);
            $invoiceEmail        = empty($invoiceEmail) ? $email : $invoiceEmail;
            $bic                 = filter_var($this->request->request->get('bic'), FILTER_SANITIZE_STRING);
            $iban                = $this->request->request->get('iban1')
                . $this->request->request->get('iban2')
                . $this->request->request->get('iban3')
                . $this->request->request->get('iban4')
                . $this->request->request->get('iban5')
                . $this->request->request->get('iban6')
                . $this->request->request->get('iban7');
            $iban                = filter_var($iban, FILTER_SANITIZE_STRING);
            $bankAccountDocument = $this->request->files->get('rib');
            $registryForm        = $this->request->files->get('kbis');

            if (1 !== preg_match('/^\d{9}$/', $siren)) {
                $_SESSION['freeow']['title']   = 'Une erreur survenue !';
                $_SESSION['freeow']['message'] = 'SIREN n\'est pas valide.';
                header('Location: ' . $this->url . '/company/add' . (isset($this->params[0]) ? '/' . $this->params[0] : ''));
            }

            $entityManager->beginTransaction();
            try {
                $client = new Clients();
                $client->setEmail($email)
                       ->setIdLangue('fr')
                       ->setStatus(Clients::STATUS_ONLINE)
                       ->setCivilite($title)
                       ->setNom($name)
                       ->setPrenom($firstName);
                $entityManager->persist($client);
                $entityManager->flush($client);

                $company = new Companies();
                $company->setSiren($siren)
                        ->setName($corporateName)
                        ->setStatusAdresseCorrespondance(Companies::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL)
                        ->setEmailDirigeant($email)
                        ->setEmailFacture($invoiceEmail)
                        ->setIdClientOwner($client->getIdClient())
                        ->setAdresse1($address)
                        ->setZip($postCode)
                        ->setCity($city)
                        ->setPhone($phone);
                $entityManager->persist($company);
                $entityManager->flush($company);

                if ($iban && $bic && $bankAccountDocument) {
                    $attachmentTypeRib = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::RIB);
                    $attachmentRib     = $attachmentManager->upload($client, $attachmentTypeRib, $bankAccountDocument);
                    $bankAccount       = $bankAccountManager->saveBankInformation($client, $bic, $iban, $attachmentRib);
                    $bankAccountManager->validateBankAccount($bankAccount);
                }
                if ($registryForm) {
                    $attachmentTypeKbis = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::KBIS);
                    $attachmentManager->upload($client, $attachmentTypeKbis, $registryForm);
                }

                $entityManager->commit();

                $_SESSION['freeow']['title']   = 'Société créée.';
                $_SESSION['freeow']['message'] = 'La Société est bien créée !';
                header('Location: ' . $this->url . '/company');
            } catch (\Exception $exception) {
                $entityManager->getConnection()->rollBack();
                $_SESSION['freeow']['title']   = 'Une erreur survenue !';
                $_SESSION['freeow']['message'] = 'La Société n\'est pas créée !';
                header('Location: ' . $this->url . '/company/add' . (isset($this->params[0]) ? '/' . $this->params[0] : ''));
            }
            die;
        }
    }

    public function _fetch_details_ajax()
    {
        $this->hideDecoration();
        if (false === empty($this->params[0])) {
            $siren = filter_var($this->params[0], FILTER_SANITIZE_STRING);
            /** @var \Unilend\Bundle\WSClientBundle\Service\AltaresManager $altares */
            $altares = $this->get('unilend.service.ws_client.altares_manager');
            /** @var \Unilend\Bundle\WSClientBundle\Service\InfolegaleManager $infoLegale */
            $infoLegale = $this->get('unilend.service.ws_client.infolegale_manager');

            $altaresCompanyIdentity       = $altares->getCompanyIdentity($siren);
            $altaresEstablishmentIdentity = $altares->getEstablishmentIdentity($siren);
            $infoLegaleIdentity           = $infoLegale->getIdentity($siren);
            $companyIdentity              = [];
            if ($altaresCompanyIdentity instanceof CompanyIdentity) {
                $companyIdentity = [
                    'corporateName' => $altaresCompanyIdentity->getCorporateName(),
                    'address'       => $altaresCompanyIdentity->getAddress(),
                    'postCode'      => $altaresCompanyIdentity->getPostCode(),
                    'city'          => $altaresCompanyIdentity->getCity(),
                ];
            }
            if ($altaresEstablishmentIdentity instanceof EstablishmentIdentity) {
                $companyIdentity['phoneNumber'] = $altaresEstablishmentIdentity->getPhoneNumber();
            }
            if (false === empty($infoLegaleIdentity->dirigeants->dirigeant)) {
                $companyIdentity['title']          = (string) $infoLegaleIdentity->dirigeants->dirigeant->civilite;
                $companyIdentity['ownerName']      = (string) $infoLegaleIdentity->dirigeants->dirigeant->nom;
                $companyIdentity['ownerFirstName'] = (string) $infoLegaleIdentity->dirigeants->dirigeant->prenom;
            }

            /** @var \JMS\Serializer\Serializer $serializer */
            $serializer = $this->get('jms_serializer');

            echo $serializer->serialize($companyIdentity, 'json');
        }
    }
}
