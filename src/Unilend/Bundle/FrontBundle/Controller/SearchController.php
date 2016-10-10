<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\SearchService;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

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
        return $this->redirectToRoute('search_result', ['query' => urlencode($request->request->get('search'))]);
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
        /** @var SearchService $search */
        $search = $this->get('unilend.service.search_service');
        $query  = urldecode($query);

        /** @var BaseUser $user */
        $user = $this->getUser();

        $isFullyConnectedUser = ($user instanceof UserLender && $user->getClientStatus() == \clients_status::VALIDATED || $user instanceof UserBorrower);

        $template = [
            'query'   => $query,
            'results' => $search->search($query, $isFullyConnectedUser)
        ];

        return $this->render('pages/search.html.twig', $template);
    }
}
