<?php

namespace Unilend\librairies\greenPoint;

/**
 * Created by PhpStorm.
 * User: mesbahzitouni
 * Date: 24/03/2016
 * Time: 10:04
 */
class greenPoint
{

    const TEST_URL = 'https://id-control.fr/api/';
    const PROD_URL = 'https://id-control.fr/api/';

    /**
     * @var string
     */
    private $sPassWord;
    /**
     * @var string
     */
    private $sLogin;
    /**
     * @var string
     */
    private $sUrl;
    /**
     * @var array
     */
    private $aRequests;
    /**
     * @var \bdd
     */
    private $oDB;
    /**
     * @var \settings
     */
    private $oSettings;

    /**
     * greenPoint constructor.
     * @param \bdd $oDB
     * @param string $sEnv
     */
    public function __construct(\bdd $oDB, $sEnv)
    {
        require_once __DIR__ . '/../../data/settings.data.php';

        $this->oDB       = $oDB;
        $this->oSettings = new \settings($this->oDB);

        switch ($sEnv) {
            case 'prod':
                $this->sUrl = self::PROD_URL;

                $this->oSettings->get('green_point_pw_prod', 'type');
                $this->sPassWord = $this->oSettings->value;

                $this->oSettings->get('green_point_login_prod', 'type');
                $this->sLogin = $this->oSettings->value;
                break;
            default :
                $this->sUrl = self::TEST_URL;

                $this->oSettings->get('green_point_pw_test', 'type');
                $this->sPassWord = $this->oSettings->value;

                $this->oSettings->get('green_point_login_test', 'type');
                $this->sLogin = $this->oSettings->value;
                break;
        }
    }

    public function __destruct()
    {

    }

    private function addRequest($sMethod, $aRequestParams, $bReturnQueryId = false)
    {
        $iRequestId = count($this->aRequests);
        $this->aRequests[$iRequestId]['REQUEST_PARAMS'] = $aRequestParams;
        $this->aRequests[$iRequestId]['REQUEST_METHOD'] = $sMethod;
        if(true === $bReturnQueryId){
            return $iRequestId;
        } else {
            return $this;
        }
    }

    /**
     * @return mixed
     */
    public function sendRequests()
    {
        if(is_array($this->aRequests) && count($this->aRequests) > 0){
            $rMultiCurl = curl_multi_init();
            $aCurlHandlers = array();

            foreach ($this->aRequests as $i => $aRequest) {
                $aCurlHandlers[$i] = curl_init($this->sUrl . $aRequest['REQUEST_METHOD']);
                curl_setopt($aCurlHandlers[$i], CURLOPT_HTTPHEADER, array('Accept: application/json'));
                curl_setopt($aCurlHandlers[$i], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($aCurlHandlers[$i], CURLOPT_USERPWD, $this->sLogin . ':' . $this->sPassWord);
                curl_setopt($aCurlHandlers[$i], CURLOPT_POST, true);
                curl_setopt($aCurlHandlers[$i], CURLOPT_POSTFIELDS, $aRequest['REQUEST_PARAMS']);

                curl_setopt($aCurlHandlers[$i], CURLOPT_CONNECTTIMEOUT, 0);

                curl_multi_add_handle($rMultiCurl, $aCurlHandlers[$i]);
            }
            $iStillRunning = null;
            do {
                $iMrc = curl_multi_exec($rMultiCurl, $iStillRunning);
            } while ($iMrc == CURLM_CALL_MULTI_PERFORM);

            while ($iStillRunning && $iMrc == CURLM_OK) {
                do {
                    $iMrc = curl_multi_exec($rMultiCurl, $iStillRunning);
                } while ($iMrc == CURLM_CALL_MULTI_PERFORM);
            }
            foreach($aCurlHandlers as $rCurl){
                curl_multi_remove_handle($rMultiCurl, $rCurl);
            }
            curl_multi_close($rMultiCurl);
            foreach($this->aRequests as $i => $aRequest){
                $this->aRequests[$i]['RESPONSE'] = curl_multi_getcontent($aCurlHandlers[$i]);
                curl_close($aCurlHandlers[$i]);
                unset($aCurlHandlers[$i]);
            }
        }
        $aResult = $this->aRequests;
        $this->aRequests = array();
        return $aResult;
    }

    /**
     * @param $aData
     * @param $bExecute
     * @return mixed
     * @throws \Exception
     */
    public function idControl($aData, $bExecute = true)
    {
        if(false === array_key_exists('files', $aData)){
            throw new \InvalidArgumentException('no files to submit');
        }
        $aResult = $this->addRequest('idcontrol', $aData, !$bExecute);

        if(true == $bExecute){
            $aResult = $this->sendRequests();
        }
        return $aResult;
    }

    /**
     * @param $aData
     * @param $bExecute
     * @return mixed
     * @throws \Exception
     */
    public function ibanFlash($aData, $bExecute = true)
    {
        if(false === array_key_exists('files', $aData)){
            throw new \InvalidArgumentException('no files to submit');
        }
        $aResult = $this->addRequest('ibanflash', $aData, !$bExecute);

        if(true == $bExecute){
            $aResult = $this->sendRequests();
        }
        return $aResult;
    }

    /**
     * @param $aData
     * @param $bExecute
     * @return mixed
     * @throws \Exception
     */
    public function addressControl($aData, $bExecute = true)
    {
        if(false === array_key_exists('files', $aData)){
            throw new \InvalidArgumentException('no files to submit');
        }
        $aResult = $this->addRequest('addresscontrol', $aData, !$bExecute);

        if(true == $bExecute){
            $aResult = $this->sendRequests();
        }
        return $aResult;
    }

    public function kyc($sMethod)
    {

    }

    private function updateCustomer()
    {

    }

    private function deleteCustomer()
    {

    }
}
