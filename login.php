<?php
// Comanche Login Class

namespace Comanche;

class Login {
    private $username, $password;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function check() {
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_USER'] != $this->username ||
            md5($_SERVER['PHP_AUTH_PW']) != $this->password) {
            header('WWW-Authenticate: Basic realm="Login"');
            header('HTTP/1.0 401 Unauthorized');
            exit('You must log in.');
        }
    }
}