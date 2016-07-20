<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class ProjectRateSettingsManager
{
    const RATE_CHANGED_NOTIFICATION_MAIL = 'project-rate-settings-modification-notification';

    /** @var EntityManager */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;

    public function __construct(EntityManager $entityManager, TemplateMessageProvider $messageProvider, \Swift_Mailer $mailer)
    {
        $this->entityManager   = $entityManager;
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
    }

    /**
     * @param string $evaluation
     * @param int    $periodId
     * @param float  $rateMin
     * @param float  $rateMax
     *
     * @return bool
     */
    public function saveSetting($evaluation, $periodId, $rateMin, $rateMax)
    {
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->entityManager->getRepository('project_rate_settings');
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');

        $rateMin = str_replace(',', '.', $rateMin);
        $rateMax = str_replace(',', '.', $rateMax);

        $rateMinOld = '';
        $rateMaxOld = '';

        $existingProjectRateSettings = $projectRateSettings->select('evaluation = "' . $evaluation . '" AND id_period = ' . $periodId . ' AND status != ' . \project_rate_settings::STATUS_ARCHIVED);

        if (empty($existingProjectRateSettings)) {
            $this->createSettings($evaluation, $periodId, $rateMin, $rateMax);
        } else {
            $activeProjectRateSettings = array_shift($existingProjectRateSettings);

            if ($projectRateSettings->get($activeProjectRateSettings['id_rate'])) {
                $rateMinOld = $projectRateSettings->rate_min;
                $rateMaxOld = $projectRateSettings->rate_max;

                if ($rateMin != $rateMinOld || $rateMax != $rateMaxOld) {
                    if ($project->exist($activeProjectRateSettings['id_rate'], 'id_rate')) {
                        $projectRateSettings->status = \project_rate_settings::STATUS_ARCHIVED;
                        $projectRateSettings->update();
                        $this->createSettings($evaluation, $periodId, $rateMin, $rateMax);
                    } else {
                        $projectRateSettings->rate_min = $rateMin;
                        $projectRateSettings->rate_max = $rateMax;
                        $projectRateSettings->update();
                    }
                }
            }

            // It shouldn't have more than one settings for each category, but if we have, archive them all.
            if (false === empty($existingProjectRateSettings)) {
                foreach ($existingProjectRateSettings as $settings) {
                    $projectRateSettings->get($settings['id_rate']);
                    $projectRateSettings->status = \project_rate_settings::STATUS_ARCHIVED;
                    $projectRateSettings->update();
                }
            }
        }
        if ($rateMin != $rateMinOld || $rateMax != $rateMaxOld) {
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('Adresse project rate setting', 'type');
            $recipient = $settings->value;

            /** @var \project_period $period */
            $period = $this->entityManager->getRepository('project_period');
            $period->get($periodId);

            $varMail = [
                'evaluation' => $evaluation,
                'periodMin'  => $period->min,
                'periodMax'  => $period->max,
                'rateMin'    => $rateMin,
                'rateMax'    => $rateMax,
                'rateMinOld' => $rateMinOld,
                'rateMaxOld' => $rateMaxOld,
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage(self::RATE_CHANGED_NOTIFICATION_MAIL, $varMail);
            $message->setTo($recipient);
            $this->mailer->send($message);
        }
    }

    /**
     * @param string $evaluation
     * @param int    $periodId
     * @param float  $rateMin
     * @param float  $rateMax
     *
     * @return bool
     */
    private function createSettings($evaluation, $periodId, $rateMin, $rateMax)
    {
        /** @var \project_rate_settings $oAutoBid */
        $projectRateSettings = $this->entityManager->getRepository('project_rate_settings');

        $projectRateSettings->status     = \project_rate_settings::STATUS_ACTIVE;
        $projectRateSettings->evaluation = $evaluation;
        $projectRateSettings->id_period  = $periodId;
        $projectRateSettings->rate_min   = $rateMin;
        $projectRateSettings->rate_max   = $rateMax;
        $projectRateSettings->create();
    }
}