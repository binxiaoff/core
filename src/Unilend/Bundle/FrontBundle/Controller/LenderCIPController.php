<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LenderCIPController extends Controller
{
    /**
     * @Route("/conseil-cip", name="cip_index")
     * @Template("pages/lender_cip/index.html.twig")
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/conseil-cip/oui-non", name="cip_boolean_question")
     * @Template("pages/lender_cip/boolean.html.twig")
     *
     * @return array
     */
    public function booleanQuestionAction()
    {
        return [];
    }

    /**
     * @Route("/conseil-cip/valeur", name="cip_value_question")
     * @Template("pages/lender_cip/value.html.twig")
     *
     * @return array
     */
    public function valueQuestionAction()
    {
        return [];
    }

    /**
     * @Route("/conseil-cip/conseil", name="cip_advice")
     * @Template("pages/lender_cip/advice.html.twig")
     *
     * @return array
     */
    public function adviceAction()
    {
        return [];
    }
}
