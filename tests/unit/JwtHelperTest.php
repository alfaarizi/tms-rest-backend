<?php

namespace app\tests\unit;

use app\components\JwtHelper;

class JwtHelperTest extends \Codeception\Test\Unit
{
    public function testValid()
    {
        $tokenData = ['data1' => 'value', 'data2' => 42];
        $token = JwtHelper::create($tokenData);
        $decoded = JwtHelper::validate($token);
        $this->assertEquals($tokenData, $decoded);
    }

    public function testInvalidSignature()
    {
        $tokenData = ['data1' => 'value', 'data2' => 42];
        $token = JwtHelper::create($tokenData);

        $parts = explode('.', $token);
        $parts[2] = str_repeat('x', strlen($parts[2])); //header.payload.xxxxxx
        $invalidToken = implode('.', $parts);

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);
        $decoded = JwtHelper::validate($invalidToken);
    }
}
