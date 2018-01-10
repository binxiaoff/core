<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Loader\ExistsLoaderInterface;

class ExceptionController extends Controller
{
    /**
     * @Route("/erreur404", name="error_404")
     *
     * @return Response
     */
    public function error404Action() : Response
    {
        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $translator = $this->get('translator');
        $pageTitle  = $translator->trans('error-page_404-page-title');
        $title      = $translator->trans('error-page_404-title');
        $details    = $translator->trans('error-page_404-details');

        return $this->render('exception/error.html.twig', ['errorPageTitle' => $pageTitle, 'errorTitle' => $title, 'errorDetails' => $details]);
    }

    /**
     * @param Request                   $request
     * @param FlattenException          $exception
     * @param DebugLoggerInterface|null $logger
     *
     * @return Response
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null) : Response
    {
        if ($logger instanceof LoggerInterface) {
            try {
                $dbHost = $this->get('database_connection')->fetchColumn('Select @@hostname');
                $logger->info('Current database host is ' . $dbHost);
            } catch (\Exception $exception) {
                $logger->error('Exception occurs when getting the database host name. Error message : ' . $exception->getMessage());
            }
        }

        $seoPage = $this->container->get('sonata.seo.page');
        $seoPage->addMeta('name', 'robots', 'noindex');

        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $showException  = $request->attributes->get('showException', $this->getParameter('kernel.debug'));

        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        } else {
            $code = 500;
        }

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
     * @param int $startObLevel
     *
     * @return string
     */
    private function getAndCleanOutputBuffering(int $startObLevel) : string
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
    private function findTemplate(Request $request, string $format, bool $showException) : string
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

    /**
     * @param string $template
     *
     * @return bool
     */
    private function templateExists(string $template) : bool
    {
        $template = (string) $template;

        $loader = $this->get('twig')->getLoader();
        if ($loader instanceof ExistsLoaderInterface || method_exists($loader, 'exists')) {
            return $loader->exists($template);
        }

        try {
            $loader->getSourceContext($template)->getCode();

            return true;
        } catch (\Twig_Error_Loader $e) {
        }

        return false;
    }
}
