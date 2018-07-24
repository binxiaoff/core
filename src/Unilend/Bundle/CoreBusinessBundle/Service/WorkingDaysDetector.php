<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Yasumi\Provider\France;
use Yasumi\Yasumi;

class WorkingDaysDetector
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \DateTime $startDate
     * @param int       $workingDays
     *
     * @return \DateTimeInterface
     */
    public function nextWorkingDay(\DateTime $startDate, int $workingDays = 1): \DateTimeInterface
    {
        try {
            return Yasumi::nextWorkingDay(France::class, $startDate, $workingDays);
        } catch (\Exception $exception) {
            $this->logger->critical('Failed to get the next working day. The alternative solution is applied. The generation of project schedule may be wrong. Error : ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'trace'    => $exception->getTraceAsString()
            ]);
            return $startDate->modify($workingDays * 2 . 'day');
        }
    }

    /**
     * @param \DateTime $startDate
     * @param int       $workingDays
     *
     * @return \DateTimeInterface
     */
    public function previousWorkingDay(\DateTime $startDate, int $workingDays = 1): \DateTimeInterface
    {
        try {
            return Yasumi::prevWorkingDay(France::class, $startDate, $workingDays);
        } catch (\Exception $exception) {
            $this->logger->critical('Failed to get the next working day. The alternative solution is applied. The generation of project schedule may be wrong. Error : ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'trace'    => $exception->getTraceAsString()
            ]);
            return $startDate->modify(-1 * $workingDays * 2 . 'day');
        }
    }
}
