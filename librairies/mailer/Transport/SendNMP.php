<?php

namespace Unilend\librairies\Mailer\Transport;

use Exception;
use Unilend\librairies\Data;
use Unilend\librairies\Mailer\Email;

/**
 * Class SendNMP
 * @package Unilend\librairies\Mailer\Transport
 */
class SendNMP extends abstractTransport
{
    /**
     * Tableau d’options pour ce transport.
     *
     * @var array
     */
    protected $opts = array(
        /**
         * Correspond au 5e argument de la fonction mail().
         * Si l’option -f n’est pas présente, elle sera ajoutée automatiquement.
         * Ignoré si le safe mode est activé.
         *
         * @var string
         */
        'additional_params' => '',

        /**
         * Utilisée pour définir si la fonction mail() de PHP utilise un MTA
         * local (Sendmail ou équivalent) ou bien établit une connexion vers
         * un serveur SMTP défini dans sa configuration.
         * Si laissée vide, cette option est définie ultérieurement en
         * vérifiant la valeur retournée par ini_get('sendmail_path').
         *
         * @see self::send()
         * @var boolean
         */
        'php_use_smtp' => null
    );

    /**
     * Stocke le message d’erreur émis par la fonction mail().
     *
     * @var string
     */
    protected $errstr;

    /**
     * Traitement/envoi d’un email.
     *
     * @param Email $email
     *
     * @throws Exception
     */
    public function send(Email $email)
    {
        $email = $this->prepareMessage($email);

        $recipients = $email->headers->get('To')->value;
        $aRecipients = explode(',', $recipients);
        if (count($aRecipients) > 1) {
            throw new Exception("The Transport NMP doesn't support multi-recipients");
        }

        $oNMP = Data::loadData('nmp');
        $oNMPDesabo = Data::loadData('nmp_desabo');

        if ($oNMP instanceof \nmp && $oNMPDesabo instanceof \nmp_desabo
            && isset($this->opts['nmp_secure'], $this->opts['id_nmp'], $this->opts['nmp_unique'], $this->opts['mail_text_id'], $this->opts['mail_text_mode'])
        ) {
            // On creer la ligne du filer
            $oMailsFiler = $this->saveMessage($email, $this->opts['mail_text_id']);

            if (ENVIRONMENT === 'demo') {
                // pas d'enregistrement nmp de minuit a 6h du matin pour la demo
                $debut = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                $fin   = mktime(6, 0, 0, date("m"), date("d"), date("Y"));

                if (time() >= $debut && time() <= $fin) {
                    //echo 'stop';
                    die;
                }
            }

            // Send mail to those who aren't unsubscribed
            if ($oMailsFiler instanceof \mails_filer && !$oNMPDesabo->exist($oMailsFiler->email_nmp, 'email') || $this->opts['mail_text_mode'] == 0) {
                $aVarDyn = array();

                foreach ($this->opts['mail_var'] as $key => $value) {
                    $aVarDyn['entry'][] = array('key' => $key, 'value' => $value);
                }

                $aVarDyn['entry'][] = array(
                    'key' => 'miroir',
                    'value' => '/miroir/' . $oMailsFiler->id_filermails . '/' . md5($oMailsFiler->id_textemail)
                );
                $aVarDyn['entry'][] = array(
                    'key' => 'desabo',
                    'value' => '/removeNMP/' . $oMailsFiler->desabo . '/' . $oMailsFiler->id_filermails . '/' . $oMailsFiler->email_nmp
                );

                $arg0['arg0'] = array(
                    'content' => array(),
                    'dyn' => $aVarDyn,
                    'email' => $oMailsFiler->email_nmp,
                    'encrypt' => $this->opts['nmp_secure'],
                    'notificationId' => $this->opts['id_nmp'],
                    'random' => $this->opts['nmp_unique'],
                    'senddate' => date('Y-m-d'),
                    'synchrotype' => 'NOTHING',
                    'uidkey' => 'EMAIL'
                );

                $oNMP->serialize_content = serialize($arg0);
                $oNMP->date              = date('Y-m-d');
                $oNMP->mailto            = $oMailsFiler->email_nmp;
                $oNMP->status            = 0;
                $oNMP->create();
            }
        }
    }
}
