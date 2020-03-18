<?php

declare(strict_types=1);

namespace Unilend\Service\Psn;

use Exception;
use SimpleXMLElement;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Entity\AttachmentSignature;
use Unilend\Entity\Staff;
use Unilend\Service\FileSystem\FileSystemHelper;

class XmlGenerator
{
    private const KLS_CODE_ENTITY = '14000';
    /**
     * @var FileSystemHelper
     */
    private $fileSystemHelper;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param FileSystemHelper $fileSystemHelper
     * @param RouterInterface  $router
     */
    public function __construct(FileSystemHelper $fileSystemHelper, RouterInterface $router)
    {
        $this->fileSystemHelper = $fileSystemHelper;
        $this->router           = $router;
    }

    /**
     * @param AttachmentSignature $attachmentSignature
     *
     * @throws Exception
     *
     * @return string
     */
    public function generate(AttachmentSignature $attachmentSignature): string
    {
        $requester = $attachmentSignature->getAttachment()->getAddedBy();
        $signatory = $attachmentSignature->getSignatory();

        $this->check($requester, $signatory);

        $attachment = $attachmentSignature->getAttachment();
        $fileSystem = $this->fileSystemHelper->getFileSystemForClass($attachmentSignature->getAttachment());

        $fileBase64Content = base64_encode($fileSystem->read($attachment->getPath()));

        $xml = new SimpleXMLElement('<Donnees/>');
        $xml->addChild('InfoControle')->addAttribute('Date', (new \DateTime())->format('Y-m-d H:i:s \G\M\TP'));

        $appelpm      = $xml->addChild('APPELPM');
        $paramsentree = $appelpm->addChild('PARAMSENTREE');

        $paramsentree->addChild('BLOC')->addAttribute('nom', 'O1SN850');
        $pm = $paramsentree->addChild('PM');
        $pm->addAttribute('nom', 'SignatureEntite');
        $pm->addAttribute('version', '4');

        // The Context ADSU that PSN doesn't parse. So this part stay constant.
        $contextepu = $paramsentree->addChild('CONTEXTEPU');
        $contextepu->addAttribute('IDAGEN', 'MP01210');
        $contextepu->addAttribute('IDELSTCO', '42510');
        $contextepu->addAttribute('IDPOFO', 'POFO010203');
        $contextepu->addAttribute('IDPTVE', 'PostePC973663');
        $contextepu->addAttribute('IDSESSIONAPP', '9876543210123');
        $contextepu->addAttribute('IDSESSIONPU', '1005200314h23m16sCREATEPP04958231005200314h23m16sCREATEPP0495823');
        $contextepu->addAttribute('IDSESSIONSAG', '42510-PC973663-POFO010203*******.ServeurParam123');
        $contextepu->addAttribute('NOMPU', 'CREATION DE PARTENAIRE PERSONNE*');
        $contextepu->addAttribute('NUMCR', self::KLS_CODE_ENTITY);

        $parametresDeSignature = $appelpm->addChild('PARAMETRESdePM')->addChild('PARAMETRESdeSIGNATURE');
        $parametresDeSignature->addChild('NUMCRP', $signatory->getCompany()->getEntityCode());
        $parametresDeSignature->addChild('NUMCRA', $requester->getCompany()->getEntityCode());
        $parametresDeSignature->addChild('NUMCRT', self::KLS_CODE_ENTITY); // Optional
        $parametresDeSignature->addChild('IDPART', '1234567890123'); // unconfirmed value
        $parametresDeSignature->addChild('IDPROT', 'CIB01');
        $parametresDeSignature->addChild('NUMARCH', $attachmentSignature->getPublicId());
        $parametresDeSignature->addChild('OMEXML', $fileBase64Content);
        $parametresDeSignature->addChild('IDNISE', '001');
        $parametresDeSignature->addChild('URLRET', $this->router->generate(
            'front_arrangement_document_sign_result',
            ['documentSignatureId' => $attachmentSignature->getPublicId()],
            RouterInterface::ABSOLUTE_URL
        ));
        $parametresDeSignature->addChild('PARRET')->addChild('ParamsRetour')->addChild('Parametre');
        $parametresDeSignature->addChild('TOPARCHIVAGE', 'N'); // unconfirmed value
        $parametresDeSignature->addChild('IDTECHCOMM', '170');

        $donneeEntreprise = $parametresDeSignature->addChild('DONNEEENTREPRISE');
        $donneeEntreprise->addChild('INDICATEURENTREPRISE', 'O'); // unconfirmed value
        $donneeEntreprise->addChild('LIBELLEENTREPRISE', $signatory->getCompany()->getName());

        $donneePP = $parametresDeSignature->addChild('DONNEEPP');
        $donneePP->addChild('CDTICI', '1'); // unconfirmed value
        $donneePP->addChild('LNPRUS', $signatory->getClient()->getFirstName());
        $donneePP->addChild('LNPATR', $signatory->getClient()->getLastName());

        $listeVisuelsSignature = $parametresDeSignature->addChild('LISTEVISUELSSIGNATURE');

        $visuelSignatureClient = $listeVisuelsSignature->addChild('VISUELSIGNATURE');
        $visuelSignatureClient->addChild('TYPESIGNATAIRE', 'client');
        $visuelSignatureClient->addChild('PAGE', '1');
        $visuelSignatureClient->addChild('POSITIONXCOINHAUTGAUCHE', '110');
        $visuelSignatureClient->addChild('POSITIONYCOINHAUTGAUCHE', '57');
        $visuelSignatureClient->addChild('HAUTEUR', '25');
        $visuelSignatureClient->addChild('LARGEUR', '55');

        $visuelSignatureEntity = $listeVisuelsSignature->addChild('VISUELSIGNATURE');
        $visuelSignatureEntity->addChild('TYPESIGNATAIRE', 'entite');
        $visuelSignatureEntity->addChild('PAGE', '1');
        $visuelSignatureEntity->addChild('POSITIONXCOINHAUTGAUCHE', '35');
        $visuelSignatureEntity->addChild('POSITIONYCOINHAUTGAUCHE', '57');
        $visuelSignatureEntity->addChild('HAUTEUR', '25');
        $visuelSignatureEntity->addChild('LARGEUR', '55');

        return $xml->asXML();
    }

    /**
     * @param Staff $requester
     * @param Staff $signatory
     */
    private function check(Staff $requester, Staff $signatory): void
    {
        if (!$requester->getCompany()->getEntityCode()) {
            throw new \RuntimeException('The requester company has no entity code');
        }

        if (!$signatory->getCompany()->getEntityCode()) {
            throw new \RuntimeException('The signatory company has no entity code');
        }
    }
}
