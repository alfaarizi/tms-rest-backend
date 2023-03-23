<?php

namespace app\tests\unit;

use app\exceptions\TokenExpiredException;
use app\models\AccessToken;
use app\models\User;
use app\tests\unit\fixtures\AccessTokenFixture;
use Codeception\Test\Unit;

class AccessTokenTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'users' => [
                'class' => AccessTokenFixture::class,
            ],
        ];
    }

    public function testCheckValidationValid()
    {
        $validToken = AccessToken::findOne(['token' => 'BATMAN;12345']);
        $this->assertTrue($validToken->checkValidation());
    }

    public function testCheckValidationExpired()
    {
        $expiredToken = AccessToken::findOne(['token' => 'STUD01;EXPIRED']);
        $this->assertFalse($expiredToken->checkValidation());
    }

    public function testGetUser()
    {
        $token0 = AccessToken::findOne(['token' => 'BATMAN;12345']);
        $this->assertTrue($token0->user->neptun === "BATMAN");
    }

    public function testCreateForUser()
    {
        $user = User::findOne(['neptun' => 'STUD02']);
        $token = AccessToken::createForUser($user);

        $length = strlen($token->token);
        $this->assertTrue($length === AccessToken::ACCESS_TOKEN_LENGTH + 7);

        $length = strlen($token->imageToken);
        $this->assertTrue($length === AccessToken::IMAGE_TOKEN_LENGTH + 7);

        $this->assertTrue(str_starts_with($token->token, "STUD02-"));
        $this->assertTrue(str_starts_with($token->imageToken, "STUD02-"));
    }

    public function testRefreshValidToken()
    {
        $token = AccessToken::findOne(['token' => 'STUD02;VALID']);
        $oldValidUntil = $token->validUntil;
        $token->refreshValidUntil();
        $this->assertTrue($oldValidUntil != $token->validUntil);
    }

    public function testRefreshAlreadyExpired()
    {
        $token1 = AccessToken::findOne(['token' => 'STUD01;EXPIRED']);
        $this->expectException(TokenExpiredException::class);

        $token1->refreshValidUntil();
    }
}
