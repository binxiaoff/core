<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompatibilityController extends Controller
{
    /**
     * @param $exception
     * @param $logger
     * @throws
     */
    public function dispatchAction(FlattenException $exception, LoggerInterface $logger)
    {
        $class = $exception->getClass();
        if ($class == 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException') {
            $logger->info('Got:' . $exception->getMessage() . ' And call legacy dispatcher.');
        }

        throw new $class;
    }
}
