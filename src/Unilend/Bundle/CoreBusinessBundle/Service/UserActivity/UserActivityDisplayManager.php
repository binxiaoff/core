<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\UserActivity;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class UserActivityDisplayManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->logger        = $logger;
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    public function getLoginHistory(Clients $client): array
    {
        $result = [];
        try {
            $loginHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsHistory')
                ->getRecentLoginHistoryAndDevices($client);

            foreach ($loginHistory as $login) {
                $result[] = [
                    'deviceType'  => $this->getDeviceType($login['device_type']),
                    'deviceModel' => $login['device_model'],
                    'city'        => $login['city'],
                    'country'     => $login['fr'],
                    'browserName' => $login['browser_name'],
                    'date'        => $this->getLoginTimeSentence(new \DateTime($login['added']), $client->getLastlogin())
                ];
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get client history login. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        return $result;
    }

    /**
     * @param string $deviceType
     *
     * @return string
     */
    private function getDeviceType(string $deviceType): string
    {
        if (1 === preg_match('/mobile|phone/i', $deviceType)) {
            return 'mobile';
        } elseif (1 === preg_match('/tablet/i', $deviceType)) {
            return 'tablet';
        } elseif (1 === preg_match('/desktop/i', $deviceType)) {
            return 'desktop';
        }

        $this->logger->warning('Unable to detect either the device is "mobile", "tablet" or "desktop", returning default "desktop". Using needle: "' . $deviceType . '"', [
            'device_type' => $deviceType,
            'class'       => __CLASS__,
            'function'    => __FUNCTION__
        ]);

        return 'desktop';
    }

    /**
     * @param \DateTime $loginDate
     * @param \DateTime $lastLoginDate
     *
     * @return string
     */
    private function getLoginTimeSentence(\DateTime $loginDate, \DateTime $lastLoginDate): string
    {
        $interval   = $loginDate->diff($lastLoginDate);

        if (false === $interval && $loginDate instanceof \DateTime) {
            return $loginDate->format('d/m/Y H:i');
        }

        if ($lastLoginDate < $loginDate) {
            $minutes = 0;
        } elseif (0 === $interval->days && 0 === $interval->h) {
            $minutes = $interval->i;
        } elseif (0 === $interval->days && 0 < $interval->h) {
            $minutes = $interval->i + 60 * $interval->h;
        } elseif (1 === $interval->days) {
            $minutes = $interval->i + 60 * ($interval->h + 24 * $interval->d);
        } else {
            return $loginDate->format('d/m/Y H:i');
        }

        return $this->translator->transChoice('lender-profile_security-activity-and-devices-login-time', $minutes, ['%minutes%' => $minutes, '%hours%' => $interval->h]);
    }
}
