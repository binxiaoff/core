<?php

use Unilend\Entity\Zones;

class settingsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);

        $this->menu_admin = 'configuration';
    }

    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $settingsRepository = $entityManager->getRepository(Settings::class);

        if (
            isset($_POST['form_edit_settings'], $this->params[0], $_POST['value'])
            && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            $setting = $settingsRepository->find($this->params[0]);
            $setting->setValue($_POST['value']);

            try {
                $entityManager->flush($setting);

                $_SESSION['freeow']['title']   = 'Modification d\'un paramètre';
                $_SESSION['freeow']['message'] = 'Le paramètre a bien été modifié';
            } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->error('Unable to update setting "' . $setting->getType() . '" - Message: ' . $exception->getMessage(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine()
                ]);

                $_SESSION['freeow']['title']   = 'Modification d\'un paramètre';
                $_SESSION['freeow']['message'] = 'Impossible de mettre à jour le paramètre';
            }

            header('Location: ' . $this->lurl . '/settings');
            die;
        }

        $this->settings = $settingsRepository->findBy([], ['type' => 'ASC']);
    }

    public function _edit()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->lurl . '/settings';

        if (isset($this->params[0]) && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager      = $this->get('doctrine.orm.entity_manager');
            $settingsRepository = $entityManager->getRepository(Settings::class);
            $this->setting      = $settingsRepository->find($this->params[0]);
        }
    }
}
