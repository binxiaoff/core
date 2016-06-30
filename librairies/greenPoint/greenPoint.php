<?php

namespace Unilend\librairies\greenPoint;

use Unilend\core\Loader;

class greenPoint
{
    const TEST_URL = 'https://id-control.fr/api/';
    const PROD_URL = 'https://beprems.pro/api/';

    /** @var string */
    private $sPassWord;

    /** @var string */
    private $sLogin;

    /** @var string */
    private $sUrl;

    /** @var array */
    private $aRequests;

    /** @var \settings settings */
    private $oSettings;

    /** @var string */
    private $sRequestMethod;

    /** @var int */
    private $iCustomerId;

    /** @var array */
    private $aConfig;

    public function __construct($environment)
    {
        $this->iCustomerId = '';
        $this->aConfig     = Loader::loadConfig();
        $this->oSettings   = Loader::loadData('settings');

        switch ($environment) {
            case 'prod':
                $this->sUrl = self::PROD_URL;

                $this->oSettings->get('green_point_pw_prod', 'type');
                $this->sPassWord = $this->oSettings->value;

                $this->oSettings->get('green_point_login_prod', 'type');
                $this->sLogin = $this->oSettings->value;
                break;
            default:
                $this->sUrl = self::TEST_URL;

                $this->oSettings->get('green_point_pw_test', 'type');
                $this->sPassWord = $this->oSettings->value;

                $this->oSettings->get('green_point_login_test', 'type');
                $this->sLogin = $this->oSettings->value;
                break;
        }
    }

    /**
     * @param string $sMethod
     * @param array $aRequestParams
     * @param bool $bReturnQueryId
     * @return $this|int
     */
    private function addRequest($sMethod, array $aRequestParams, $bReturnQueryId = false)
    {
        $iRequestId                                     = count($this->aRequests);
        $this->aRequests[$iRequestId]['REQUEST_PARAMS'] = $aRequestParams;
        $this->aRequests[$iRequestId]['REQUEST_METHOD'] = $sMethod;

        if (true === $bReturnQueryId) {
            return $iRequestId;
        } else {
            return $this;
        }
    }

    /**
     * @return array
     */
    public function sendRequests()
    {
        if (count($this->aRequests) > 0) {
            $rMultiCurl    = curl_multi_init();
            $aCurlHandlers = array();

            foreach ($this->aRequests as $iIndex => $aRequest) {
                $sUrl = $this->sUrl . $aRequest['REQUEST_METHOD'];
                if (false === empty($this->iCustomerId)) {
                    $sUrl .= '/' . $this->iCustomerId;
                }
                $aCurlHandlers[$iIndex] = curl_init($sUrl);
                curl_setopt($aCurlHandlers[$iIndex], CURLOPT_HTTPHEADER, array('Accept: application/json'));
                curl_setopt($aCurlHandlers[$iIndex], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($aCurlHandlers[$iIndex], CURLOPT_USERPWD, $this->sLogin . ':' . $this->sPassWord);
                curl_setopt($aCurlHandlers[$iIndex], CURLOPT_POSTFIELDS, $aRequest['REQUEST_PARAMS']);
                curl_setopt($aCurlHandlers[$iIndex], CURLOPT_CUSTOMREQUEST, $this->sRequestMethod);
                curl_setopt($aCurlHandlers[$iIndex], CURLOPT_CONNECTTIMEOUT, 0);
                curl_multi_add_handle($rMultiCurl, $aCurlHandlers[$iIndex]);
            }
            $iStillRunning = null;
            do {
                $iMrc = curl_multi_exec($rMultiCurl, $iStillRunning);
            } while ($iMrc == CURLM_CALL_MULTI_PERFORM);

            while ($iStillRunning && $iMrc == CURLM_OK) {
                /**
                 * When curl_multi_select() returns -1, then halt the script for a little while berfore running curl_multi_exec()
                 * https://bugs.php.net/bug.php?id=61141
                 */
                if (curl_multi_select($rMultiCurl) != -1) {
                    usleep(100);
                }
                do {
                    $iMrc = curl_multi_exec($rMultiCurl, $iStillRunning);
                } while ($iMrc == CURLM_CALL_MULTI_PERFORM);
            }

            foreach ($aCurlHandlers as $rCurl) {
                curl_multi_remove_handle($rMultiCurl, $rCurl);
            }
            curl_multi_close($rMultiCurl);

            foreach ($this->aRequests as $iIndex => $aRequest) {
                $this->aRequests[$iIndex]['RESPONSE'] = curl_multi_getcontent($aCurlHandlers[$iIndex]);
                curl_close($aCurlHandlers[$iIndex]);
                unset($aCurlHandlers[$iIndex]);
            }
        }
        $aResult         = $this->aRequests;
        $this->aRequests = array();
        return $aResult;
    }

    /**
     * @param array $aData
     * @param bool $bExecute
     * @return int|array|greenPoint
     */
    public function idControl(array $aData, $bExecute = true)
    {
        if (false === array_key_exists('files', $aData)) {
            throw new \InvalidArgumentException('no files to submit');
        }
        $this->setCustomOptions('POST');
        $mResult = $this->addRequest('idcontrol', $aData, ! $bExecute);

        if (true == $bExecute) {
            $mResult = $this->sendRequests();
        }
        return $mResult;
    }

    /**
     * @param array $aData
     * @param bool $bExecute
     * @return int|array|greenPoint
     */
    public function ibanFlash(array $aData, $bExecute = true)
    {
        if (false === array_key_exists('files', $aData)) {
            throw new \InvalidArgumentException('no files to submit');
        }
        $this->setCustomOptions('POST');
        $mResult = $this->addRequest('ibanflash', $aData, ! $bExecute);

        if (true == $bExecute) {
            $mResult = $this->sendRequests();
        }
        return $mResult;
    }

    /**
     * @param array $aData
     * @param bool $bExecute
     * @return int|array|greenPoint
     */
    public function addressControl(array $aData, $bExecute = true)
    {
        if (false === array_key_exists('files', $aData)) {
            throw new \InvalidArgumentException('no files to submit');
        }
        $mResult = $this->addRequest('addresscontrol', $aData, ! $bExecute);
        $this->setCustomOptions('POST');

        if (true == $bExecute) {
            $mResult = $this->sendRequests();
        }
        return $mResult;
    }

    /**
     * @param array $aData
     * @param bool $bExecute
     * @throws \InvalidArgumentException
     * @return int|array|greenPoint
     */
    public function createCustomer(array $aData, $bExecute = true)
    {
        if (empty($aData['dossier'])) {
            throw new \InvalidArgumentException('Missing Mandatory parameter');
        }
        $this->setCustomOptions('POST');
        $mResult = $this->addRequest('kyc', $aData, ! $bExecute);

        if (true == $bExecute) {
            $mResult = $this->sendRequests();
        }
        return $mResult;
    }

    /**
     * @param array $aData
     * @throws \InvalidArgumentException
     * @return array
     */
    public function updateCustomer(array $aData)
    {
        if (empty($aData['dossier'])) {
            throw new \InvalidArgumentException('Missing Mandatory parameter');
        }
        $this->setCustomOptions('PUT', $aData['dossier']);
        return $this->addRequest('kyc', $aData, false)->sendRequests();
    }

    /**
     * @param $iCustomerId
     * @throws \InvalidArgumentException
     * @return array
     */
    public function deleteCustomer($iCustomerId)
    {
        if (empty($aData['dossier'])) {
            throw new \InvalidArgumentException('Missing Mandatory parameter');
        }
        $this->setCustomOptions('DELETE', $iCustomerId);
        return $this->addRequest('kyc', array(), false)->sendRequests();
    }

    /**
     * @param int $iCustomerId
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getCustomer($iCustomerId)
    {
        if (empty($iCustomerId)) {
            throw new \InvalidArgumentException('Missing Mandatory parameter');
        }
        $this->setCustomOptions('GET', $iCustomerId);
        return $this->addRequest('kyc', array(), false)->sendRequests();
    }

    /**
     * @param string $sMethod
     * @param int|null $iCustomerId
     */
    private function setCustomOptions($sMethod, $iCustomerId = null)
    {
        $this->sRequestMethod = $sMethod;
        $this->iCustomerId    = $iCustomerId;
    }
}
