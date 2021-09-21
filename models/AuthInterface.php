<?php

namespace app\models;

/**
 * Defines an authentication interface.
 *
 * @property-read string $id The unique ID that represents the identity.
 * @property-read string $name The display name for the identity.
 * @property-read string $email The email address for the identity.
 * @property-read string $isStudent The student permission level for the identity.
 * @property-read string $isTeacher The teacher permission level for the identity.
 * @property-read string $isAdmin The administrator permission level for the identity.
 * @property-read boolean $isAuthenticated Whether the identity is valid.
 */
interface AuthInterface
{
    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string An ID that uniquely identifies a user identity.
     */
    public function getId();

    /**
     * Returns the display name for the identity.
     * @return string The name of the user.
     */
    public function getName();

    public function getEmail();

    public function getIsStudent();

    public function getIsTeacher();

    public function getIsAdmin();

    /**
     * Returns a value indicating whether the identity is authenticated.
     * @return boolean Whether the identity is valid.
     */
    public function getIsAuthenticated();

    /**
     * Authenticates the user.
     *
     * This method must succeed if returns.
     * @param string $returnUrl The URL to return the user to after login.
     * @return boolean True if the authentication succeeded, otherwise false.
     */
    public function login($returnUrl = null);

    /**
     * Log out the current user with the given return URL.
     * @param string $returnUrl The URL to return the user to after the logout.
     */
    public function logout($returnUrl = null);
}
