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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        var_dump($em->getRepository('UnilendCoreBusinessBundle:Wallet')->getInactiveLenderWalletOnPeriod(new \DateTime('45 days ago'), 5000));
        var_dump($em->getRepository('UnilendCoreBusinessBundle:Clients')->getClientsWithMultipleBankAccountsOnPeriod(new \DateTime('12 months ago')));
        die;
        $vigilanceRules = $em->getRepository('UnilendCoreBusinessBundle:VigilanceRule')->findAll();
        $vigilanceRuleDetail = $em->getRepository('UnilendCoreBusinessBundle:VigilanceRuleDetail');

        foreach ($vigilanceRules as $rule) {
            $ruleDetails = $vigilanceRuleDetail->findBy(['rule' => $rule]);

            foreach ($ruleDetails as $ruleDetail) {

                switch ($ruleDetail->getActionLabel()) {
                    case 'money_deposit':
                        /** @var OperationRepository $operation */
                        $operation = $em->getRepository('UnilendCoreBusinessBundle:Operation');

                }
            }
        }
    }
}