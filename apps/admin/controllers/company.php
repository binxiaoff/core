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
        $this->siren = '';

        if (isset($this->params[0])) {
            $this->siren = $this->params[0];
        }
        $this->client  = new Clients();
        $this->company = new Companies();

        if ($this->request->isMethod('POST')) {
            if ($this->save()) {
                $_SESSION['freeow']['title']   = 'Société créée.';
                $_SESSION['freeow']['message'] = 'La Société est bien créée !';

                header('Location: ' . $this->url . '/company/edit/' . $this->company->getIdCompany());
            } else {
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

    public function _edit()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (empty($this->params[0])) {
            header('Location: ' . $this->url . '/company');
            die;
        }
        $this->company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->params[0]);

        if (null === $this->company) {
            header('Location: ' . $this->url . '/company');
            die;
        }
        $this->client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->company->getIdClientOwner());

        if (null === $this->client) {
            header('Location: ' . $this->url . '/company');
            die;
        }
        $this->siren                = $this->company->getSiren();
        $this->bankAccount          = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->client);
        $this->bankAccountDocuments = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy([
            'idClient' => $this->client,
            'idType'   => AttachmentType::RIB
        ]);

        if ($this->request->isMethod('POST')) {
            if ($this->save()) {
                $_SESSION['freeow']['title']   = 'Société sauvegardée.';
                $_SESSION['freeow']['message'] = 'La Société est bien sauvegardée !';
            } else {
                $_SESSION['freeow']['title']   = 'Une erreur survenue !';
                $_SESSION['freeow']['message'] = 'La Société n\'est pas sauvegardée !';
            }

            header('Location: ' . $this->url . '/company/edit/' . $this->company->getIdCompany());
            die;
        }
    }

    /**
     * @return bool
     */
    private function save()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');

        $siren               = substr(filter_var($this->request->request->get('siren'), FILTER_SANITIZE_STRING), 0, 9);
        $corporateName       = filter_var($this->request->request->get('corporate_name'), FILTER_SANITIZE_STRING);
        $title               = filter_var($this->request->request->get('title'), FILTER_SANITIZE_STRING);
        $name                = filter_var($this->request->request->get('name'), FILTER_SANITIZE_STRING);
        $firstName           = filter_var($this->request->request->get('firstName'), FILTER_SANITIZE_STRING);
        $email               = filter_var($this->request->request->get('email'), FILTER_SANITIZE_EMAIL);
        $phone               = filter_var($this->request->request->get('phone'), FILTER_SANITIZE_STRING);
        $address             = filter_var($this->request->request->get('address'), FILTER_SANITIZE_STRING);
        $postCode            = filter_var($this->request->request->get('postCode'), FILTER_SANITIZE_STRING);
        $city                = filter_var($this->request->request->get('city'), FILTER_SANITIZE_STRING);
        $invoiceEmail        = filter_var($this->request->request->get('invoice_email'), FILTER_SANITIZE_EMAIL);
        $invoiceEmail        = empty($invoiceEmail) ? $email : $invoiceEmail;
        $bankAccountDocument = $this->request->files->get('rib');
        $registryForm        = $this->request->files->get('kbis');

        if (1 !== preg_match('/^\d{9}$/', $siren)) {
            $_SESSION['freeow']['title']   = 'Une erreur survenue !';
            $_SESSION['freeow']['message'] = 'SIREN n\'est pas valide.';
            header('Location: ' . $this->url . '/company/add' . (isset($this->params[0]) ? '/' . $this->params[0] : ''));
        }

        $entityManager->beginTransaction();
        try {
            $this->client->setEmail($email)
                         ->setIdLangue('fr')
                         ->setStatus(Clients::STATUS_ONLINE)
                         ->setCivilite($title)
                         ->setNom($name)
                         ->setPrenom($firstName);

            if (false === $entityManager->contains($this->client)) {
                $entityManager->persist($this->client);
            }
            $entityManager->flush($this->client);

            $this->company->setSiren($siren)
                          ->setName($corporateName)
                          ->setStatusAdresseCorrespondance(Companies::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL)
                          ->setEmailDirigeant($email)
                          ->setEmailFacture($invoiceEmail)
                          ->setIdClientOwner($this->client->getIdClient())
                          ->setAdresse1($address)
                          ->setZip($postCode)
                          ->setCity($city)
                          ->setPhone($phone);

            if (false === $entityManager->contains($this->company)) {
                $entityManager->persist($this->company);
            }
            $entityManager->flush($this->company);

            if ($bankAccountDocument) {
                $attachmentTypeRib = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::RIB);
                $attachmentManager->upload($this->client, $attachmentTypeRib, $bankAccountDocument);
            }
            if ($registryForm) {
                $attachmentTypeKbis = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::KBIS);
                $attachmentManager->upload($this->client, $attachmentTypeKbis, $registryForm);
            }

            $entityManager->commit();

            return true;
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            return false;
        }
    }
}
