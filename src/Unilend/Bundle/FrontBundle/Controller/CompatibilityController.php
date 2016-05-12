<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompatibilityController extends Controller
{
    public function dispatchAction()
    {
        throw new NotFoundHttpException();
    }
}