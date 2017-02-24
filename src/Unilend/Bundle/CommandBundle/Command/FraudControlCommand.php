<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Repository\OperationRepository;

class FraudControlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('unilend:fraud_control')
            ->setDescription('Detect atypical client operations based on defined rules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $vigilanceRuleManager = $this->getContainer()->get('unilend.service.vigilance_rule_manager');
        $vigilanceRules      = $em->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findAll();

        foreach ($vigilanceRules as $rule) {
            $vigilanceRuleManager->checkRule($rule);
        }
    }
}