<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Unilend\Service\Simulator\EntityManager;

class TemplateMessageProvider
{
    /** @var EntityManager */
    private $oEntityManager;
    private $sTemplateMessageClass;

    public function __construct(EntityManager $oEntityManager, $sTemplateMessageClass)
    {
        $this->oEntityManager        = $oEntityManager;
        $this->sTemplateMessageClass = $sTemplateMessageClass;
    }

    public function newMessage($sTemplate, $sLanguage, $aVariables = null, $bWrapVariables = true)
    {
        /** @var \mails_text $oMailTemplate */
        $oMailTemplate = $this->oEntityManager->getRepository('mails_text');
        if (false === $oMailTemplate->get($sTemplate, 'lang = "' . $sLanguage . '" AND type')) {
            throw new \Exception('The mail template ' . $sTemplate . ' for the language ' . $sLanguage . 'is not found.');
        }

        if ($bWrapVariables) {
            $aVariables = $this->wrapVariables($aVariables);
        }

        $sSubject  = strtr($oMailTemplate->subject, $aVariables);
        $sBody     = strtr($oMailTemplate->content, $aVariables);
        $sFromName = strtr($oMailTemplate->exp_name, $aVariables);

        /** @var TemplateMessage $oMessage */
        $oMessage = new $this->sTemplateMessageClass($oMailTemplate->id_textemail);
        $oMessage->setVariables($aVariables)
                 ->setFrom($oMailTemplate->exp_email, $sFromName)
                 ->setSubject($sSubject)
                 ->setBody($sBody, 'text/html');

        return $oMessage;
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
        $aVariablesWrapped = [];
        foreach ($aVariables as $key => $value) {
            $aVariablesWrapped[$sPrefix . $key . $sSuffix] = $value;
        }

        return $aVariablesWrapped;
    }
}
