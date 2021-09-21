<?php

namespace app\models;

use app\components\LdapAuthenticator;
use Yii;
use yii\base\NotSupportedException;

/**
 * Defines an authentication mechanism with the Neptun academic registry system through SimpleSAMLphp.
 * The Neptun system is used at several hungarian universities.
 *
 * @property-read string $id The Neptun code of the user, which uniquely represents the identity.
 * @property-read string $name The display name for the identity.
 * @property-read string $email The email address for the identity.
 * @property-read string $isStudent The student permission level for the identity.
 * @property-read string $isTeacher The teacher permission level for the identity.
 * @property-read string $isAdmin The administrator permission level for the identity.
 * @property-read boolean $isAuthenticated Whether the identity is valid.
 */
class LdapAuth extends \yii\base\BaseObject implements AuthInterface
{
    private static $_isSupported = null;

    public static function isSupported()
    {
        if (is_null(self::$_isSupported)) {
            self::$_isSupported = function_exists('ldap_connect');
        }
        return self::$_isSupported;
    }

    /**
     * @var LdapAuthenticator Stores the LDAP authenticator.
     */
    private $_ldap;
    /**
     * @var array Stores the received attributes of the identity.
     */
    private $_attributes = [];
    /**
     * @var string Username.
     */
    public $username;
    /**
     * @var string User password to authenticate.
     */
    public $password;

    /**
     * Constructs a new instance.
     */
    public function __construct(
        $host,
        $bindDN,
        $bindPasswd,
        $baseDN,
        $uidAttr
    ) {
        if (!static::isSupported()) {
            throw new NotSupportedException('LDAP is not supported.');
        }

        $this->_ldap = new LdapAuthenticator($host, $bindDN, $bindPasswd, $baseDN, $uidAttr);
    }

    /**
     * Returns the Neptun code of the user.
     * The Neptun code uniquely represents the identity.
     * @return string The Neptun code of the user.
     */
    public function getId()
    {
        return $this->l;
    }

    /**
     * Returns the display name for the identity.
     * @return string The name of the user.
     */
    public function getName()
    {
        return $this->displayname;
    }

    public function getEmail()
    {
        return $this->mail;
    }

    public function getIsStudent()
    {
        return strpos($this->distinguishedname, 'OU=Tanulok') !== false;
    }

    public function getIsTeacher()
    {
        return strpos($this->distinguishedname, 'OU=Oktatok') !== false;
    }

    public function getIsAdmin()
    {
        return false;
    }

    /**
     * Returns a value indicating whether the identity is authenticated.
     * @return boolean Whether the identity is valid.
     */
    public function getIsAuthenticated()
    {
        return !empty($this->_attributes);
    }

    /**
     * Authenticates the user.
     *
     * This method must succeed if returns.
     * @param string $returnUrl The URL to return the user to after login.
     * @return boolean True if the authentication succeeded, otherwise false.
     */
    public function login($returnUrl = null)
    {
        $this->_attributes = $this->_ldap->auth($this->username, $this->password);
        return $this->isAuthenticated;
    }

    /**
     * Log out the current user with the given return URL.
     * @param string $returnUrl The URL to return the user to after the logout.
     */
    public function logout($returnUrl = null)
    {
        $this->_attributes = [];
    }

    /**
     * Checks if a property value is null.
     * @param string $name Name of the property.
     * @return boolean True if the property is not null, otherwise false.
     * @see __get
     */
    public function __isset($name)
    {
        return isset($this->_attributes[$name]) || parent::__isset($name);
    }

    /**
     * Returns the value of a property.
     * @param string $name Name of the property.
     * @return mixed The value of the property.
     * @see __isset
     */
    public function __get($name)
    {
        if (!isset($this->_attributes[$name])) {
            return parent::__get($name);
        }
        if (is_array($this->_attributes[$name]) && count($this->_attributes[$name]) == 1) {
            return $this->_attributes[$name][0];
        } else {
            return $this->_attributes[$name];
        }
    }
}
