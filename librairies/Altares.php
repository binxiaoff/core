<?php

namespace Unilend\librairies;

class Altares
{
    const RESPONSE_CODE_INACTIVE                       = 1;
    const RESPONSE_CODE_NOT_REGISTERED                 = 2;
    const RESPONSE_CODE_PROCEDURE                      = 3;
    const RESPONSE_CODE_OLD_ANNUAL_ACCOUNTS            = 4;
    const RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK         = 5;
    const RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES = 6;
    const RESPONSE_CODE_UNKNOWN_SIREN                  = 7;
    const RESPONSE_CODE_ELIGIBLE                       = 8;
    const RESPONSE_CODE_NO_ANNUAL_ACCOUNTS             = 9;

    /**
     * @var string
     */
    private $sIdentification;

    /**
     * @var \bdd
     */
    private $oDatabase;

    /**
     * @var \settings
     */
    private $oSettings;

    /**
     * @param \bdd $oDatabase
     */
    public function __construct(\bdd $oDatabase)
    {
        ini_set('default_socket_timeout', 60);

        require_once __DIR__ . '/../data/settings.data.php';

        $this->oDatabase = $oDatabase;
        $this->oSettings = new \settings($oDatabase);

        $this->oSettings->get('Altares login', 'type');

        $this->sIdentification = $this->oSettings->value;

        $this->oSettings->get('Altares mot de passe', 'type');
        $this->sIdentification .= '|' . $this->oSettings->value;
    }

    /**
     * Retrieve getEligibility WS data
     * @param int $iSIREN
     * @return mixed
     */
    public function getEligibility($iSIREN)
    {
        $this->oSettings->get('Altares WSDL Eligibility', 'type');

        return $this->soapCall($this->oSettings->value, 'getEligibility', array('siren' => $iSIREN));
    }

    /**
     * Retrieve getDerniersBilans WS data
     * @param int $iSIREN
     * @param int $iSheetsCount
     * @return mixed
     */
    public function getBalanceSheets($iSIREN, $iSheetsCount = 3)
    {
        $this->oSettings->get('Altares WSDL CallistoIdentite', 'type');

        return $this->soapCall($this->oSettings->value, 'getDerniersBilans', array('siren' => $iSIREN, 'nbBilans' => $iSheetsCount));
    }

    public function setCompanyData($iCompanyId)
    {
        // @todo
    }

    /**
     * Make SOAP call to Altares WS
     * @param string $sWSDLUrl
     * @param string $sWSName
     * @param array $aParameters
     * @return mixed
     */
    private function soapCall($sWSDLUrl, $sWSName, array $aParameters = array())
    {
        $oClient = new \SoapClient($sWSDLUrl, array('trace' => 1, 'exception' => true));
        $oResult = $oClient->__soapCall(
            $sWSName,
            array(array('identification' => $this->sIdentification, 'refClient' => 'sffpme') + $aParameters)
        );
        return isset($oResult->return) ? $oResult->return : null;
    }
}
