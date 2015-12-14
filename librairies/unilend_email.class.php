<?php

use Unilend\librairies\Mailer\Email;
use Unilend\librairies\Mailer\Mailer;

/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 30/11/2015
 * Time: 14:40
 */
class unilend_email
{
    /** @var mails_filer */
    private $oMailFiler = null;

    /** @var mails_text */
    private $oMailText = null;

    /** @var nmp */
    private $oNmp = null;

    /** @var nmp_desabo */
    private $oNmpDesabo = null;

    private $aMailVar = array();

    private $aRecipient = array();

    private $aCCRecipient = array();

    private $aBCCRecipient = array();

    private $aReplyTo = array();

    private $oEmail = null;

    /**
     * unilend_email constructor.
     *
     * @param $aAttributes
     */
    public function __construct($aAttributes)
    {
        $this->oMailFiler = $aAttributes[0];
        $this->oMailText  = $aAttributes[1];

        if (isset($aAttributes[2])) {
            $this->oNmp = $aAttributes[2];
        }

        if (isset($aAttributes[3])) {
            $this->oNmpDesabo = $aAttributes[3];
        }

        $this->oEmail = new Email();
    }

    /**
     * @throws Exception
     */
    public function sendFromTemplate()
    {
        $this->prepareEmailFromTemplate();

        $aParams = array(
            'mails_filer' => $this->oMailFiler,
            'mails_text' => $this->oMailText
        );

        if (ENVIRONMENT === 'prod') {
            $aParams['nmp']        = $this->oNmp;
            $aParams['nmp_desabo'] = $this->oNmpDesabo;
            $aParams['mail_var']   = $this->aMailVar;

            Mailer::setTransport('sendnmp', $aParams);
        } else {
            Mailer::setTransport('mail', $aParams);
        }

        $this->send();
    }

    public function sendToStaff()
    {
        $this->prepareEmailFromTemplate();

        $aParams = array(
            'mails_filer' => $this->oMailFiler,
            'mails_text' => $this->oMailText
        );
        Mailer::setTransport('mail', $aParams);

        $this->send();
    }

    /**
     * @param $sKey
     * @param $sValue
     */
    public function addMailVar($sKey, $sValue)
    {
        $this->aMailVar[$sKey] = $sValue;
    }

    public function addAllMailVars($aVariable)
    {
        $this->aMailVar = array_merge($this->aMailVar, $aVariable);
    }

    /**
     * @param $sPrefix
     * @param $sSuffix
     */
    private function wrapVariables($sPrefix = '[EMV DYN]', $sSuffix = '[EMV /DYN]')
    {
        foreach ($this->aMailVar as $key => $value) {
            $this->aMailVar[$sPrefix . $key . $sSuffix] = $value;
            unset($this->aMailVar['$key']);
        }
    }

    public function setTemplate($sMailType, $sLanguage)
    {
        if (false === $this->oMailText->get($sMailType, 'lang = "' . $sLanguage . '" AND type')) {
            throw new \Exception('The mail template ' . $sMailType . 'is not found.');
        }
    }

    private function prepareEmailFromTemplate()
    {
        if (!$this->oMailText->id_textemail) {
            throw new \Exception('The mail template is not defined.');
        }

        if (empty($this->aRecipient)) {
            throw new \Exception('No recipient');
        }

        if (ENVIRONMENT !== 'prod') {
            $this->wrapVariables();

            // @todo once mailcatcher is installed on every dev/demo, email domain check may be deleted (not subject prefixing)
            foreach ($this->aRecipient as $iIndex => $sRecipient) {
                if (1 !== preg_match('/@unilend.fr$/', $sRecipient)) {
                    unset($this->aRecipient[$iIndex]);
                }
            }

            if (empty($this->aRecipient)) {
                $this->aRecipient[] = 'test-' . ENVIRONMENT . '@unilend.fr';
            }

            $this->oMailText->subject = '[' . ENVIRONMENT . '] ' . $this->oMailText->subject;
        }

        $sMailSubject = strtr($this->oMailText->subject, $this->aMailVar);
        $sMailContent = strtr($this->oMailText->content, $this->aMailVar);
        $sMailFrom    = strtr($this->oMailText->exp_name, $this->aMailVar);

        $this->oEmail->setFrom($this->oMailText->exp_email, $sMailFrom);
        $this->oEmail->setSubject(stripslashes($sMailSubject));
        $this->oEmail->setHTMLBody(stripslashes($sMailContent));

        return $this->oEmail;
    }

    private function send()
    {
        Mailer::send($this->oEmail);
        $this->aMailVar = array();
        $this->oEmail = new Email();
    }

    public function __call($sMethod, $aArgument)
    {
        if (!method_exists($this->oEmail, $sMethod)) {
            throw new Exception("The [$sMethod] is not defined in Email class");
        }

        return call_user_func_array(
            array($this->oEmail, $sMethod),
            $aArgument
        );
    }
}