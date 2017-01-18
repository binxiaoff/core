<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ExceptionController extends Controller
{
    /**
     * @Route("/erreur404", name="error_404")
     *
     * @return Response
     */
    public function error404Action()
    {
        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $translator = $this->get('translator');
        $pageTitle  = $translator->trans('error-page_404-page-title');
        $title      = $translator->trans('error-page_404-title');
        $details    = $translator->trans('error-page_404-details');

        return $this->render('exception/error.html.twig', ['errorPageTitle' => $pageTitle, 'errorTitle' => $title, 'errorDetails' => $details]);
    }

    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $dbHost = $this->get('database_connection')->fetchColumn('Select @@hostname');

        if ($logger instanceof LoggerInterface) {
            try {
                $logger->info('Current database host is ' . $dbHost);
            } catch (\Exception $exception) {
                $logger->error('Exception occurs when getting the database host name. Error message : ' . $exception->getMessage());
            }
        }

        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $showException  = $request->attributes->get('showException', $this->getParameter('kernel.debug'));

        $code = $exception->getStatusCode();

        $translator = $this->get('translator');
        $pageTitle  = $translator->trans('error-page_general-page-title');
        $title      = $translator->trans('error-page_general-title');
        $details    = $translator->trans('error-page_general-details');

        return $this->render(
            (string)$this->findTemplate($request, $request->getRequestFormat(), $showException),
            [
                'status_code'    => $code,
                'status_text'    => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception'      => $exception,
                'logger'         => $logger,
                'currentContent' => $currentContent,
                'errorPageTitle' => $pageTitle,
                'errorTitle'     => $title,
                'errorDetails'   => $details
            ]
        );
    }

    /**
     * @Route("/cf-error/error5xx", name="cloudflare_5xx")
     *
     * @return Response
     */
    public function error5xxAction()
    {
        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $translator = $this->get('translator');
        $pageTitle  = $translator->trans('error-page_general-page-title');
        $title      = $translator->trans('error-page_general-title');
        $details    = $translator->trans('error-page_general-details');

        return $this->render('exception/error.html.twig', [
            'errorPageTitle' => $pageTitle,
            'errorTitle'     => $title,
            'errorDetails'   => $details,
            'hideButtons'    => true
        ]);
    }

    /**
     * @Route("/cf-error/block", name="cloudflare_block")
     *
     * @return Response
     */
    public function blockAction()
    {
        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $translator = $this->get('translator');
        $pageTitle  = $translator->trans('error-page_blocked-page-title');
        $title      = $translator->trans('error-page_blocked-title');
        $details    = $translator->trans('error-page_blocked-details');

        return $this->render('exception/error.html.twig', [
            'errorPageTitle' => $pageTitle,
            'errorTitle'     => $title,
            'errorDetails'   => $details,
            'hideButtons'    => true
        ]);
    }

    /**
     * @Route("/cf-error/challenge", name="cloudflare_challenge")
     *
     * @return Response
     */
    public function challengeAction()
    {
        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $translator = $this->get('translator');
        $pageTitle  = $translator->trans('error-page_challenge-page-title');
        $title      = $translator->trans('error-page_challenge-title');
        $details    = $translator->trans('error-page_challenge-details');

        return $this->render('exception/error.html.twig', [
            'errorPageTitle' => $pageTitle,
            'errorTitle'     => $title,
            'errorDetails'   => $details,
            'hideButtons'    => true
        ]);
    }

    /**
     * @param int $startObLevel
     *
     * @return string
     */
    private function getAndCleanOutputBuffering($startObLevel)
    {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }

    /**
     * @param Request $request
     * @param string  $format
     * @param bool    $showException
     *
     * @return string
     */
    private function findTemplate(Request $request, $format, $showException)
    {
        $name = $showException ? 'exception' : 'error';
        if ($showException && 'html' == $format) {
            $name = 'exception_full';
        }

        if (! $showException) {
            $template = sprintf('exception/%s.%s.twig', $name, $format);
            if ($this->templateExists($template)) {
                return $template;
            }
        }

        $request->setRequestFormat('html');

        return sprintf('@Twig/Exception/%s.html.twig', $showException ? 'exception_full' : $name);
    }

    // to be removed when the minimum required version of Twig is >= 3.0
    private function templateExists($template)
    {
        $template = (string) $template;

        $loader = $this->get('twig')->getLoader();
        if ($loader instanceof \Twig_ExistsLoaderInterface) {
            return $loader->exists($template);
        }

        try {
            $loader->getSource($template);

            return true;
        } catch (\Twig_Error_Loader $e) {
        }

        return false;
    }
}
