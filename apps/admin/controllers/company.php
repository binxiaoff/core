<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{AddressType, Attachment, AttachmentType, BankAccount, Clients, Companies, Pays, Zones};
use Unilend\Entity\External\Altares\{CompanyIdentityDetail, EstablishmentIdentityDetail};

class companyController extends bootstrap
{
    /** @var TranslatorInterface */
    protected $translator;
    /** @var Clients */
    protected $client;
    /** @var Companies */
    protected $company;
    /** @var string */
    protected $siren;

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
        $this->translator = $this->get('translator');
    }

    public function _default()
    {
        if ($this->request->request->get('siren')) {
            $siren = filter_var($this->request->request->get('siren'), FILTER_SANITIZE_STRING);
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager   = $this->get('doctrine.orm.entity_manager');
            $this->companies = $entityManager->getRepository(Companies::class)->findBy(['siren' => $siren], ['idCompany' => 'DESC']);
        }
    }

    public function _add()
    {
        $this->siren = '';

        if (isset($this->params[0])) {
            $this->siren = $this->params[0];
        }
        $this->client         = new Clients();
        $this->company        = new Companies();

        if ($this->request->isMethod(Request::METHOD_POST)) {
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
            /** @var \Unilend\Service\WebServiceClient\AltaresManager $altares */
            $altares = $this->get('unilend.service.ws_client.altares_manager');
            /** @var \Unilend\Service\WebServiceClient\InfolegaleManager $infoLegale */
            $infoLegale = $this->get('unilend.service.ws_client.infolegale_manager');
            /** @var \JMS\Serializer\Serializer $serializer */
            $serializer = $this->get('jms_serializer');

            $companyIdentity = [];
            try {
                $altaresCompanyIdentity = $altares->getCompanyIdentity($siren);

                if ($altaresCompanyIdentity instanceof CompanyIdentityDetail) {
                    $companyIdentity = [
                        'corporateName' => $altaresCompanyIdentity->getCorporateName(),
                        'address'       => $altaresCompanyIdentity->getAddress(),
                        'postCode'      => $altaresCompanyIdentity->getPostCode(),
                        'city'          => $altaresCompanyIdentity->getCity(),
                    ];
                }
                $altaresEstablishmentIdentity = $altares->getEstablishmentIdentity($siren);
                $infoLegaleIdentity           = $infoLegale->getIdentity($siren);

                if ($altaresEstablishmentIdentity instanceof EstablishmentIdentityDetail) {
                    $companyIdentity['phoneNumber'] = $altaresEstablishmentIdentity->getPhoneNumber();
                }
                if (false === empty($infoLegaleIdentity->getDirectors()) && 0 < $infoLegaleIdentity->getDirectors()->count()) {
                    $companyIdentity['title']          = $infoLegaleIdentity->getDirectors()->first()->getTitle();
                    $companyIdentity['ownerName']      = $infoLegaleIdentity->getDirectors()->first()->getName();
                    $companyIdentity['ownerFirstName'] = $infoLegaleIdentity->getDirectors()->first()->getFirstName();
                }

                echo $serializer->serialize($companyIdentity, 'json');
            } catch (\Exception $exception) {
                echo $serializer->serialize(['error' => 'Problème technique, veuillez réessayer ultérieurement.'], 'json');
            }
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
        $this->company = $entityManager->getRepository(Companies::class)->find($this->params[0]);

        if (null === $this->company) {
            header('Location: ' . $this->url . '/company');
            die;
        }

        $this->client = $this->company->getIdClientOwner();

        if (null === $this->client || empty($this->client->getIdClient())) {
            header('Location: ' . $this->url . '/company');
            die;
        }

        $this->siren                = $this->company->getSiren();
        $this->bankAccount          = $entityManager->getRepository(BankAccount::class)->getClientValidatedBankAccount($this->client);
        $this->bankAccountDocuments = $entityManager->getRepository(Attachment::class)->findBy([
            'idClient' => $this->client,
            'idType'   => AttachmentType::RIB
        ]);

        if ($this->request->isMethod(Request::METHOD_POST)) {
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
     * @throws \Doctrine\DBAL\ConnectionException
     */
    private function save(): bool
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Service\AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        /** @var \Unilend\Service\AddressManager $addressManager */
        $addressManager = $this->get('unilend.service.address_manager');

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
            $_SESSION['freeow']['title']   = 'Une erreur est survenue';
            $_SESSION['freeow']['message'] = 'Le SIREN n‘est pas valide.';

            header('Location: ' . $this->lurl . $_SERVER['REQUEST_URI']);
            exit;
        }

        if ($email !== $this->client->getEmail()) {
            $duplicates = $entityManager
                ->getRepository(Clients::class)
                ->findGrantedLoginAccountsByEmail($email);

            if (false === empty($duplicates)) {
                $_SESSION['freeow']['title']   = 'Une erreur est survenue';
                $_SESSION['freeow']['message'] = 'Cette adresse email est déjà utilisée par un autre client.';

                header('Location: ' . $this->lurl . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $entityManager->beginTransaction();

        try {
            $this->client
                ->setEmail($email)
                ->setIdLangue('fr')
                ->setCivilite($title)
                ->setNom($name)
                ->setPrenom($firstName);

            if (false === $entityManager->contains($this->client)) {
                $entityManager->persist($this->client);
            }
            $entityManager->flush($this->client);

            $this->company
                ->setSiren($siren)
                ->setName($corporateName)
                ->setStatusAdresseCorrespondance(Companies::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL)
                ->setEmailDirigeant($email)
                ->setEmailFacture($invoiceEmail)
                ->setIdClientOwner($this->client)
                ->setPhone($phone);

            if (false === $entityManager->contains($this->company)) {
                $entityManager->persist($this->company);
            }
            $entityManager->flush($this->company);

            $addressManager->saveCompanyAddress($address, $postCode, $city, Pays::COUNTRY_FRANCE, $this->company, AddressType::TYPE_MAIN_ADDRESS);

            if ($bankAccountDocument) {
                $attachmentTypeRib = $entityManager->getRepository(AttachmentType::class)->find(AttachmentType::RIB);
                $attachmentManager->upload($this->client, $attachmentTypeRib, $bankAccountDocument);
            }
            if ($registryForm) {
                $attachmentTypeKbis = $entityManager->getRepository(AttachmentType::class)->find(AttachmentType::KBIS);
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
