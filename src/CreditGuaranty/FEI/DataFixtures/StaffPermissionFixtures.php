<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\DataFixtures\UserFixtures;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\User;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class StaffPermissionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private UserPasswordEncoderInterface $passwordEncoder;
    private ObjectManager $manager;

    public function __construct(TokenStorageInterface $tokenStorage, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($tokenStorage);
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StaffFixtures::class,
            ParticipationFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        // Managing company (CASA)
        /** @var Company $company */
        $company = $this->getReference(CompanyFixtures::CASA);
        /** @var User $referenceUser */
        $referenceUser = $this->getReference(UserFixtures::ADMIN);

        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(
                    StaffPermission::PERMISSION_READ_PROGRAM
                    | StaffPermission::PERMISSION_EDIT_PROGRAM
                    | StaffPermission::PERMISSION_CREATE_PROGRAM
                    | StaffPermission::PERMISSION_READ_RESERVATION
                )
            );
            if ($staff->getUser() === $referenceUser) {
                $staffPermission->setGrantPermissions(
                    StaffPermission::PERMISSION_GRANT_READ_PROGRAM
                    | StaffPermission::PERMISSION_GRANT_CREATE_PROGRAM
                    | StaffPermission::PERMISSION_GRANT_EDIT_PROGRAM
                    | StaffPermission::PERMISSION_GRANT_READ_RESERVATION
                );
            }

            $manager->persist($staffPermission);
        }

        // Participant (CR)
        // we create the CG admin for CA banks.
        foreach (CompanyFixtures::CA_SHORTCODE as $companyName => $companyShortCode) {
            /** @var Company $company */
            $company = $this->getReference('company:' . $companyShortCode);
            $this->createAdminParticipant($company);
        }

        $manager->flush();
    }

    private function createAdminParticipant(Company $company): void
    {
        foreach ($company->getStaff() as $staff) {
            $staffPermission = new StaffPermission(
                $staff,
                new Bitmask(
                    StaffPermission::PERMISSION_READ_RESERVATION
                    | StaffPermission::PERMISSION_EDIT_RESERVATION
                    | StaffPermission::PERMISSION_CREATE_RESERVATION
                    | StaffPermission::PERMISSION_READ_PROGRAM
                )
            );
            // In the fixtures, there is only one staff per company, who is the admin
            $staffPermission->setGrantPermissions(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS);
            $this->manager->persist($staffPermission);
        }
    }
}
