<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Command;

use DateInterval;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\StaffRepository;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Repository\ReservationStatusRepository;

class ArchiveReservationExceedingDurationCommand extends Command
{
    private const BATCH_SIZE = 10;

    protected static $defaultName               = 'kls:reservation:archive-exceeding-duration';
    protected static string $defaultDescription = 'Archive reservations that exceed the validity duration';

    private StaffRepository $staffRepository;
    private ReservationStatusRepository $reservationStatusRepository;

    public function __construct(StaffRepository $staffRepository, ReservationStatusRepository $reservationStatusRepository)
    {
        parent::__construct();

        $this->staffRepository             = $staffRepository;
        $this->reservationStatusRepository = $reservationStatusRepository;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * @throws ORMException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Staff $adminStaff */
        $adminStaff = $this->staffRepository->find(1);

        $acceptedByManagerReservationStatuses = $this->reservationStatusRepository->findBy([
            'status' => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
        ]);

        $i = 1;

        foreach ($acceptedByManagerReservationStatuses as $acceptedByManagerReservationStatus) {
            $reservation = $acceptedByManagerReservationStatus->getReservation();

            if ($acceptedByManagerReservationStatus !== $reservation->getCurrentStatus()) {
                continue;
            }

            $reservationDuration = $reservation->getProgram()->getReservationDuration();

            if (null === $reservationDuration) {
                // besoin de warning ici ?
                continue;
            }

            $dateInterval         = new DateInterval('P' . $reservationDuration . 'M');
            $reservationLimitDate = $acceptedByManagerReservationStatus->getAdded()->add($dateInterval);

            if ($acceptedByManagerReservationStatus->getAdded() < $reservationLimitDate) {
                continue;
            }

            $archivedReservationStatus = new ReservationStatus($reservation, ReservationStatus::STATUS_ARCHIVED, $adminStaff);
            $this->reservationStatusRepository->persist($archivedReservationStatus);

            if (0 === $i % self::BATCH_SIZE) {
                $this->reservationStatusRepository->flush();
            }

            ++$i;
        }

        $this->reservationStatusRepository->flush();

        return Command::SUCCESS;
    }
}
