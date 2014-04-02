<?php

namespace Bangpound\Bundle\DrupalBundle;

class SwiftMailSystem extends \Bangpound\Bridge\Drupal\SwiftMailSystem {

    /**
     *
     */
    public function __construct()
    {
        /** @var \Swift_Mailer $mailer */
        $mailer = \Drufony::get('mailer');
        parent::__construct($mailer);
    }
}
