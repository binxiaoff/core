<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Elements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\core\Loader;

class TermsOfSaleManager
{
    const EXCEPTION_CODE_INVALID_EMAIL        = 1;
    const EXCEPTION_CODE_INVALID_PHONE_NUMBER = 2;
    const EXCEPTION_CODE_PDF_FILE_NOT_FOUND   = 3;

    /** @var EntityManager */
    private $entityManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var string */
    private $rootDirectory;
    /** @var string */
    private $locale;

    /**
     * @param EntityManager $entityManager
     * @param MailerManager $mailerManager
     * @param string        $rootDirectory
     * @param string        $locale
     */
    public function __construct(EntityManager $entityManager, MailerManager $mailerManager, $rootDirectory, $locale)
    {
        $this->entityManager = $entityManager;
        $this->mailerManager = $mailerManager;
        $this->rootDirectory = $rootDirectory;
        $this->locale        = $locale;
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendBorrowerEmail(Projects $project)
    {
        /** @var \ficelle $stringManager */
        $stringManager = Loader::loadLib('ficelle');
        $client        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

        if (empty($client->getEmail())) {
            throw new \Exception('Invalid client email', self::EXCEPTION_CODE_INVALID_EMAIL);
        }

        if (empty($client->getTelephone()) || false === $stringManager->isMobilePhoneNumber($client->getTelephone())) {
            throw new \Exception('Invalid client mobile phone number', self::EXCEPTION_CODE_INVALID_PHONE_NUMBER);
        }

        $termsOfSale = $project->getTermsOfSale();

        if (null === $termsOfSale) {
            $tree = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Lien conditions generales depot dossier']);

            if (null === $tree) {
                throw new \Exception('Unable to find tree element', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
            }

            $termsOfSale = new ProjectCgv();
            $termsOfSale->setIdProject($project);
            $termsOfSale->setIdTree($tree->getValue());
            $termsOfSale->setName($termsOfSale->generateFileName());
            $termsOfSale->setIdUniversign('');
            $termsOfSale->setUrlUniversign('');
            $termsOfSale->setStatus(UniversignEntityInterface::STATUS_PENDING);

            $this->entityManager->persist($termsOfSale);
            $this->entityManager->flush($termsOfSale);
        }

        $pdfElement = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TreeElements')->findOneBy([
            'idTree'    => $termsOfSale->getIdTree(),
            'idElement' => Elements::TYPE_PDF_TERMS_OF_SALE,
            'idLangue'  => substr($this->locale, 0, 2)
        ]);

        if (null === $pdfElement || empty($pdfElement->getValue())) {
            throw new \Exception('Unable to find PDF', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
        }

        $pdfPath = $this->rootDirectory . '/../public/default/var/fichiers/' . $pdfElement->getValue();

        if (false === file_exists($pdfPath)) {
            throw new \Exception('PDF file does not exist', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
        }

        if (false === is_dir($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH)) {
            mkdir($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH);
        }

        if (false === file_exists($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH . $termsOfSale->getName())) {
            copy($pdfPath, $this->rootDirectory . '/../' . ProjectCgv::BASE_PATH . $termsOfSale->getName());
        }

        $this->mailerManager->sendProjectTermsOfSale($termsOfSale);
    }
}
