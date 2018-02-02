<?php

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager;
use Unilend\Bundle\CoreBusinessBundle\Service\BulkCompanyCheckManager;

class projetsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_RISK);

        $this->menu_admin = 'emprunteurs';
    }

    public function _depot_liste()
    {
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        /** @var BulkCompanyCheckManager $bulkCompanyCheckManager */
        $bulkCompanyCheckManager = $this->get('unilend.service.eligibility.bulk_company_check_manager');

        if ($userManager->isGrantedRisk($this->userEntity)) {
            $success = '';
            $error   = '';
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $this->request->files->get('siren_list');

            if (false === empty($uploadedFile)) {
                $uploadDir = $bulkCompanyCheckManager->getProjectCreationInputPendingDir();
                try {
                    $bulkCompanyCheckManager->uploadFile($uploadDir, $uploadedFile, $this->userEntity);
                    $success = 'Le fichier a été pris en compte. Une notification vous sera envoyé dès qu\'il sera traité';
                } catch (\Exception $exception) {
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error(
                        'Could not upload the file into ' . $uploadDir . ' Error: ' . $exception->getMessage(),
                        ['method', __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                    $error = 'Le fichier n\'a pas été pris en compte. Veuillez rééssayer ou contacter l\'équipe technique.';
                }
            }
            $this->render(null, ['success' => $success, 'error' => $error]);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }
}
