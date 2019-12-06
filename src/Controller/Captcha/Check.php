<?php

declare(strict_types=1);

namespace Unilend\Controller\Captcha;

use Symfony\Component\HttpFoundation\JsonResponse;
use Unilend\Entity\Request\CaptchaCheck;
use Unilend\Service\GoogleRecaptchaManager;

class Check
{
    /**
     * @var GoogleRecaptchaManager
     */
    private $googleRecaptchaManager;

    /**
     * @param GoogleRecaptchaManager $googleRecaptchaManager
     */
    public function __construct(GoogleRecaptchaManager $googleRecaptchaManager)
    {
        $this->googleRecaptchaManager = $googleRecaptchaManager;
    }

    /**
     * @param CaptchaCheck $data
     *
     * @return JsonResponse
     */
    public function __invoke(CaptchaCheck $data): JsonResponse
    {
        return $this->googleRecaptchaManager->isValid($data->captchaValue) ? new JsonResponse('OK') : new JsonResponse('KO', 400);
    }
}
