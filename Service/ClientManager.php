<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 22/01/2016
 * Time: 15:33
 */

namespace Unilend\Service;


use Unilend\core\Loader;

class ClientManager
{
    /** @var \clients */
    private $oClient;
    /** @var \ficelle */
    private $oFicelle;

    public function __construct()
    {
        $this->oClient = Loader::loadData('clients');

        $this->oFicelle    = Loader::loadLib('ficelle');
    }

    public function getClientTransferPurpose($iClientId)
    {
        if ($this->oClient->get($iClientId)) {
            $sFirstName  = substr($this->oFicelle->stripAccents(utf8_decode(trim($this->oClient->prenom))), 0, 1);
            $sName       = $this->oFicelle->stripAccents(utf8_decode(trim($this->oClient->nom)));
            $sTruncateId = str_pad($this->oClient->id_client, 6, 0, STR_PAD_LEFT);
            $sPurpose    = mb_strtoupper($sTruncateId . $sFirstName . $sName, 'UTF-8');

            return $sPurpose;
        }

        return '';
    }
}