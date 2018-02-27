<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Autobid;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsHistoryActions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager;
use Unilend\core\Loader;

class AutolendController extends Controller
{
    /**
     * @Route("/profile/autolend", name="autolend")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function autolendAction(Request $request)
    {
        $autoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $client                 = $this->getClient();
        $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        if (false === $autoBidSettingsManager->isQualified($client)) {
            return $this->redirectToRoute('lender_profile');
        }

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        /** @var \projects $project */
        $project           = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $autobidRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->get('unilend.service.entity_manager')->getRepository('project_rate_settings');
        /** @var \project_period $projectPeriods */
        $projectPeriods = $this->get('unilend.service.entity_manager')->getRepository('project_period');

        if ($request->isMethod('POST')) {
            /** @var \client_settings $clientSettings */
            $clientSettings = $this->get('unilend.service.entity_manager')->getRepository('client_settings');

            /** @var array $messages */
            $messages = [];
            $post     = $request->request->all();

            if ($request->isXmlHttpRequest()) {
                if (false === empty($post['setting']) && $post['setting'] === 'autolend-off') {
                    $this->saveAutolendOff($clientSettings, $autoBidSettingsManager, $request);
                }
            } else {
                if (isset($post['hidden-settings-mode-input']) && $post['hidden-settings-mode-input'] === 'simple') {
                    $messages = $this->handleSimpleSettings($post, $settings, $autoBidSettingsManager, $request);
                }

                if (isset($post['hidden-settings-mode-input']) && $post['hidden-settings-mode-input'] === 'expert') {
                    $messages = $this->handleExpertSettings($post, $settings, $autoBidSettingsManager, $request);
                }

                $translator = $this->get('translator');
                if (isset($messages['error'])) {
                    foreach ($messages['error'] as $error) {
                        $this->addFlash('autolend_error', $error);
                    }
                } else {
                    $success = $translator->trans('autolend_update-settings-success-message');
                    $this->addFlash('autolend_success', $success);
                }
            }

            return $this->redirectToRoute('autolend');
        }

        $projectPeriods = $projectPeriods->select('', 'min ASC');
        foreach ($projectPeriods as $period) {
            $template['projectPeriods'][$period['id_period']] = $period;
        }

        $settings->get('date-premier-projet-tunnel-de-taux', 'type');
        $startingDate = $settings->value;

        $template['averageRateUnilend'] = round($project->getAvgRate(null, null, null, $startingDate), 1);
        $projectRates                   = $projectRateSettings->getSettings();
        $projectRateFormatted           = [];
        foreach ($projectRates as $rate) {
            $projectRateFormatted[$rate['id_period']][$rate['evaluation']] = $rate;
        }

        $template['projectRatesGlobal'] = $autoBidSettingsManager->getRateRange();
        $autoBidSettings                = $autobidRepository->getSettings($wallet, null, null, [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE]);
        $autoBidSettings                = $this->fillMissingAutolendSettings($autoBidSettings, $projectPeriods, $project, $projectRateSettings);

        foreach ($autoBidSettings as $aSetting) {
            $aSetting['project_rate_min'] = $template['projectRatesGlobal']['rate_min'];
            $aSetting['project_rate_max'] = $template['projectRatesGlobal']['rate_max'];

            if (isset($projectRateFormatted[$aSetting['id_period']][$aSetting['evaluation']])) {
                $aSetting['project_rate_min'] = $projectRateFormatted[$aSetting['id_period']][$aSetting['evaluation']]['rate_min'];
                $aSetting['project_rate_max'] = $projectRateFormatted[$aSetting['id_period']][$aSetting['evaluation']]['rate_max'];
            }

            $averageRateUnilend                                    = $project->getAvgRate($aSetting['evaluation'], $aSetting['period_min'], $aSetting['period_max'], $startingDate);
            $medianRateForSetting                                  = bcdiv(bcadd($aSetting['project_rate_min'], $aSetting['project_rate_max']), 2, 1);
            $aSetting['cellAverageRateUnilend']                    = ($averageRateUnilend > 0) ? $averageRateUnilend : $medianRateForSetting;
            $template['autoBidSettings'][$aSetting['id_period']][] = $aSetting;
        }

        try {
            $template['validation_date'] = $autoBidSettingsManager->getValidationDate($client);
        } catch (\Exception $exception) {
            $template['validation_date'] = '';
            $this->get('logger')->error(
                'Could not get the last autobid settings validation date for the client: ' . $client->getIdClient() . '. Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        $template['autobid_on']      = $autoBidSettingsManager->isOn($client);
        $template['never_activated'] = false === $autoBidSettingsManager->hasAutoBidActivationHistory($client);
        $template['is_novice']       = $autoBidSettingsManager->isNovice($client);
        $template['autolend_amount'] = $autoBidSettingsManager->getAmount($client);

        return $this->render('autolend/autolend.html.twig', $template);
    }

    /**
     * @param array                  $post
     * @param \settings              $settings
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param Request                $request
     *
     * @return array
     */
    private function handleSimpleSettings(array $post, \settings $settings, AutoBidSettingsManager $autoBidSettingsManager, Request $request)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var Clients $client */
        $client = $this->getClient();

        $settings->get('pret min', 'type');
        $minimumBidAmount = (int) $settings->value;
        $errorMsg         = [];
        $autolendAmount   = null;
        $autolendRateMin  = null;

        try {
            $maxBidAmount = $autoBidSettingsManager->getMaxAmountPossible($client);
        } catch (\Exception $exception) {
            return ['error' => [$translator->trans('autolend_error-message-simple-settings-failed')]];
        }

        if (false === empty($post['autolend_amount'])) {
            $autolendAmount = $ficelle->cleanFormatedNumber($post['autolend_amount']);
        }

        if (false === empty($post['autolend_rate_min'])) {
            $autolendRateMin = $ficelle->cleanFormatedNumber($post['autolend_rate_min']);
        }

        if (empty($autolendAmount) || false === is_numeric($autolendAmount) || $autolendAmount < $minimumBidAmount || (null !== $maxBidAmount && $autolendAmount > $maxBidAmount)) {
            if (null === $maxBidAmount) {
                $errorMsg[] = $translator->trans('autolend_error-message-amount-wrong', [
                    '%MIN_AMOUNT%' => $ficelle->formatNumber($minimumBidAmount, 0)
                ]);
            } else {
                $errorMsg[] = $translator->trans('autolend_error-message-amount-wrong-with-max', [
                    '%MIN_AMOUNT%' => $ficelle->formatNumber($minimumBidAmount, 0),
                    '%MAX_AMOUNT%' => $ficelle->formatNumber($maxBidAmount, 0)
                ]);
            }
        }

        if (empty($autolendRateMin) || false === is_numeric($autolendRateMin) || false === $autoBidSettingsManager->isRateValid($autolendRateMin)) {
            $errorMsg[] = $translator->trans('autolend_error-message-simple-setting-rate-wrong');
        }

        if (false === empty($errorMsg)) {
            return ['error' => $errorMsg];
        }

        try {
            $autoBidSettingsManager->saveNoviceSetting($client, $autolendRateMin, $autolendAmount);

            try {
                if (false === $autoBidSettingsManager->isOn($client)) {
                    $autoBidSettingsManager->on($client);
                    $this->saveAutoBidSwitchHistory($client, \client_settings::AUTO_BID_ON, $request);
                }
            } catch (\Exception $exception) {
                $this->get('logger')->error(
                    'Could not determine autobid switch status, or turn autobid switch ON for client: ' . $client->getIdClient() . '. Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__ . ':' . __LINE__, 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );

                return ['error' => [$translator->trans('autolend_error-message-switch-on-failed')]];
            }
        } catch (\Exception $exception) {
            $this->get('logger')->error(
                'Could not save novice autolend settings for client: ' . $client->getIdClient() . ' Exception: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'min_rate' => $autolendRateMin, 'amount' => $autolendAmount]
            );

            return ['error' => [$translator->trans('autolend_error-message-simple-settings-failed')]];
        }

        return [];
    }

    /**
     * @param array                  $post
     * @param \settings              $settings
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param Request                $request
     *
     * @return array
     */
    private function handleExpertSettings(array $post, \settings $settings, AutoBidSettingsManager $autoBidSettingsManager, Request $request)
    {
        $entityManagerSimulator  = $this->get('unilend.service.entity_manager');
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $client                  = $this->getClient();
        $projectPeriodRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectPeriod');
        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $settings->get('pret min', 'type');
        $minimumBidAmount = (int) $settings->value;
        $autoBidPeriods   = [];
        $errorMsg         = [];
        $aRiskValues      = $project->getAvailableRisks();
        $amount           = null;

        try {
            $maxBidAmount = $autoBidSettingsManager->getMaxAmountPossible($client);
        } catch (\Exception $exception) {
            return ['error' => [$translator->trans('autolend_error-message-advanced-settings-failed')]];
        }

        foreach ($projectPeriodRepository->findBy(['status' => \project_period::STATUS_ACTIVE]) as $period) {
            $autoBidPeriods[] = $period->getIdPeriod();
        }

        if (isset($post['autolend_amount'])) {
            $amount = $ficelle->cleanFormatedNumber($post['autolend_amount']);
        }

        if (empty($amount) || false === is_numeric($amount) || $amount < $minimumBidAmount || (null !== $maxBidAmount && $amount > $maxBidAmount)) {
            if (null === $maxBidAmount) {
                $errorMsg[] = $translator->trans('autolend_error-message-amount-wrong', [
                    '%MIN_AMOUNT%' => $ficelle->formatNumber($minimumBidAmount, 0)
                ]);
            } else {
                $errorMsg[] = $translator->trans('autolend_error-message-amount-wrong-with-max', [
                    '%MIN_AMOUNT%' => $ficelle->formatNumber($minimumBidAmount, 0),
                    '%MAX_AMOUNT%' => $ficelle->formatNumber($maxBidAmount, 0)
                ]);
            }
        }

        if (false === isset($post['data']) || false === $this->checkPostedAdvancedSettings($post['data'], $autoBidPeriods, $aRiskValues)) {
            return ['error' => [$translator->trans('autolend_error-message-invalid-expert-form-data')]];
        }

        foreach ($post['data'] as $setting) {
            if (
                isset($setting['interest'], $setting['period'], $setting['evaluation'], $setting['is-active']) &&
                null !== ($projectPeriodEntity = $projectPeriodRepository->find($setting['period']))
            ) {
                $note              = constant(Projects::class . '::RISK_' . $setting['evaluation']);
                $note              = is_float($note) ? $ficelle->formatNumber($note, 1) : $note;
                $periodTranslation = $translator->trans(
                    'autolend_expert-settings-project-period-' . $projectPeriodEntity->getIdPeriod(),
                    ['%min%' => $projectPeriodEntity->getMin(), '%max%' => $projectPeriodEntity->getMax()]
                );

                if (
                    $setting['is-active'] == Autobid::STATUS_ACTIVE &&
                    (false === in_array($setting['evaluation'], $aRiskValues) || false === in_array($setting['period'], $autoBidPeriods))
                ) {
                    $errorMsg[] = $translator->trans('autolend_error-message-expert-setting-category-non-exist', [
                        '%RISK%'   => $note,
                        '%period%' => $periodTranslation
                    ]);
                }

                if (
                    $setting['is-active'] == Autobid::STATUS_ACTIVE
                    && empty($setting['interest']) || false === is_numeric($ficelle->cleanFormatedNumber($setting['interest']))
                ) {
                    $projectRateRange = $autoBidSettingsManager->getRateRange($setting['evaluation'], $setting['period']);
                    $errorMsg[]       = $translator->trans('autolend_error-message-expert-setting-rate-wrong', [
                        '%RISK%'     => $note,
                        '%period%'   => $periodTranslation,
                        '%RATE_MIN%' => $ficelle->formatNumber($projectRateRange['rate_min'], 1),
                        '%RATE_MAX%' => $ficelle->formatNumber($projectRateRange['rate_max'], 1)
                    ]);
                }
            } else {
                $errorMsg[] = $translator->trans('autolend_error-message-invalid-expert-form-data');
                break;
            }
        }

        if (false === empty($errorMsg)) {
            return ['error' => $errorMsg];
        }
        $entityManager->getConnection()->beginTransaction();

        try {
            foreach ($post['data'] as $setting) {
                $rate = $ficelle->cleanFormatedNumber($setting['interest']);
                $rate = filter_var($rate, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $autoBidSettingsManager->saveSetting($client, $setting['evaluation'], $projectPeriodRepository->find($setting['period']), $rate, $amount);
                $autoBidSettingsManager->activateDeactivateSetting($client, $setting['evaluation'], $setting['period'], $setting['is-active']);
            }
            $entityManager->getConnection()->commit();

            try {
                if (false === $autoBidSettingsManager->isOn($client)) {
                    $autoBidSettingsManager->on($client);
                    $this->saveAutoBidSwitchHistory($client, \client_settings::AUTO_BID_ON, $request);
                }
            } catch (\Exception $exception) {
                $this->get('logger')->error(
                    'Could not determine autobid switch status, or turn autobid switch on for client: ' . $client->getIdClient() . '. Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );

                return ['error' => [$translator->trans('autolend_error-message-switch-on-failed')]];
            }
        } catch (\Exception $exception) {
            $logger = $this->get('logger');
            try {
                $entityManager->getConnection()->rollBack();
            } catch (\Exception $rollBackException) {
                $logger->error(
                    'Error while trying to rollback the transaction on autobid expert settings save. Message: ' . $rollBackException->getMessage(),
                    ['method' => __METHOD__, 'id_client' => $client->getIdClient(), 'file' => $rollBackException->getFile(), 'line' => $rollBackException->getLine()]
                );
            }

            $lastProcessedSetting = empty($setting) ? [] : $setting;
            $logger->error(
                'Could not save advanced autolend settings for client ' . $client->getIdClient() . '. Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'last_processed_setting' => $lastProcessedSetting]
            );

            return ['error' => [$translator->trans('autolend_error-message-advanced-settings-failed')]];
        }

        return [];
    }

    /**
     * @param \client_settings       $clientSettings
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param Request                $request
     *
     * @return string
     * @throws \Exception
     */
    private function saveAutolendOff(\client_settings $clientSettings, AutoBidSettingsManager $autoBidSettingsManager, Request $request)
    {
        /** @var Clients $client */
        $client = $this->getClient();

        if (\client_settings::AUTO_BID_ON == $clientSettings->getSetting($client->getIdClient(), \client_setting_type::TYPE_AUTO_BID_SWITCH)) {
            $autoBidSettingsManager->off($client);
            $this->saveAutoBidSwitchHistory($client, \client_settings::AUTO_BID_OFF, $request);
            return 'update_off_success';
        } else {
            return 'already-off';
        }
    }

    /**
     * @param array                  $userSettings
     * @param array                  $projectPeriods
     * @param \projects              $project
     * @param \project_rate_settings $projectRateSettings
     *
     * @return array
     */
    private function fillMissingAutolendSettings(array $userSettings, array $projectPeriods, \projects $project, \project_rate_settings $projectRateSettings)
    {
        $settings           = [];
        $fakeSettingsStatus = empty($userSettings) ? Autobid::STATUS_ACTIVE : Autobid::STATUS_INACTIVE;
        $availableRisks     = $project->getAvailableRisks();
        rsort($availableRisks);

        $logger = $this->get('logger');

        foreach ($projectPeriods as $period) {
            foreach ($availableRisks as $risk) {
                $rateSetting = $projectRateSettings->select('id_period = ' . $period['id_period'] . ' AND evaluation = "' . $risk . '" AND status = ' . \project_rate_settings::STATUS_ACTIVE);
                $key         = $period['min'] . $risk;

                if (false === array_key_exists($key, $userSettings)) {
                    $rate           = array_shift($rateSetting);
                    $averageRate    = bcdiv(bcadd($rate['rate_min'], $rate['rate_max']), 2, 1);
                    $settings[$key] = [
                        'id_autobid' => '',
                        'status'     => $fakeSettingsStatus,
                        'evaluation' => $risk,
                        'id_period'  => $period['id_period'],
                        'rate_min'   => $averageRate,
                        'amount'     => '',
                        'period_min' => $period['min'],
                        'period_max' => $period['max']
                    ];
                    $logger->warning(
                        'The autobid setting is missing for the combination evaluation/period: ' . $risk . '/' . $period['id_period'] . ' for the client: ' . $this->getClient()->getIdClient() .
                        '. A default setting will be used',
                        ['method' => __METHOD__, 'id_client' => $this->getClient()->getIdClient()]
                    );
                } else {
                    $settings[$key] = $userSettings[$key];
                }
            }
        }

        return $settings;
    }

    /**
     * @param Clients $client
     * @param string  $value
     * @param Request $request
     */
    private function saveAutoBidSwitchHistory(Clients $client, $value, Request $request)
    {
        $onOff       = $value === \client_settings::AUTO_BID_ON ? 'on' : 'off';
        $userId      = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sSerialized = serialize(array('id_user' => $userId, 'id_client' => $client->getIdClient(), 'autobid_switch' => $onOff));
        $this->get('unilend.frontbundle.service.form_manager')->saveFormSubmission($client, ClientsHistoryActions::AUTOBID_SWITCH, $sSerialized, $request->getClientIp());
    }

    /**
     * @return Clients
     */
    private function getClient()
    {
        return $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
    }

    /**
     * @param array $postedSettings
     * @param array $projectPeriodsId
     * @param array $projectEvaluations
     *
     * @return bool
     */
    private function checkPostedAdvancedSettings(array $postedSettings, array $projectPeriodsId, array $projectEvaluations): bool
    {
        $postedPeriods     = array_unique(array_column($postedSettings, 'period'));
        $postedEvaluations = array_unique(array_column($postedSettings, 'evaluation'));

        if (0 !== count(array_diff($projectPeriodsId, $postedPeriods)) || 0 !== count(array_diff($projectEvaluations, $postedEvaluations))) {
            return false;
        }

        return true;
    }
}
