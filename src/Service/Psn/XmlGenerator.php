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

        $contextepu = $paramsentree->addChild('CONTEXTEPU');
        $contextepu->addAttribute('IDAGEN', 'MP01210'); // unconfirmed value
        $contextepu->addAttribute('IDELSTCO', '42510'); // unconfirmed value
        $contextepu->addAttribute('IDPOFO', 'POFO010203'); // unconfirmed value
        $contextepu->addAttribute('IDPTVE', 'PostePC973663'); // unconfirmed value
        $contextepu->addAttribute('IDSESSIONAPP', '9876543210123'); // unconfirmed value
        $contextepu->addAttribute('IDSESSIONPU', '1005200314h23m16sCREATEPP04958231005200314h23m16sCREATEPP0495823'); // unconfirmed value
        $contextepu->addAttribute('IDSESSIONSAG', '42510-PC973663-POFO010203*******.ServeurParam123'); // unconfirmed value
        $contextepu->addAttribute('NOMPU', 'CREATION DE PARTENAIRE PERSONNE*'); // unconfirmed value
        $contextepu->addAttribute('NUMCR', self::KLS_CODE_ENTITY); // unconfirmed value

        $parametresdesignature = $appelpm->addChild('PARAMETRESdePM')->addChild('PARAMETRESdeSIGNATURE');
        $parametresdesignature->addChild('NUMCRP', $signatory->getCompany()->getEntityCode());
        $parametresdesignature->addChild('NUMCRA', $requester->getCompany()->getEntityCode());
        //$parametresdesignature->addChild('NUMCRT', self::KLS_CODE_ENTITY); // Optional
        $parametresdesignature->addChild('IDPART', '1234567890123'); // unconfirmed value
        $parametresdesignature->addChild('IDPROT', 'CIB01'); // unconfirmed value
        $parametresdesignature->addChild('NUMARCH', $attachmentSignature->getPublicId());
        $parametresdesignature->addChild('OMEXML', $fileBase64Content);
        $parametresdesignature->addChild('IDNISE', '001');
        $parametresdesignature->addChild('URLRET', $this->router->generate(
            'front_arrangement_document_sign_result',
            ['documentSignatureId' => $attachmentSignature->getPublicId()],
            RouterInterface::ABSOLUTE_URL
        ));
        $parametresdesignature->addChild('PARRET')->addChild('ParamsRetour')->addChild('Parametre');
        $parametresdesignature->addChild('TOPARCHIVAGE', '0'); // unconfirmed value
        $parametresdesignature->addChild('IDTECHCOMM', '170');

        $donneeentreprise = $parametresdesignature->addChild('DONNEEENTREPRISE');
        $donneeentreprise->addChild('INDICATEURENTREPRISE', $signatory->getCompany()->getEntityCode()); // unconfirmed value
        $donneeentreprise->addChild('LIBELLEENTREPRISE', $signatory->getCompany()->getName());

        $visuelsignature = $parametresdesignature->addChild('LISTEVISUELSSIGNATURE')->addChild('VISUELSIGNATURE');
        $visuelsignature->addChild('TYPESIGNATAIRE', 'ENTITE');
        $visuelsignature->addChild('PAGE', '1');
        $visuelsignature->addChild('POSITIONXCOINHAUTGAUCHE', '35');
        $visuelsignature->addChild('POSITIONYCOINHAUTGAUCHE', '57');
        $visuelsignature->addChild('HAUTEUR', '25');
        $visuelsignature->addChild('LARGEUR', '55');

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
