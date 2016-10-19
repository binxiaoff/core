<?php

class simulationController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
    }

    public function _default()
    {
        header('Location: ' . $this->lurl);
    }

    public function _altares()
    {
        ini_set('default_socket_timeout', 60);
        $this->hideDecoration();
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Altares $altares */
        $altares = $this->get('unilend.service.altares');

        $siren = '';
        if (isset($_POST['siren']) && $_POST['siren'] != '') {
            $siren = $_POST['siren'];
        }
        $this->result = [];
        if (isset($_POST['api'])) {
            switch ($_POST['api']) {
                case 1 :
                    $this->result = $altares->getEligibility($siren);
                    break;
                case 2 :
                    $this->result = $altares->getBalanceSheets($siren);
                    break;
                default :
                    $this->result = [];
                    break;
            }
        }
    }
}
