<?php

namespace app\models;

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
 * @property-read string $loginURL The login URL provided by SimpleSAMLphp.
 * @property-read string $logoutURL The logout URL provided by SimpleSAMLphp.
 */
class NeptunAuth extends \yii\base\BaseObject implements RemoteAuthInterface
{
    private static $_isSupported = null;

    public static function isSupported()
    {
        if (is_null(self::$_isSupported)) {
            try {
                if (file_exists(Yii::getAlias('@simplesamlphp'))) {
                    include_once Yii::getAlias('@simplesamlphp') . '/lib/_autoload.php';
                    include_once Yii::getAlias('@simplesamlphp') . '/lib/SimpleSAML/Auth/Simple.php';
                }
            } catch (yii\base\InvalidArgumentException $e) {
                // alias @simplesamlphp not defined
            }

            self::$_isSupported = class_exists('\SimpleSAML_Auth_Simple');
        }
        return self::$_isSupported;
    }

    /**
     * @var SimpleSAML_Auth_Simple Stores the SAML authenticator.
     */
    private $_simpleSaml;
    /**
     * @var array Stores the received attributes of the identity.
     */
    private $_attributes = [];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        if (!static::isSupported()) {
            throw new NotSupportedException('SAML is not supported.');
        }

        $this->_simpleSaml = new \SimpleSAML_Auth_Simple('default-sp');
        $this->_attributes = $this->_simpleSaml->getAttributes();
    }

    /**
     * Returns the Neptun code of the user.
     * The Neptun code uniquely represents the identity.
     * @return string The Neptun code of the user.
     */
    public function getId()
    {
        return $this->niifPersonOrgID;
    }

    /**
     * Returns the display name for the identity.
     * @return string The name of the user.
     */
    public function getName()
    {
        return $this->displayName;
    }

    public function getEmail()
    {
        return $this->mail;
    }

    public function getIsStudent()
    {
        if (isset($this->eduPersonScopedAffiliation)) {
            if (is_array($this->eduPersonScopedAffiliation)) {
                return array_search("student@elte.hu", $this->eduPersonScopedAffiliation) !== false;
            } else {
                return $this->eduPersonScopedAffiliation === "student@elte.hu" ||
                    $this->eduPersonScopedAffiliation === "alum@elte.hu";
            }
        }
        return false;
    }

    public function getIsTeacher()
    {
        if (isset($this->eduPersonScopedAffiliation)) {
            if (is_array($this->eduPersonScopedAffiliation)) {
                return array_search("faculty@elte.hu", $this->eduPersonScopedAffiliation) !== false;
            } else {
                return $this->eduPersonScopedAffiliation === "faculty@elte.hu";
            }
        }
        return false;
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
     * Returns the login URL.
     * @param string $returnUrl The URL to return the user to after login.
     * @return string The login URL.
     */
    public function getLoginURL($returnUrl = null)
    {
        $url = $this->_simpleSaml->getLoginURL($returnUrl);
        return preg_replace('|^http://|i', 'https://', $url);
    }

    /**
     * Returns the logout URL.
     * @param string $returnUrl The URL to return the user to after the logout.
     * @return string The logout URL.
     */
    public function getLogoutURL($returnUrl = null)
    {
        return $this->_simpleSaml->getLogoutURL($returnUrl);
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
        $this->_simpleSaml->requireAuth(array(
            'ReturnTo' => $returnUrl
        ));
        if ($this->_simpleSaml->isAuthenticated()) {
            $this->_attributes = $this->_simpleSaml->getAttributes();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Log out the current user with the given return URL.
     * @param string $returnUrl The URL to return the user to after the logout.
     */
    public function logout($returnUrl = null)
    {
        $this->_attributes = [];
        $this->_simpleSaml->logout($returnUrl);
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
