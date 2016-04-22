<?php
namespace Unilend\Service\Mailer;

use \Swift_Mailer;
use \mails_text;

class Mailer
{
    /** @var mails_text */
    private $oMailTemplate;
    /** @var Swift_Mailer */
    private $oMailer;
    /** @var TemplateMessage */
    private $oMessage;

    public function __construct(mails_text $oMailTemplate, Swift_Mailer $oMailer, TemplateMessage $oMessage)
    {
        $this->oMailTemplate = $oMailTemplate;
        $this->oMailer       = $oMailer;
        $this->oMessage      = $oMessage;
    }

    /**
     * @param      $sTemplate
     * @param      $sLanguage
     * @param null $aVariables
     *
     * @return \Swift_Mime_MimePart
     * @throws \Exception
     */
    public function newMessage($sTemplate, $sLanguage, $aVariables = null, $bWrap = true)
    {
        if (false === $this->oMailTemplate->get($sTemplate, 'lang = "' . $sLanguage . '" AND type')) {
            throw new \Exception('The mail template ' . $sTemplate . ' for the language ' . $sLanguage . 'is not found.');
        }

        if ($bWrap) {
            $this->wrapVariables($aVariables);
        }

        $sMailSubject  = strtr($this->oMailTemplate->subject, $aVariables);
        $sMailContent  = strtr($this->oMailTemplate->content, $aVariables);
        $sMailFromName = strtr($this->oMailTemplate->exp_name, $aVariables);

        $oMessage = $this->oMessage->newInstance()
                                   ->setSubject($sMailSubject)
                                   ->setFrom($this->oMailTemplate->exp_email, $sMailFromName)
                                   ->setBody($sMailContent, 'text/html')
                                   ->setVariable($aVariables);
        return $oMessage;
    }

    /**
     * @param $oMessage
     */
    public function send(Swift_Message $oMessage)
    {
        $this->oMailer->send($oMessage);
    }

    /**
     * @param        $aVariables
     * @param string $sPrefix
     * @param string $sSuffix
     *
     * @return mixed
     */
    private function wrapVariables($aVariables, $sPrefix = '[EMV DYN]', $sSuffix = '[EMV /DYN]')
    {
        foreach ($aVariables as $key => $value) {
            $aVariables[$sPrefix . $key . $sSuffix] = $value;
        }

        return $aVariables;
    }
}