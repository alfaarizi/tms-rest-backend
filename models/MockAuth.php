<?php

namespace app\models;

/**
 * Defines a mocking authentication interface.
 */
class MockAuth extends \yii\base\BaseObject implements AuthInterface
{
    private $_neptun;
    private $_name;
    private $_email;
    private $_isStudent;
    private $_isTeacher;
    private $_isAdmin;
    private $_isAuthenticated;

    /**
     * Constructs a new instance.
     */
    public function __construct($neptun, $name, $email, $isStudent = true, $isTeacher = false, $isAdmin = false)
    {
        $this->_neptun = $neptun;
        $this->_name = $name;
        $this->_email = $email;
        $this->_isStudent = $isStudent;
        $this->_isTeacher = $isTeacher;
        $this->_isAdmin = $isAdmin;
        $this->_isAuthenticated = true;
    }

    /**
     * Returns the Neptun code of the user.
     * The Neptun code uniquely represents the identity.
     * @return string The Neptun code of the user.
     */
    public function getId()
    {
        return $this->_neptun;
    }

    /**
     * Returns the display name for the identity.
     * @return string The name of the user.
     */
    public function getName()
    {
        return $this->_name;
    }

    public function getEmail()
    {
        return $this->_email;
    }

    public function getIsStudent()
    {
        return $this->_isStudent;
    }

    public function getIsTeacher()
    {
        return $this->_isTeacher;
    }

    public function getIsAdmin()
    {
        return $this->_isAdmin;
    }

    /**
     * Returns a value indicating whether the identity is authenticated.
     * @return boolean Whether the identity is valid.
     */
    public function getIsAuthenticated()
    {
        return $this->_isAuthenticated;
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
        $this->_isAuthenticated = true;
        return true;
    }

    /**
     * Log out the current user with the given return URL.
     * @param string $returnUrl The URL to return the user to after the logout.
     */
    public function logout($returnUrl = null)
    {
        $this->_isAuthenticated = false;
    }
}
