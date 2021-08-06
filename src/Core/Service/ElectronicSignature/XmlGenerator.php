<?php

declare(strict_types=1);

namespace Unilend\Core\Service\ElectronicSignature;

use Exception;
use League\Flysystem\FilesystemException;
use SimpleXMLElement;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Core\Entity\FileVersionSignature;
use Unilend\Core\Service\FileSystem\FileSystemHelper;

class XmlGenerator
{
    private const KLS_CODE_ENTITY = '14000';

    private FileSystemHelper $fileSystemHelper;
    private RouterInterface $router;

    public function __construct(FileSystemHelper $fileSystemHelper, RouterInterface $router)
    {
        $this->fileSystemHelper = $fileSystemHelper;
        $this->router           = $router;
    }

    /**
     * @throws Exception
     * @throws FilesystemException
     */
    public function generate(FileVersionSignature $fileVersionSignature): string
    {
        $signatory   = $fileVersionSignature->getSignatory();
        $fileVersion = $fileVersionSignature->getFileVersion();
        if (null === $fileVersion) {
            throw new \RuntimeException(\sprintf('No file version found for signature for %d', $fileVersionSignature->getId()));
        }
        $fileSystem        = $this->fileSystemHelper->getFileSystem($fileVersion);
        $fileBase64Content = \base64_encode($fileSystem->read($fileVersion->getPath()));

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
        $parametresDeSignature->addChild('NUMCRP', self::KLS_CODE_ENTITY);
        $parametresDeSignature->addChild('NUMCRA', self::KLS_CODE_ENTITY);
        $parametresDeSignature->addChild('NUMCRT', self::KLS_CODE_ENTITY);
        $parametresDeSignature->addChild('IDPART', '1234567890123'); // static value, don't ask why
        $parametresDeSignature->addChild('IDPROT', 'CIB01');
        $parametresDeSignature->addChild('NUMARCH', (string) $fileVersionSignature->getId());
        $parametresDeSignature->addChild('OMEXML', $fileBase64Content);
        $parametresDeSignature->addChild('IDNISE', '001');
        $parametresDeSignature->addChild('URLRET', $this->router->generate(
            'file-signature-result',
            ['fileSignatureId' => $fileVersionSignature->getPublicId()],
            RouterInterface::ABSOLUTE_URL
        ));

        $parameter = $parametresDeSignature->addChild('PARRET')->addChild('ParamsRetour')->addChild('Parametre');
        $parameter->addAttribute('Nom', 'fileSignatureId');
        $parameter->addAttribute('Valeur', (string) $fileVersionSignature->getId());

        $parametresDeSignature->addChild('TOPARCHIVAGE', 'O'); // last signature ? O = yes, N = no
        $parametresDeSignature->addChild('IDTECHCOMM', '170');

        $donneeEntreprise = $parametresDeSignature->addChild('DONNEEENTREPRISE');
        $donneeEntreprise->addChild('INDICATEURENTREPRISE', 'O');
        $donneeEntreprise->addChild('LIBELLEENTREPRISE', $signatory->getCompany()->getDisplayName());

        $donneePP = $parametresDeSignature->addChild('DONNEEPP');
        $donneePP->addChild('CDTICI', '1'); // todo: ask for the values for other titles
        $donneePP->addChild('LNPRUS', $signatory->getUser()->getFirstName());
        $donneePP->addChild('LNPATR', $signatory->getUser()->getLastName());

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
}
