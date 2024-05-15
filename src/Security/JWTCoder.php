<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\Api\Security;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Systemcheck\ContaoApiBundleException\ExpiredTokenException;
use Systemcheck\ContaoApiBundleException\InvalidJWTException;


use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;

class JWTCoder
{
    const ALG = 'HS256';
    private $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @param array $payload
     * @param int   $ttl
     *
     * @return string
     */
    public function encode(array $payload, $ttl = 86400)
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;

        return JWT::encode($payload, $this->key, self::ALG);
    }

    /**
     * @param string $token
     *
     * @throws InvalidJWTException
     *
     * @return object
     */
    public function decode($token)
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse(
                $token
            );
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            echo 'Oh no, an error: ' . $e->getMessage();
        }
    
        return $token->claims()->all();
        assert($token instanceof UnencryptedToken);

        /*try {
            $payload = JWT::decode($token, $this->key, [self::ALG]);
        } catch (ExpiredException $e) {
            throw new ExpiredTokenException('systemcheck.api.exception.auth.token_expired');
        } catch (\Exception $e) {
            throw new InvalidJWTException('systemcheck.api.exception.auth.invalid_token');
        }

        return $payload;*/
    }
}
