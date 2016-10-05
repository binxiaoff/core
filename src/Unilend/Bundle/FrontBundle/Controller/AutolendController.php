<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use \Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class AutolendController extends Controller
{
    /**
     * @Route("/profile/autolend", name="autolend")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function autolendAction(Request $request)
    {
        /** @var AutoBidSettingsManager $autoBidSettingsManager */
        $autoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        /** @var \clients $client */
        $client = $this->getClient();
        /** @var \lenders_accounts $lendersAccounts */
        $lendersAccounts  = $this->getLenderAccount();

        if (false === $autoBidSettingsManager->isQualified($lendersAccounts)) {
            return $this->redirectToRoute('lender_profile');
        }

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        /** @var \clients_status $clientStatus */
        $clientStatus = $this->get('unilend.service.entity_manager')->getRepository('clients_status');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        /** @var \autobid $autobid */
        $autobid = $this->get('unilend.service.entity_manager')->getRepository('autobid');
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->get('unilend.service.entity_manager')->getRepository('project_rate_settings');
        /** @var \project_period $projectPeriods */
        $projectPeriods = $this->get('unilend.service.entity_manager')->getRepository('project_period');

        if ($request->isMethod('POST')) {
            /** @var \client_settings $clientSettings */
            $clientSettings = $this->get('unilend.service.entity_manager')->getRepository('client_settings');

            /** @var array $messages */
            $messages = [];
            $post = $request->request->all();

            if ($request->isXmlHttpRequest()) {
                if (false === empty($post['setting']) && $post['setting'] == 'autolend-off') {
                    $this->saveAutolendOff($lendersAccounts, $clientSettings, $autoBidSettingsManager);
                }
            } else {
                if (isset($post['hidden-settings-mode-input']) && $post['hidden-settings-mode-input'] == 'simple') {
                    $messages = $this->handleSimpleSettings($post, $settings, $lendersAccounts, $autoBidSettingsManager);
                }

                if (isset($post['hidden-settings-mode-input']) && $post['hidden-settings-mode-input'] == 'expert') {
                    $messages = $this->handleExpertSettings($post, $settings, $lendersAccounts, $autoBidSettingsManager);
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
        }

        $projectPeriods = $projectPeriods->select();
        foreach ($projectPeriods as $period) {
            $template['projectPeriods'][$period['id_period']] = $period;
        }
        $clientStatus->getLastStatut($client->id_client);

        $settings->get('date-premier-projet-tunnel-de-taux', 'type');
        $startingDate = $settings->value;

        $template['averageRateUnilend'] = round($project->getAvgRate(null, null, null, $startingDate), 1);
        $projectRates = $projectRateSettings->getSettings();
        $projectRateFormatted = [];
        foreach ($projectRates as $rate) {
            $projectRateFormatted[$rate['id_period']][$rate['evaluation']] = $rate;
        }

        $template['projectRatesGlobal'] = $autoBidSettingsManager->getRateRange();
        $autoBidSettings = $autobid->getSettings($lendersAccounts->id_lender_account, null, null, array(\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE));

        if (empty($autoBidSettings)) {
            $autoBidSettings = $this->generateFakeAutolendSettings($projectPeriods, $project, $projectRateSettings);
        }

        foreach ($autoBidSettings as $aSetting) {
            $aSetting['project_rate_min'] = $template['projectRatesGlobal']['rate_min'];
            $aSetting['project_rate_max'] = $template['projectRatesGlobal']['rate_max'];

            if (isset($projectRateFormatted[$aSetting['id_period']][$aSetting['evaluation']])) {
                $aSetting['project_rate_min'] = $projectRateFormatted[$aSetting['id_period']][$aSetting['evaluation']]['rate_min'];
                $aSetting['project_rate_max'] = $projectRateFormatted[$aSetting['id_period']][$aSetting['evaluation']]['rate_max'];
            }

            $averageRateUnilend   = $project->getAvgRate($aSetting['evaluation'], $aSetting['period_min'], $aSetting['period_max'], $startingDate);
            $medianRateForSetting = bcdiv(bcadd($aSetting['project_rate_min'], $aSetting['project_rate_max']), 2, 1);;
            $aSetting['cellAverageRateUnilend'] = ($averageRateUnilend > 0) ? $averageRateUnilend : $medianRateForSetting;
            $template['autoBidSettings'][$aSetting['id_period']][] = $aSetting;
        }

        /** @var \DateTime $validateTime */
        $validateTime = $autoBidSettingsManager->getValidationDate($lendersAccounts);

        $template['autobid_on']      = $autoBidSettingsManager->isOn($lendersAccounts);
        $template['never_activated'] = false === $autoBidSettingsManager->hasAutoBidActivationHistory($lendersAccounts);
        $template['is_novice']       = $autoBidSettingsManager->isNovice($lendersAccounts);
        $template['validation_date'] = strftime('%d %B %G', $validateTime->format('U'));
        $template['autolend_amount'] = $autoBidSettingsManager->getAmount($lendersAccounts);

        return $this->render('pages/autolend.html.twig', $template);
    }

    private function handleSimpleSettings($post, \settings $settings, \lenders_accounts $lenderAccount, AutoBidSettingsManager $autoBidSettingsManager)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $settings->get('pret min', 'type');
        $minimumBidAmount = (int)$settings->value;
        $errorMsg = [];

        if (empty($post['autolend_amount']) || false === is_numeric($post['autolend_amount']) || $post['autolend_amount'] < $minimumBidAmount) {
            $errorMsg[] = $translator->trans('autolend_error-message-amount-wrong', ['%MIN_AMOUNT%' => $minimumBidAmount]);
        }

        if (empty($post['autolend_rate_min'])
            || false === str_replace(',', '.', $post['autolend_rate_min'])
            || false === $autoBidSettingsManager->isRateValid(str_replace(',', '.', $post['autolend_rate_min']))) {
            $errorMsg[] = $translator->trans('autolend_error-message-simple-setting-rate-wrong');
        }

        if (empty($errorMsg)) {
            if (false === $autoBidSettingsManager->isOn($lenderAccount)) {
                $autoBidSettingsManager->on($lenderAccount);
            }
            $autoBidSettingsManager->saveNoviceSetting($lenderAccount->id_lender_account, $post['autolend_rate_min'], $post['autolend_amount']);
        } else {
            return array('error' => $errorMsg);
        }
    }

    private function handleExpertSettings($post, \settings $settings, \lenders_accounts $lenderAccount, AutoBidSettingsManager $autoBidSettingsManager)
    {
        /** @var \project_period $projectPeriods */
        $projectPeriods = $this->get('unilend.service.entity_manager')->getRepository('project_period');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
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

        foreach ($projectPeriods->select('status = ' . \project_period::STATUS_ACTIVE) as $period) {
            $autoBidPeriods[] = $period['id_period'];
        }

        if (isset($post['autolend_amount'])) {
            $amount = $ficelle->cleanFormatedNumber($post['autolend_amount']);
        }

        if (empty($amount) || false === is_numeric($amount) || $amount < $minimumBidAmount) {
            $errorMsg[] = $translator->trans('autolend_error-message-amount-wrong', ['%MIN_AMOUNT%' => $minimumBidAmount]);
        }

        foreach ($post['data'] as $setting) {
            $projectPeriods->get($setting['period']);
            $note              = constant('\projects::RISK_' . $setting['evaluation']);
            $note              = is_float($note) ? $ficelle->formatNumber($note, 1) : $note;
            $periodTranslation = $translator->trans(
                'autolend_expert-settings-project-period-' . $projectPeriods->id_period,
                ['%min%' => $projectPeriods->min, '%max%' => $projectPeriods->max]
            );

            if (
                $setting['is-active'] == \autobid::STATUS_ACTIVE &&
                (false === in_array($setting['evaluation'], $aRiskValues) || false === in_array($setting['period'], $autoBidPeriods))
            ) {
                $errorMsg[] = $translator->trans('autolend_error-message-expert-setting-category-non-exist', [
                    '%RISK%'   => $note,
                    '%period%' => $periodTranslation
                ]);
            }

            if (
                $setting['is-active'] == \autobid::STATUS_ACTIVE
                && empty($setting['interest']) || false === is_numeric($ficelle->cleanFormatedNumber($setting['interest']))
            ) {
                $projectRateRange = $autoBidSettingsManager->getRateRange($setting['evaluation'], $setting['period']);
                $errorMsg[]       = $translator->trans('autolend_error-message-expert-setting-rate-wrong', [
                        '%RISK%'     => $note,
                        '%period%'   => $periodTranslation,
                        '%RATE_MIN%' => $ficelle->formatNumber($projectRateRange['rate_min'], 1),
                        '%RATE_MAX%' => $ficelle->formatNumber($projectRateRange['rate_max'], 1)
                    ]
                );
            }
        }

        if (false === empty($errorMsg)) {
            return ['error' => $errorMsg];
        }

        if (false === $autoBidSettingsManager->isOn($lenderAccount)) {
            $autoBidSettingsManager->on($lenderAccount);
        }

        foreach ($post['data'] as $setting) {
            $rate = $ficelle->cleanFormatedNumber($setting['interest']);
            $autoBidSettingsManager->saveSetting($lenderAccount->id_lender_account, $setting['evaluation'], $setting['period'], $rate, $amount);
            $autoBidSettingsManager->activateDeactivateSetting($lenderAccount->id_lender_account, $setting['evaluation'], $setting['period'], $setting['is-active']);
        }

        return [];
    }

    private function saveAutolendOff(\lenders_accounts $lenderAccount, \client_settings $clientSettings, AutoBidSettingsManager $autoBidSettingsManager)
    {
        if (\client_settings::AUTO_BID_ON == $clientSettings->getSetting($lenderAccount->id_client_owner, \client_setting_type::TYPE_AUTO_BID_SWITCH)) {
            $autoBidSettingsManager->off($lenderAccount);
            return 'update_off_success';
        } else {
            return 'already-off';
        }
    }

    private function getClient()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientId);

        return $client;
    }

    private function getLenderAccount()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($clientId, 'id_client_owner');

        return $lenderAccount;
    }

    private function generateFakeAutolendSettings($projectPeriods, \projects $project, \project_rate_settings $projectRateSettings)
    {
        $settings = [];

        foreach ($projectPeriods as $period ) {
            foreach($project->getAvailableRisks() as $risk) {
                $rateSetting = $projectRateSettings->select('id_period = ' . $period['id_period'] . ' AND evaluation = "' . $risk . '" AND status = ' . \project_rate_settings::STATUS_ACTIVE);
                $rate = array_shift($rateSetting);
                $averageRate = bcdiv(bcadd($rate['rate_min'], $rate['rate_max']), 2, 1);
                $settings[] = [
                    'id_autobid' => '',
                    'status' => 1,
                    'evaluation' => $risk,
                    'id_period' => $period['id_period'],
                    'rate_min' => $averageRate,
                    'amount' => '',
                    'period_min' => $period['min'],
                    'period_max' => $period['max']
                ];
            }
        }

        return $settings;
    }
}
