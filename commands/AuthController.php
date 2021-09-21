<?php

namespace app\commands;

use Yii;
use app\models\User;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manages user authorization.
 */
class AuthController extends BaseController
{
    /**
     * Grants a role to the specified user.
     *
     * @param $neptun string The Neptun ID of the user.
     * @param $role string The role to grant to the user.
     * @return int Error code.
     */
    public function actionGrant($neptun, $role)
    {
        $authManager = Yii::$app->authManager;
        $user = User::findOne(['neptun' => $neptun]);
        $roleObj = $authManager->getRole($role);

        if (is_null($user)) {
            $this->stderr("Failed to find user '$neptun'." . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        if (is_null($roleObj)) {
            $this->stderr("Failed to find semester '$role'." . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $authManager->assign($roleObj, $user->id);
        return ExitCode::OK;
    }

    /**
     * Revokes a role to the specified user.
     *
     * @param $neptun string The Neptun ID of the user.
     * @param $role string|null The role to revoke from the user. If empty, all roles will be revoked.
     * @return int Error code.
     */
    public function actionRevoke($neptun, $role = null)
    {
        $authManager = Yii::$app->authManager;
        $user = User::findOne(['neptun' => $neptun]);

        if (is_null($user)) {
            $this->stderr("Failed to find user '$neptun'." . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $roleObj = null;
        if (!empty($role)) {
            $roleObj = $authManager->getRole($role);
            if (is_null($roleObj)) {
                $this->stderr("Failed to find semester '$role'." . PHP_EOL, Console::FG_RED);
                return ExitCode::DATAERR;
            }
        }

        if (!is_null($roleObj)) {
            $authManager->revoke($roleObj, $user->id);
        } else {
            $authManager->revokeAll($user->id);
        }
        return ExitCode::OK;
    }
}
