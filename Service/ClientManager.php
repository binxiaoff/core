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
    public static function getClientTransferPurpose(\clients $oClient)
    {
        /** @var \ficelle $oFicelle */
        $oFicelle    = Loader::loadLib('ficelle');
        $sFirstName  = substr($oFicelle->stripAccents(utf8_decode(trim($oClient->prenom))), 0, 1);
        $sName       = $oFicelle->stripAccents(utf8_decode(trim($oClient->nom)));
        $sTruncateId = str_pad($oClient->id_client, 6, 0, STR_PAD_LEFT);
        $sPurpose    = mb_strtoupper($sTruncateId . $sFirstName . $sName, 'UTF-8');

        return $sPurpose;
    }
}