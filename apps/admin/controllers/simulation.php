<?php

class simulationController extends bootstrap
{
    public function simulationController(&$command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;
    }

    public function _default()
    {
        header("location:".$this->lurl);
    }

    function _altares()
    {
        ini_set('default_socket_timeout', 60);

        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;

        $url = 'http://iws-sffpme.edgeteam.fr/services/MozaikEligibilityObject?wsdl';

        //Creation objet Soap
        $client = new SoapClient($url);
        //print_r($client->__getFunctions());
        var_dump($client->__getFunctions());
        //print_r($client->__getTypes());
        // Appel WS

        $siren = '';
        if (isset($_POST['siren']) && $_POST['siren'] != '') {
            $siren = $_POST['siren'];
        }
        $result = $client->__soapCall("getEligibility", array(
            array(
                "identification"=>"U2012008557|45c8586a626ddabd233951066138d0efa7f4eb9d",
                "refClient"=>"unilend",
                "siren"=>"$siren"
            )
        ));

        ?>
        <form action="" method="post">
            <table>
                <tr>
                    <td><label>siren : </label></td>
                    <td><input type="text" name="siren" value="<?=(isset($_POST['siren'])?$_POST['siren']:'')?>"/></td>
                </tr>
                <tr>
                    <td><label>valider : </label></td>
                    <td><input type="submit" name="send" value="Valider"/></td>
                </tr>
            </table>
        </form>
        <?
        echo '<pre>';
        print_r($result->return);
        echo '</pre>';
    }
}