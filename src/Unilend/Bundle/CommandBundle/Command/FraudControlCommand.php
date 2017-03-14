<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;

class FraudControlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('unilend:client:atypical_operation:check')
            ->setDescription('Detect atypical client operations based on defined rules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em                   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $vigilanceRuleManager = $this->getContainer()->get('unilend.service.vigilance_rule_manager');
        $stopWatch            = $this->getContainer()->get('debug.stopwatch');
        $logger               = $this->getContainer()->get('logger');
        /** @var VigilanceRule[] $vigilanceRules */
        $vigilanceRules = $em->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findAll();

        foreach ($vigilanceRules as $rule) {
            $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_rule' => $rule->getId()];
            $stopWatch->start(__FUNCTION__ . $rule->getLabel());
            try {
                $vigilanceRuleManager->checkRule($rule);
            } catch (\Exception $exception) {
                $logger->error('Could not process the vigilance rule: ' . $rule->getLabel() . ' - Error: ' . $exception->getMessage(), $logContext);
            }

            if ($stopWatch->isStarted(__FUNCTION__ . $rule->getLabel())) {
                $logger->info('Total execution time for rule: ' . $rule->getLabel() . ': ' . $stopWatch->stop(__FUNCTION__ . $rule->getLabel())->getDuration() / 1000 . ' seconds', $logContext);
            }
        }
    }
}
