<?php

namespace app\models;

/**
 * Defines a remote authentication interface.
 *
 * @property-read string $loginURL The login URL.
 * @property-read string $logoutURL The logout URL.
 */
interface RemoteAuthInterface extends AuthInterface
{
    /**
     * Returns the login URL.
     * @param string $returnUrl The URL to return the user to after login.
     * @return string The login URL.
     */
    public function getLoginURL($returnUrl = null);

    /**
     * Returns the logout URL.
     * @param string $returnUrl The URL to return the user to after the logout.
     * @return string The logout URL.
     */
    public function getLogoutURL($returnUrl = null);
}
