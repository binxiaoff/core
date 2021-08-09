<?php

declare(strict_types=1);

namespace KLS\Core\Command;

use Enlightn\SecurityChecker\SecurityChecker;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SecurityCheckCommand extends Command
{
    private string $composerlockPath;

    private LoggerInterface $securityLogger;

    private SecurityChecker $securityChecker;

    public function __construct(string $projectDirectory, LoggerInterface $securityLogger)
    {
        parent::__construct();
        $this->composerlockPath = $projectDirectory . DIRECTORY_SEPARATOR . 'composer.lock';
        $this->securityLogger   = $securityLogger;
        $this->securityChecker  = new SecurityChecker();
    }

    protected function configure()
    {
        $this
            ->setName('kls:security:check')
            ->setDescription('Check known security issues in the packages managed by Composer')
            ->setHelp(<<<'HELP'
                The command is successful if no installed packages (at their currently installed version) 
                are present in the security advisories database <href=https://github.com/FriendsOfPHP/security-advisories>PHP Security Advisories Database</>

                It uses <href=https://github.com/enlightn/security-checker>Enlightn Security Checker</>
                HELP
            )
        ;
    }

    /**
     * @throws JsonException
     * @throws GuzzleException
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io     = new SymfonyStyle($input, $output);
        $result = $this->securityChecker->check($this->composerlockPath);

        if (0 === \count($result)) {
            $io->success('No vulnerability detected');

            return 0;
        }

        $lockFile     = \file_get_contents($this->composerlockPath);
        $composerLock = \json_decode($lockFile, true, 512, JSON_THROW_ON_ERROR);
        $testedHash   = $composerLock['content-hash'] ?? $composerLock['hash'];

        $content = '';

        foreach ($result as $package => $vulnerability) {
            $content .= "{$package} ({$vulnerability['version']}) ({$vulnerability['time']})" . PHP_EOL;
            foreach ($vulnerability['advisories'] as $advisory) {
                $content .= "\t";
                $content .= \implode(' ', \array_filter(
                    [$advisory['title'], $advisory['cve'] ? '(' . $advisory['cve'] . ')' : null, $advisory['link'] ? '(' . $advisory['link'] . ')' : null]
                ));
                $content .= PHP_EOL;
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
