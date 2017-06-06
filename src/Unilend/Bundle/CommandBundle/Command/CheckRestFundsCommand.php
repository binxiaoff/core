<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRestFundsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:project:rest_funds:check')
            ->setDescription('Find all projects that their funds are released at least 1 month ago but not totally transferred to the borrowers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projects      = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findPartiallyReleasedProjects(new \DateTime('1 month ago'));
        $adminHost     = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_admin');

        $projectsTexts = '<ul>';
        foreach ($projects as $project) {
            $projectsTexts .= '<li><a href="' . $adminHost . '/dossiers/edit/' . $project->getIdProject() . '">' . $project->getTitle() . ' (id : ' . $project->getIdProject() . ')</a></li>';
        }
        $projectsTexts .= '</ul>';

        if (0 < count($projects)) {
            $settings  = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse controle interne']);
            $variables = ['projects' => $projectsTexts];
            $message   = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-project-rest-funds', $variables);
            $message->setTo($settings->getValue());
            $this->getContainer()->get('mailer')->send($message);
        }
    }
}
