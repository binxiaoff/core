<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use Psr\Log\LoggerInterface;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\Console\{Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle};

class SecurityCheckNotificationCommand extends Command
{
    /** @var string */
    private string $composerlockPath;
    /** @var SecurityChecker */
    private SecurityChecker $securityChecker;
    /** @var LoggerInterface */
    private LoggerInterface $securityLogger;

    /**
     * @param string          $projectDirectory
     * @param SecurityChecker $securityChecker
     * @param LoggerInterface $securityLogger
     */
    public function __construct(string $projectDirectory, SecurityChecker $securityChecker, LoggerInterface $securityLogger)
    {
        parent::__construct();
        $this->composerlockPath = $projectDirectory . DIRECTORY_SEPARATOR . 'composer.lock';
        $this->securityChecker  = $securityChecker;
        $this->securityLogger   = $securityLogger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kls:security:check')
            ->setDescription('Check known security issues in the packages managed by Composer using SensioLabs Security Advisories Checker')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io     = new SymfonyStyle($input, $output);
        $result = $this->securityChecker->check($this->composerlockPath);

        if (0 === $result->count()) {
            $io->success('No vulnerability detected');

            return 0;
        }

        $lockFile     = file_get_contents($this->composerlockPath);
        $composerLock = json_decode($lockFile, true, 512, JSON_THROW_ON_ERROR);
        $testedHash   = $composerLock['hash'];

        $vulnerabilities = json_decode((string) $result, true, 512, JSON_THROW_ON_ERROR);

        $content = '';

        foreach ($vulnerabilities as $package => $vulnerability) {
            $content .= "{$package} ({$vulnerability['version']})" . PHP_EOL;
            foreach ($vulnerability['advisories'] as $advisory) {
                $cve   = $advisory['cve'];
                $title = trim(str_replace($cve, '', $advisory['title']), ' \t\n\r\0\x0B:');
                $content .= "\t•{$title}" . ($cve ? " ({$cve})" : '') . PHP_EOL;
            }
        }

        $this->securityLogger->critical(
            'Vulnerabilities detected in current composer.lock.',
            [
                'hash'            => $testedHash,
                'vulnerabilities' => $content,
            ]
        );

        $io->error('Vulnerabilities detected. See Slack or email for more details.');

        return 0;
    }
}
