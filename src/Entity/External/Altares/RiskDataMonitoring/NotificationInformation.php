<?php

namespace Unilend\Entity\External\Altares\RiskDataMonitoring;

use JMS\Serializer\Annotation as JMS;

class NotificationInformation
{
    /**
     * @JMS\SerializedName("alerteList")
     * @JMS\Type("array<Unilend\Entity\External\Altares\RiskDataMonitoring\Notification>")
     */
    private $notificationList;

    /**
     * @JMS\SerializedName("nbAlerteNonLuesGlobal")
     * @JMS\Type("integer")
     */
    private $countNotReadNotificationsGlobal;

    /**
     * @JMS\SerializedName("nbTotalAlerteList")
     * @JMS\Type("integer")
     */
    private $countNotReadNotificationsSelection;

    /**
     * @return mixed
     */
    public function getNotificationList()
    {
        return $this->notificationList;
    }

    /**
     * @return mixed
     */
    public function getCountNotReadNotificationsGlobal()
    {
        return $this->countNotReadNotificationsGlobal;
    }

    /**
     * @return mixed
     */
    public function getCountNotReadNotificationsSelection()
    {
        return $this->countNotReadNotificationsSelection;
    }

    /**
     * @param mixed $notificationList
     */
    public function setNotificationList($notificationList): void
    {
        $this->notificationList = $notificationList;
    }

    /**
     * @param mixed $countNotReadNotificationsGlobal
     */
    public function setCountNotReadNotificationsGlobal($countNotReadNotificationsGlobal): void
    {
        $this->countNotReadNotificationsGlobal = $countNotReadNotificationsGlobal;
    }

    /**
     * @param mixed $countNotReadNotificationsSelection
     */
    public function setCountNotReadNotificationsSelection($countNotReadNotificationsSelection): void
    {
        $this->countNotReadNotificationsSelection = $countNotReadNotificationsSelection;
    }


}
