<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;

class SearchController extends Controller
{
    /**
     * @Route("/search", name="search")
     * @Method({"POST"})
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        return empty($request->request->get('search')) ? $this->redirectToRoute('faq-preteur') : $this->redirectToRoute('search_result', ['query' => urlencode($request->request->get('search'))]);
    }

    /**
     * @Route("/search/{query}", name="search_result")
     * @Method({"GET"})
     *
     * @param  string $query
     * @return Response
     */
    public function resultAction($query)
    {
        $query   = filter_var(urldecode($query), FILTER_SANITIZE_STRING);
        $search  = $this->get('unilend.service.search_service');
        $results = $search->search($query);

        if (false === empty($results['projects'])) {
            if (null === $this->getUser()) {
                unset($results['projects']);
            } else {
                $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
                $projectRepository     = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Projects');

                foreach ($results['projects'] as $index => $result) {
                    $project = $projectRepository->find($result['projectId']);

                    if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $this->getUser())) {
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

        return $this->render('pages/search.html.twig', $template);
    }
}
