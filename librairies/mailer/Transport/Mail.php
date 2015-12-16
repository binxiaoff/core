<?php

namespace Unilend\librairies\Mailer\Transport;

use Exception;
use Unilend\librairies\Mailer\Email;

class Mail extends abstractTransport
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
        // Récupération de l’expéditeur (à faire en premier)
        $sender = $email->getSender();

        // Préparation des en-têtes et du message
        $email = $this->prepareMessage($email);

        // Copy the email object for saving in the mail filer
        $emailCopy = clone $email;

        //
        // Si l’option PHP 'sendmail_path' est vide, cela signifie que PHP
        // ouvre une connexion vers un serveur SMTP défini dans sa configuration.
        //
        $sendmail_path = ini_get('sendmail_path');
        if (!isset($this->opts['php_use_smtp'])) {
            // Si ini_get() est désactivée, on a reçu la valeur null
            $this->opts['php_use_smtp'] = ($sendmail_path !== null && $sendmail_path == '');
        }

        //
        // On récupère les en-têtes Subject et To qui doivent être transmis
        // en argument de la fonction mail().
        //
        $subject    = $email->headers->get('Subject');
        $recipients = $email->headers->get('To');

        if (!is_null($subject)) {
            $subject = $subject->value;
            // La fonction mail() ajoute elle-même l’en-tête Subject
            $email->headers->remove('Subject');
        }

        if (!is_null($recipients)) {
            $recipients = $recipients->value;

            if (!$this->opts['php_use_smtp']) {
                //
                // Sendmail parse les en-têtes To, Cc et Bcc s’ils sont
                // présents pour récupérer la liste des adresses destinataire.
                // On passe déjà la liste des destinataires principaux (To)
                // en argument de la fonction mail().
                //
                $email->headers->remove('To');
            }
        }

        list($headers, $message) = explode("\r\n\r\n", $email->__toString(), 2);

        if (PHP_EOL != "\r\n") {
            $headers = str_replace("\r\n", PHP_EOL, $headers);
            $message = str_replace("\r\n", PHP_EOL, $message);

            /**
             * PHP ne laisse passer les longs entêtes Subject et To que
             * si les plis sont séparés par des séquences <CR><LF><LWS>,
             * cela même sur les systèmes UNIX-like.
             * Cela semble poser problème avec certains MTA qui remplacent les
             * séquences <LF> par <CR><LF> sans vérifier si un <CR> est déjà
             * présent, donnant ainsi une séquence <CR><CR><LF> faussant le
             * marquage de fin de bloc des en-têtes.
             * On normalise quand même les fins de ligne sur les en-têtes
             * subject et to, même si cela signifie que les plis seront remplacés
             * par des espaces sur les systèmes UNIX-like.
             *
             * @see SKIP_LONG_HEADER_SEP routine in
             *      https://github.com/php/php-src/blob/master/ext/standard/mail.c
             * @see PHP Bug 24805 at http://bugs.php.net/bug.php?id=24805
             */
            $subject    = str_replace("\r\n", PHP_EOL, $subject);
            $recipients = str_replace("\r\n", PHP_EOL, $recipients);
        }

        $params = null;

        if ($this->opts['php_use_smtp']) {
            //
            // La fonction mail() utilise prioritairement la valeur de l’option
            // sendmail_from comme adresse à passer dans la commande MAIL FROM
            // (adresse qui sera utilisée par le serveur SMTP pour forger l’entête
            // Return-Path).
            //
            ini_set('sendmail_from', $sender);
        } else {
            if (!ini_get('safe_mode')) {
                $params = ' ' . $this->opts['additional_params'];

                if (!strpos($sendmail_path . $params, ' -f')) {
                    $params .= ' -f' . escapeshellarg($sender);
                }
            }
        }

        set_error_handler(array($this, 'errorHandler'));
        $result = mail($recipients, $subject, $message, $headers, $params);
        $this->saveMessage($emailCopy, isset($this->opts['mail_text_id']) ? $this->opts['mail_text_id'] : '');
        restore_error_handler();

        if ($this->opts['php_use_smtp']) {
            ini_restore('sendmail_from');
        }

        if (!$result) {
            $errorMsg = "Unknown error while sending email.";
            if ($this->$errorMsg) {
                $errorMsg = $this->$errorMsg;
                $this->$errorMsg = null;
            }

            throw new Exception($errorMsg);
        }
    }

    /**
     * Méthode d’appel pour la gestion des erreurs.
     * Activée lors de l’appel à la fonction mail() pour capturer et stocker
     * un éventuel message d’erreur émis par cette dernière.
     *
     * @param integer $errno
     * @param string  $errorMsg
     *
     * @return boolean
     */
    protected function errorHandler($errno, $errorMsg)
    {
        $this->$errorMsg = $errorMsg;
        return true;
    }
}
