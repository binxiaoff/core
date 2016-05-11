<?php

class crongeckoboardController extends bootstrap
{

    var $Command;

    public function initialize()
    {
        parent::initialize();

        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireView = false;
        $this->autoFireDebug = true;

        /* Clé de l'API geckoboard unilend */

        $this->_sGBApiKey = "d0fb59cb37d352ae8f65629bb7e498a6";
    }

    //********************//
    //*** A LA DEMANDE ***//
    //********************//

    function _default()
    {
        die;
    }

    function _pushGeckoBoard($widgetKey,$dataPayload){
        $_rc = false;
        $_sWidgetBaseUrl = "https://push.geckoboard.com/v1/send/";
        $_sWidgetPayload = array();
        $_sWidgetPayload["api_key"] = $this->_sGBApiKey;
        $_sWidgetPayload["data"]  = $dataPayload;

        $oCurl = curl_init($_sWidgetBaseUrl.$widgetKey);
        $jsonPayload = json_encode($_sWidgetPayload,TRUE);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,  $jsonPayload);

        if (!$output = curl_exec($oCurl)){
            throw new Exception("missed curl");
        }else{
            $oOutput = json_decode($output);
            if ($oOutput->success==1)
                $_rc = true;
            else{
                if (isset($oOutput->message)){
                    //print_r(curl_getinfo($oCurl));
                    throw new Exception($oOutput->message);
                }
                else
                    throw new Exception("unknown geckoboard error");
            }
        }


        curl_close($oCurl);
        return $_rc;
    }

    /**

    type geckometer
    https://developer.geckoboard.com/#geck-o-meter
}
    **/

    function _feedLastHourBids(){

        $_sWidgetKey = "151105-df9051b1-47bd-49ec-8616-e9fa9fa52334";
        $_aWidgetData = array();

        $sql = "SELECT count(*) as cnt from bids where added > date_sub(now(), interval 60 minute)";
        $resultat = $this->bdd->query($sql);
        $aRecord = $this->bdd->fetch_array($resultat);

        $_aWidgetData['item'] = (int)$aRecord['cnt'];
        $_aWidgetData['min']['value'] = 0;
        $_aWidgetData['max']['value'] = 100;

        try{
            $rc = $this->_pushGeckoBoard($_sWidgetKey,$_aWidgetData);
        }
        catch(Exception $e){
            echo $e->getMessage();


        }

    }

     /**

    type geckometer
    https://developer.geckoboard.com/#geck-o-meter
}
    **/

    function _feedLastHourBidsDetailed(){

        $_sWidgetKey = "151105-583a43e1-acc7-492e-b3f1-81a6cdd91c6c";
        $_aWidgetData = array();

        $sql = "SELECT minute(added) as m, count(*) as cnt from bids where added > date_sub(now(), interval 60 minute) group by 1 order by added";
        $resultat = $this->bdd->query($sql);
        $total=0;
        $series=array();
        while($aRecord = $this->bdd->fetch_array($resultat)){
            $total+=$aRecord['cnt'];
            $series[]=$aRecord['cnt'];

        }

        $_aWidgetData['item'][] = array('value'=>$total);
        $_aWidgetData['item'][] = array_values($series);
        /*    array(
                'value'=>$total,
                array_values($series)

            );
          */
        try{
            $rc = $this->_pushGeckoBoard($_sWidgetKey,$_aWidgetData);
        }
        catch(Exception $e){
            echo $e->getMessage();


        }

    }

    /*

        Type bar chart
        https://developer.geckoboard.com/#bar-chart

    */

    function _feedGeckoPreteurs(){

        $_sWidgetKey = "151105-c2bb0ae4-3cca-4070-aab1-40e21080684d";
        //$_sWidgetKeyRAG = "151105-0b76b750-d101-426d-bba6-1a307d22ee90";
        $_aWidgetData = array();

        $_sqlPreteurs = "SELECT
                    date_format(c.added, '%d-%m') as dateInscription,


                    (
                        SELECT
                            IF (cshs1.added IS NULL, 'N', 'Y')
                        FROM
                            `clients_status_history` cshs1
                        WHERE
                            cshs1.id_client = c.id_client
                            AND cshs1.id_client_status = 6
                        ORDER BY
                            cshs1.added DESC
                        LIMIT
                            1
                    ) IS NOT NULL as Valide,
                    count(
                        distinct(c.id_client)
                    ) as nbClient
                FROM
                    clients c
                    INNER JOIN lenders_accounts ON c.id_client = lenders_accounts.id_client_owner
                WHERE
                    c.added >= date_sub(now(), interval 7 day)
                    and c.etape_inscription_preteur > 1
                group by
                    1,
                    2
                order by
                    c.added ASC,
                    2 ASC";

        $_aPayload = array();
        $resultat = $this->bdd->query($_sqlPreteurs);
        while ($aRecord = $this->bdd->fetch_array($resultat))
        {
            /* Simplification du graph, on oublie la notion de "en cours de complétude" */
            if ($aRecord['Valide']==1){
                $_aPayload[$aRecord['dateInscription']]+=(int)$aRecord['nbClient'];
            }
        }

        $_aWidgetData['x_axis']['labels']=array_keys($_aPayload);
        $_aWidgetData['series'][]['data']=array_values($_aPayload);

        try{
            $rc = $this->_pushGeckoBoard($_sWidgetKey,$_aWidgetData);
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
    }

    function _feedGeckoFunnel(){


        $_sWidgetKey = "151105-845fe39d-f6b8-433c-b5c7-9bd8c9c6e5a1";
        $_aWidgetData = array();

        $sql = "

        SELECT

          (
            SELECT
                cshs1.id_client_status
            FROM
                clients_status_history cshs1
                /*inner join clients_status cs on cshs1.id_client_status = cs.id_client_status */
            WHERE
                cshs1.id_client = c.id_client
            ORDER BY
                cshs1.added DESC
            LIMIT
                1
        ) as StatusCompletude,

        (
            SELECT
                IF (cshs1.added IS NULL,'N','Y')
            FROM
                `clients_status_history` cshs1
            WHERE
                cshs1.id_client = c.id_client



                AND cshs1.id_client_status = 6
            ORDER BY
                cshs1.added DESC
            LIMIT
                1
        ) IS NOT NULL as Valide,
        status_inscription_preteur,
        count(
            distinct(c.id_client)
        ) as nbClient
    FROM
        clients c
        INNER JOIN lenders_accounts ON c.id_client = lenders_accounts.id_client_owner
    WHERE
        c.added > date_sub(now(),interval 7 day)
        and c.etape_inscription_preteur > 0
    group by
        1,
        2";

        $aCorrespondanceIdLabel = array();
        $aCorrespondanceIdLabel[1] = "A controler";
        $aCorrespondanceIdLabel[2] = "Complétude";
        $aCorrespondanceIdLabel[3] = "Complétude - Relance";
        $aCorrespondanceIdLabel[4] = "Complétude - Réponse";
        $aCorrespondanceIdLabel[5] = "Modification";
        $aCorrespondanceIdLabel[6] = "Valide";


        $resultat = $this->bdd->query($sql);
        $arrayResult = array();
        $iTotalClient = 0;
        $iTotalAvantCGV = 0;
        $iEnCompletude = 0;
        while ($aRecord = $this->bdd->fetch_array($resultat)){
            //$arrayResult
            $iTotalClient+=(int)$aRecord['nbClient'];
            if ($aRecord['StatusCompletude'] == ''){
                $iTotalAvantCGV+= (int)$aRecord['nbClient'];
            }
            switch($aRecord['StatusCompletude']){
                case 2:
                case 3:
                case 4:
                case 5:
                    $iEnCompletude+=(int)$aRecord['nbClient'];
                break;
                case 6:
                    //$iEnCompletude+=(int)$aRecord['nbClient'];
                    $iValide+=(int)$aRecord['nbClient'];
                break;
                default:
                break;

            }



        }
        //$arrayResult[]= array('value'=>$iTotalClient,'label'=>'total');
        $arrayResult[]= array('value'=>$iTotalClient-$iTotalAvantCGV,'label'=>'Prêteurs ayant validé les CGV');
        $arrayResult[]= array('value'=>$iEnCompletude,'label'=>'Prêteurs en Completude');
        $arrayResult[]= array('value'=>$iValide,'label'=>'Prêteurs Valides')    ;

        $_aWidgetData=array();
        $_aWidgetData['item'] = $arrayResult;
        try{
            $rc = $this->_pushGeckoBoard($_sWidgetKey,$_aWidgetData);
        }
        catch(Exception $e){
            echo $e->getMessage();


        }
    }

}?>