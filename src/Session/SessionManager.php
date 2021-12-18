<?php
namespace Ostoandel\Session;

class SessionManager extends \Illuminate\Session\SessionManager
{

    protected function createCakeDriver()
    {
        return new CakeStore($this->config->get('session'), new \SessionHandler());
    }

}
