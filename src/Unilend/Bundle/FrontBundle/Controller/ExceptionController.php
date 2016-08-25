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
        $response    = new Response('', Response::HTTP_NOT_FOUND);
        $translator  = $this->get('translator');
        $title       = $translator->trans('error-page_404-title');
        $details = $translator->trans('error-page_404-details');
        return $this->render('pages/static_pages/error.html.twig', ['errorTitle' => $title, 'errorDetails' => $details], $response);
    }
}