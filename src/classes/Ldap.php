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

require('Utils.php');

class Ldap
{
    protected $bind;
    protected $connection;

    public function __construct($ldapHost, $ldapDn, $bindUser, $bindPwd)
    {
        $this->host = $ldapHost;
        $this->ldapDn = $ldapDn;
        $this->bindUser = $bindUser;
        $this->bindPass = $bindPwd;
    }

    public function close() {
        if ($this->connection) {
            @ldap_close($this->connection);
            @ldap_unbind($this->connection);
        }
    }

    public function connect()
    {
        $this->connection = ldap_connect($this->host);
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
        if ($this->bindUser !== NULL && $this->bindPass !== NULL) {
            $this->bind = @ldap_bind($this->connection, $this->bindUser, $this->bindPass);
            if (!$this->bind) {
                die('Bind to Active Directory failed. Error Message: ' . $this->getLdapError());
            }
        }
        if ($this->ldapDn == NULL) {
            $this->baseDn = $this->findLdapBaseDn();
        }
        return true;
    }

    public function findLdapBaseDn()
    {
        $namingContext = $this->getLdapRootDse(['defaultnamingcontext']);
        $result = $namingContext[0]['defaultnamingcontext'][0];
        return $result;
    }

    public function getLdapAttribute($uuid, $attribute, $schema = 'person')
    {
        $attribute = Utils::lc($attribute);
        $attributes = $this->getLdapAttributes($uuid, $schema);
        $result = $attributes[$attribute];
        return $result;
    }

    public function getLdapAttributes($uuid, $schema = 'person', $attributes = [])
    {

        $result = [];
        if (empty($attributes)) {
            $attributes = $this->getLdapAttributeSet($schema);
        }
        $this->connect();
        $con = $this->getLdapConnection();
        $filter = '(sAMAccountName=' . $uuid . ')';
        $sr = ldap_search($con, $this->getLdapBaseDn(), $filter, $attributes);
        $entries = ldap_get_entries($con, $sr);
        $result = $this->getLdapResults($entries);
        $this->close();
        return $result;
    }

    public function getLdapAttributeSet($schema)
    {
        $result = '';
        if ($schema == 'group') {
            $result = Utils::lc([
                'displayname',
                'description',
                'department',
                'cn',
                'mail',
                'distinguishedname',
                'msDS-parentdistname',
                'member',
            ]);
        }
        if ($schema == 'person') {
            $result = Utils::lc([
                'sn',
                'givenName',
                'displayName',
                'cn',
                'mail',
                'telephoneNumber',
                'mobile',
                'employeeID',
                'employeeType',
                'description',
                'department',
                'division',
                'l',
                'otherMailbox',
                'distinguishedName',
                'sAMAccountName',
                'userPrincipalName',
                'msDS-parentdistname',
                'memberOf',
            ]);
        }
        $result = Utils::lc($result);
        return $result;
    }

    public function getLdapBaseDn() {
        return $this->ldapDn;
    }

    public function getLdapBind() {
        return $this->bind;
    }

    public function getLdapBindPwd() {
        return $this->bindPass;
    }

    public function getLdapBindUser() {
        return $this->bindUser;
    }

    public function getLdapConnection() {
        return $this->connection;
    }

    public function getLdapError() {
        return @ldap_error($this->connection);
    }

    public function getLdapHost() {
        return $this->host;
    }

    /**
     * Takes an ugly ldap result and beautifies it
     * @param $entries
     * @return array
     * @todo Fix error reporting for array processing
     */
    public function getLdapResults($entries)
    {
        $result = [];
        $groups = [];

        /* Process groups separately */
        if (isset($entries[0]['memberof'])) {
            for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
                $groups['memberof'][] = $entries[0]['memberof'][$i]++;
            };
            unset($entries[0]['memberof']);
        }
        
        /* Process remainder attributes */
        // Turn of error reporting momentarily
        error_reporting(0);
        
        foreach ($entries as $resIdx => $resEntry) {
            $entry = [];
            foreach ($resEntry as $enKey => $enVal) {
                if (is_numeric($enKey)) {
                    continue;
                }
                if ($enVal[0]) {
                    $entry[$enKey] = $enVal[0];
                }
            }
            if ($entry) {
                $result[0] = $entry;
            }
        }
        // Finished, turn error reporting back on
        error_reporting(E_ALL);
        $result = Utils::arrayMerge($result, $groups);
        return $result;
    }

    public function getLdapRootDse($attributes = ['*', '+']) {
        if (!$this->bind) {
            return (false);
        }
        $sr = @ldap_read($this->connection, NULL, 'objectClass=*', $attributes);
        $result = @ldap_get_entries($this->connection, $sr);
        return $result;
    }

    /** Returns the objects, $uuid, distinguishedName value
     *
     * @param string $uuid the objects sAMAccountName
     * @param string $schema person or group, defaults to person
     * @return string the objects distinguishedName
     */
    public function getObjectDn($uuid, $schema = 'person') {
        return $this->getLdapAttribute($uuid, 'distinguishedname', $schema);
    }

    /** This will replace an attribute value,
     *  it does not use the users credentials,
     *  bind user must have rights to attribute.
     *
     * @param string $uuid The users id
     * @param string $attribute attribute to be set
     * @param string $value value to be set
     * @param string $schema person or group
     * @return bool Returns result, true or false if changed
     */
    public function setAttribute($uuid, $attribute, $value) {
        $this->connect();
        $con = $this->getLdapConnection();
        $user_dn = $this->getObjectDn($uuid);
        $modify_op = [
            [
                'attrib'  => $attribute,
                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                'values'  => [$value],
            ],
        ];
        $result = @ldap_modify_batch($con, $user_dn, $modify_op);
        $this->close();
        return $result;
    }

    /** This will replace the users password,
     *  it does not use the users credentials,
     *  bind user must have rights.
     *
     * @param string $uuid The users id
     * @param string $new_password The new plain text password
     * @return bool Returns result, true or false if changed
     */
    public function setPassword($uuid, $new_password) {
        $unicodePwd = iconv('UTF-8', 'UTF-16LE', '"' . $new_password . '"');
        $result = $this->setAttribute($uuid, 'unicodePwd', $unicodePwd);
        return $result;
    }

    function __destruct(){
        $this->close();
    }

}