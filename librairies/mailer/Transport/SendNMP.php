<?php

namespace Unilend\librairies\Mailer\Transport;

use Exception;
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

        if (isset($this->opts['mails_filer']) && $this->opts['mails_filer'] instanceof \mails_filer
            && isset($this->opts['mails_text']) && $this->opts['mails_text'] instanceof \mails_text
            && isset($this->opts['nmp']) && $this->opts['nmp'] instanceof \nmp
            && isset($this->opts['nmp_desabo']) && $this->opts['nmp_desabo'] instanceof \nmp_desabo) {
            // On creer la ligne du filer
            $this->saveMessage($email, $this->opts['mails_filer'], isset($this->opts['mails_text']) ? $this->opts['mails_text']->id_textemail : '');

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
            if (!$this->opts['nmp_desabo']->get($this->opts['mails_filer']->email_nmp, 'email') || $this->opts['mails_text']->mode == 0) {
                $varDyn = array();

                foreach ($this->opts['mail_var'] as $key => $value) {
                    $varDyn['entry'][] = array('key' => $key, 'value' => $value);
                }

                $varDyn['entry'][] = array(
                    'key' => 'miroir',
                    'value' => '/miroir/' . $this->opts['mails_filer']->id_filermails . '/' . md5($this->opts['mails_filer']->id_textemail)
                );
                $varDyn['entry'][] = array(
                    'key' => 'desabo',
                    'value' => '/removeNMP/' . $this->opts['mails_filer']->desabo . '/' . $this->opts['mails_filer']->id_filermails . '/' . $this->opts['mails_filer']->email_nmp
                );

                $arg0['arg0'] = array(
                    'content' => array(),
                    'dyn' => $varDyn,
                    'email' => $this->opts['mails_filer']->email_nmp,
                    'encrypt' => $this->opts['mails_text']->nmp_secure,
                    'notificationId' => $this->opts['mails_text']->id_nmp,
                    'random' => $this->opts['mails_text']->nmp_unique,
                    'senddate' => date('Y-m-d'),
                    'synchrotype' => 'NOTHING',
                    'uidkey' => 'EMAIL'
                );

                $this->opts['nmp']->serialize_content = serialize($arg0);
                $this->opts['nmp']->date              = date('Y-m-d');
                $this->opts['nmp']->mailto            = $this->opts['mails_filer']->email_nmp;
                $this->opts['nmp']->status            = 0;
                $this->opts['nmp']->create();
            }
        }
    }
}
