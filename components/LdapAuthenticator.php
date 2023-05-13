<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 Máté Cserép, https://codenet.hu
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace app\components;

/**
 * LDAP authenticator class.
 * @author Máté Cserép <mcserep@gmail.com>
 * @version 1.1
 * @link https://gist.github.com/mcserep/65db3c6e507a16c58458d6c14ea57e09
 */
class LdapAuthenticator
{
    public $host;
    public $bindDN;
    public $bindPasswd;
    public $baseDN;
    public $uidAttr;

    public function __construct($host, $bindDN, $bindPasswd, $baseDN, $uidAttr = 'sAMAccountName')
    {
        $this->host = $host;
        $this->bindDN = $bindDN;
        $this->bindPasswd = $bindPasswd;
        $this->baseDN = $baseDN;
        $this->uidAttr = $uidAttr;
    }

    public function auth($username, $passwd)
    {
        if (!function_exists('ldap_connect')) {
            throw new \RuntimeException('LDAP module not enabled for PHP.');
        }

        $ds = ldap_connect($this->host);
        if (!$ds) return false;

        if (!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3))
            return false;

        if (!ldap_start_tls($ds))
            return false;

        $bind = @ldap_bind($ds, $this->bindDN, $this->bindPasswd);
        if (!$bind) return false;

        $filter = $this->uidAttr.'='.ldap_escape($username, "", LDAP_ESCAPE_FILTER);
        $sr = ldap_search($ds, $this->baseDN, $filter);
        $entries = ldap_get_entries($ds, $sr);

        if ($entries['count'] != 1)
            return false;
        if ($entries[0]['distinguishedname']['count'] != 1)
            return false;

        if (strlen(trim($passwd)) == 0)
            return false;

        $bind = @ldap_bind($ds, $entries[0]['distinguishedname'][0], $passwd);
        if (!$bind) return false;

        ldap_close($ds);

        $result = [];
        foreach (array_keys($entries[0]) as $key) {
            if (isset($entries[0][$key]['count'])) {
                if ($entries[0][$key]['count'] == 1)
                    $result[$key] = $entries[0][$key][0];
                else {
                    $result[$key] = [];
                    for ($i = 0; $i < $entries[0][$key]['count']; ++$i)
                        $result[$key][$i] = $entries[0][$key][$i];
                }
            }
        }
        return $result;
    }
}
