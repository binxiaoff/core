<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;

class DumpedDataFixture extends AbstractFixtures
{
    private string $projectDirectory;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param string                $projectDirectory
     */
    public function __construct(TokenStorageInterface $tokenStorage, string $projectDirectory)
    {
        parent::__construct($tokenStorage);
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->in($this->projectDirectory . '/dump')->files()->name('*.sql');
        $files = iterator_to_array($finder);

        foreach ($files as $file) {
            $content   = $file->getContents();
            $statement = $manager->getConnection()->prepare($content);
            $statement->execute();
        }
    }
}
