<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 25/08/2016
 * Time: 14:51
 */

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExceptionController extends Controller
{
    /**
     * @Route("/erreur404", name="error_404")
     *
     * @return array
     */
    public function error404Action()
    {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        return $this->render('pages/static_pages/error.html.twig', [], $response);
    }
}