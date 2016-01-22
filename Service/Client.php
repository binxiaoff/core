<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 22/01/2016
 * Time: 15:33
 */

namespace Unilend\Service;


use Unilend\core\Loader;

class Client
{
    /** @var \clients */
    private $oClient;

    public function __construct($aParams)
    {
        if (isset($aParams['id_client'])) {
            $this->oClient = Loader::loadData('clients');
            $this->oClient->get($aParams['id_client']);
        }
    }

    public function getClientTransferPurpose()
    {
        /** @var \ficelle $oFicelle */
        $oFicelle    = Loader::loadLib('ficelle');
        $sFirstName  = substr($oFicelle->stripAccents(utf8_decode(trim($this->oClient->prenom))), 0, 1);
        $sName       = $oFicelle->stripAccents(utf8_decode(trim($this->oClient->nom)));
        $sTruncateId = str_pad($this->oClient->id_client, 6, 0, STR_PAD_LEFT);
        $sPurpose    = mb_strtoupper($sTruncateId . $sFirstName . $sName, 'UTF-8');

        return $sPurpose;
    }
}