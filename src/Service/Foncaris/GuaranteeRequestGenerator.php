<?php

declare(strict_types=1);

namespace Unilend\Service\Foncaris;

use Exception;
use Knp\Snappy\Pdf;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Unilend\Entity\Project;
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Repository\ElementsRepository;
use Unilend\Repository\TreeElementsRepository;
use Unilend\Service\Document\AbstractDocumentGenerator;

class GuaranteeRequestGenerator extends AbstractDocumentGenerator
{
    private const FILE_PREFIX = 'demande-garantie';
    private const PATH        = 'guarantee-foncaris';

    /** @var Environment */
    private $twig;
    /** @var Pdf */
    private $snappy;
    /** @var string */
    private $staticUrl;
    /** @var string */
    private $publicDirectory;
    /** @var AcceptationLegalDocsRepository */
    private $acceptationLegalDocsRepository;
    /** @var TreeElementsRepository */
    private $treeElementsRepository;
    /** @var ElementsRepository */
    private $elementsRepository;

    /**
     * @param Filesystem                     $filesystem
     * @param string                         $documentRootDirectory
     * @param string                         $publicDirectory
     * @param Environment                    $twig
     * @param Pdf                            $snappy
     * @param Packages                       $assetsPackages
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param TreeElementsRepository         $treeElementsRepository
     * @param ElementsRepository             $elementsRepository
     */
    public function __construct(
        Filesystem $filesystem,
        string $documentRootDirectory,
        string $publicDirectory,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        TreeElementsRepository $treeElementsRepository,
        ElementsRepository $elementsRepository
    ) {
        $this->publicDirectory                = $publicDirectory;
        $this->twig                           = $twig;
        $this->snappy                         = $snappy;
        $this->staticUrl                      = $assetsPackages->getUrl('');
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->treeElementsRepository         = $treeElementsRepository;
        $this->elementsRepository             = $elementsRepository;

        $this->snappy->setBinary('/usr/local/bin/wkhtmltopdf');

        parent::__construct($filesystem, $documentRootDirectory);
    }

    /**
     * @param Project|object $project
     *
     * @throws Exception
     */
    public function generate(object $project): void
    {
        $this->checkObject($project);

        $content = $this->twig->render('/foncaris/pdf/request.html.twig', ['project' => $project]);

        $this->snappy->setOption('user-style-sheet', $this->publicDirectory . 'styles/default/pdf/style.css');
        $this->snappy->generateFromHtml($content, $this->getFilePath($project), [], true);
    }

    /**
     * @param Project|object $project
     *
     * @return string
     */
    protected function getFileName(object $project): string
    {
        $this->checkObject($project);

        return self::FILE_PREFIX . '-' . $project->getHash() . '.pdf';
    }

    /**
     * @param Project|object $project
     *
     * @return string
     */
    protected function getRelativeDirectory(object $project): string
    {
        $this->checkObject($project);

        return self::PATH . DIRECTORY_SEPARATOR . $project->getId();
    }

    /**
     * @param Project $project
     */
    private function checkObject(Project $project)
    {
        //nothing to do. The language structure (type hint) is used to check the object.
    }
}
