<?php namespace OAuth\OAuth2\Traits;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Token\TokenInterface;
use OAuth\OAuth2\Token\StdOAuth2Token;

trait TokenParserTrait {

    /**
     * @var array
     */
    private $accessTokenKeys;

    /**
     * @param array $data
     * @param bool $expires
     * @param array $keys
     * @return StdOAuth2Token
     */
    protected function parseAccessToken($data, $expires = true, array $keys = [])
    {
        $this->setTokenResponseKeys($keys);

        $this->validateTokenData($data);

        $token = $this->createNewAccessToken($data);

        $this->setTokenExpiration($token, $data, $expires);

        $this->setRefreshToken($data, $token);

        $token->setExtraParams($data);

        return $token;
    }

    /**
     * @param array $data
     * @return array
     * @throws TokenResponseException
     */
    private function validateTokenData($data)
    {
        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data[$this->accessTokenKeys['error_desc']])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data[$this->accessTokenKeys['error_desc']] . '"');
        } elseif (isset($data[$this->accessTokenKeys['error']])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data[$this->accessTokenKeys['error']] . '"');
        }
        return $data;
    }

    /**
     * @param array $data
     * @return StdOAuth2Token
     */
    private function createNewAccessToken(array $data)
    {
        $token = new StdOAuth2Token();
        $token->setAccessToken($data[$this->accessTokenKeys['access']]);
        unset($data[$this->accessTokenKeys['access']]);
        return $token;
    }

    /**
     * @param TokenInterface $token
     * @param array $data
     * @param $expires
     */
    private function setTokenExpiration(TokenInterface $token, array $data, $expires)
    {
        if ($expires) {
            $token->setLifeTime($data[$this->accessTokenKeys['expires']]);
            unset($data[$this->accessTokenKeys['expires']]);
        } else {
            $token->setEndOfLife(StdOAuth2Token::EOL_NEVER_EXPIRES);
        }
    }

    /**
     * @param array $data
     * @param TokenInterface $token
     */
    private function setRefreshToken(array $data, TokenInterface $token)
    {
        if (isset($data[$this->accessTokenKeys['refresh']])) {
            $token->setRefreshToken($data[$this->accessTokenKeys['refresh']]);
            unset($data[$this->accessTokenKeys['refresh']]);
        }
    }

    /**
     * @param array $keys
     */
    private function setTokenResponseKeys(array $keys)
    {
        $this->accessTokenKeys = array_merge($this->getDefaultTokenKeys(), $keys);
    }

    /**
     * @return array
     */
    private function getDefaultTokenKeys()
    {
        return [
            'access' => 'access_token',
            'refresh' => 'refresh_token',
            'expires' => 'expires_in',
            'error' => 'error',
            'error_desc' => 'error_description'
        ];
    }

}