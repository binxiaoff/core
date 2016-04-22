<?php

namespace Unilend\Service\Mailer;

use \Swift_Message;

class TemplateMessage extends Swift_Message
{
    private $variables;

    public function __construct($subject = null, $body = null, $variables = null, $contentType = null, $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);

        if ($variables) {
            $this->setVariable($variables);
        }
    }

    public static function newInstance($subject = null, $body = null, $variables = null, $contentType = null, $charset = null)
    {
        return new self($subject, $body, $variables = null, $contentType, $charset);
    }
    
    public function setVariable($variables)
    {
        $this->variables = $variables;
        return $this;
    }
}