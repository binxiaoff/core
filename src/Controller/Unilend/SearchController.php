<?php

namespace Unilend\Controller\Unilend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Service\Front\ProjectDisplayManager;
use Unilend\Entity\Projects;

class SearchController extends Controller
{
    /**
     * @Route("/search", name="search", methods={"POST"})
     *
     * @return Response
     */
    public function searchAction(Request $request): Response
    {
        return empty($request->request->get('search')) ? $this->redirectToRoute('faq-preteur') : $this->redirectToRoute('search_result', ['query' => urlencode($request->request->get('search'))]);
    }

    /**
     * @Route("/search/{query}", name="search_result", methods={"GET"})
     *
     * @param  string        $query
     * @param  UserInterface $client
     *
     * @return Response
     */
    public function resultAction(string $query, ?UserInterface $client): Response
    {
        $query   = filter_var(urldecode($query), FILTER_SANITIZE_STRING);
        $search  = $this->get('unilend.service.search_service');
        $results = $search->search($query);

        if (false === empty($results['projects'])) {
            if (null === $client) {
                unset($results['projects']);
            } else {
                $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
                $projectRepository     = $this->get('doctrine.orm.entity_manager')->getRepository(Projects::class);

                foreach ($results['projects'] as $index => $result) {
                    $project = $projectRepository->find($result['projectId']);

                    if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $client)) {
                        unset($results['projects'][$index]);
                    }
                }

                if (empty($results['projects'])) {
                    unset($results['projects']);
                }
            }
        }

        $template = [
            'query'   => $query,
            'results' => $results
        ];

        return $this->render('search/result.html.twig', $template);
    }
}
