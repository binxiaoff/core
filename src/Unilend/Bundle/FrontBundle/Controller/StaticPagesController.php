<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Unilend\Bundle\FrontBundle\Service\StaticContentManager;

class StaticPagesController extends Controller
{
    /**
     * @Route("/statistics", name="statistics")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function statisticsShowAction()
    {
        return $this->render('pages/static_pages/statistics.html.twig', array());
    }

    /**
     * @Route("/reviews", name="reviews")
     */
    public function reviewFooterShowAction()
    {
        return $this->render('partials/site/reviews.html.twig', array());
    }

    /**
     * @Route("/questions-frequentes-emprunteur", name="borrower_faq")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function faqBorrowerShowAction()
    {

    }

    /**
     * @Route("/le-guide-de-lemprunteur", name="borrower_guide")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function borrowerGuideShowAction()
    {

    }

    /**
     * @Route("/temoignages", name="borrower_testimonials")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function borrowerTestimonials()
    {

    }

    /**
     * @Route("/questions-frequentes", name="lender_faq")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function faqLenderShowAction()
    {

    }

    /**
     * @Route("/fiscalite", name="lender_fiscality")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function lenderFiscalityShowAction()
    {

    }

    /**
     * @Route("/qui-sommes-nous", name="about_us")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutUsShowAction()
    {
        return $this->render('pages/static_pages/about_us.html.twig', array());
    }

    /**
     * @Route("/comment-ca-marche", name="how_it_works")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function howItWorksShowAction()
    {

    }

    /**
     * @Route("/charte-de-deontologie", name="ethics")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ethicsShowAction()
    {

    }

    /**
     * @Route("/la-presse-en-parle", name="press")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pressShowAction()
    {

    }

    /**
     * @Route("/recrutement", name="recruitment")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recruitmentShowAction()
    {

    }
}
