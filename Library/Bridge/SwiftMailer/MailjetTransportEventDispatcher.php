<?php

namespace Unilend\Bridge\SwiftMailer;

class MailjetTransportEventDispatcher extends \Swift_Events_SimpleEventDispatcher
{
    //Parent service is a abstract service (not abstract class), so we need extended it in order to create a service.
    //DO NOT delete it.
}
