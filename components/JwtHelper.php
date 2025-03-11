<?php

namespace app\components;

use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use UnexpectedValueException;
use Yii;

/**
 * A helper class for JWT signing and validation.
 */
class JwtHelper
{
    /**
     * Create and sign a JWT token with the given payload.
     * @param array $payload The payload of the token.
     * @return string The generated and signed JWT token.
     */
    public static function create(array $payload): string
    {
        $secret = Yii::$app->params['jwtSecret'];
        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Validate the given JWT token and return the decoded payload.
     * @param string $token The JWT token to validate.
     * @return array The validated payload of the token.
     * @throws InvalidArgumentException     Provided key/key-array was empty or malformed
     * @throws DomainException              Provided JWT is malformed
     * @throws UnexpectedValueException     Provided JWT was invalid
     * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
     * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
     * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
     */
    public static function validate(string $token): array
    {
        $secret = Yii::$app->params['jwtSecret'];
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return get_object_vars($decoded);
    }
}
