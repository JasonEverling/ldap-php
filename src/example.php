<?php
// Copyright (C) 2020  Jason A. Everling
//
//This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

require('classes/Ldap.php');
include('classes/Utils.php');

$ldap_host = '10.10.10.10'; // domain controller ip
$ldap_basedn = 'dc=example,dc=com'; // ad base
$ldap_binduser = 'bind@example.com'; // bind account
$ldap_bindpass = 'PASSWORD'; // bind password

try {
    $AAD = new Ldap($ldap_host, $ldap_basedn, $ldap_binduser, $ldap_bindpass);
} catch (\Exception $error) {
    throw new \Error($error->getMessage());
}

// get single attribute value
// $mail = $AAD->getLdapAttribute('USERNAME', 'mail');

// get all attributes
$attributes = $AAD->getLdapAttributes('USERNAME');

// update the password for a user
// $new_password = $AAD->setPassword('USERNAME', 'NewPassword!');

var_dump($attributes);
