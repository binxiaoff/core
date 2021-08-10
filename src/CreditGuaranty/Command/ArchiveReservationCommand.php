<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Command;

use DateTime;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Entity\Staff;
use KLS\Core\Repository\StaffRepository;
use KLS\CreditGuaranty\Entity\Reservation;
use KLS\CreditGuaranty\Entity\ReservationStatus;
use KLS\CreditGuaranty\Repository\ReservationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveReservationCommand extends Command
{
    private const BATCH_SIZE = 10;

    protected static $defaultName = 'kls:reservation:exceeding-duration:archive';

    private StaffRepository $staffRepository;
    private ReservationRepository $reservationRepository;

    public function __construct(StaffRepository $staffRepository, ReservationRepository $reservationRepository)
    {
        parent::__construct();

        $this->staffRepository       = $staffRepository;
        $this->reservationRepository = $reservationRepository;
    }

    protected function configure(): void
    {
        $this->setDescription('Archive reservations that exceed the validity duration');
    }

    /**
     * @throws ORMException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Staff $adminStaff */
        $adminStaff = $this->staffRepository->find(Staff::ID_ADMIN);

        $i = 1;

        foreach ($this->reservationRepository->findByCurrentStatus(ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY) as $reservation) {
            /** @var Reservation $reservation */
            $reservationDuration = $reservation->getProgram()->getReservationDuration();

            if (null === $reservationDuration) {
                // besoin de warning ici ?
                continue;
            }

            $reservationAcceptedByManagerDate = $reservation->getCurrentStatus()->getAdded();
            $nowDate                          = new DateTime('now');
            $intervalDate                     = $nowDate->diff($reservationAcceptedByManagerDate);

            if ($intervalDate->m < $reservationDuration || $reservationAcceptedByManagerDate > $nowDate) {
                continue;
            }

            $reservation->archive($adminStaff);

            if (0 === $i % self::BATCH_SIZE) {
                $this->reservationRepository->flush();
            }

            ++$i;
        }

        $this->reservationRepository->flush();

        return Command::SUCCESS;
    }
}
