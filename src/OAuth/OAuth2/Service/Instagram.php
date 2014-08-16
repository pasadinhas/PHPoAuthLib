<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Instagram extends AbstractService
{
    /**
     * Defined scopes
     * @link http://instagram.com/developer/authentication/#scope
     */
    const SCOPE_BASIC         = 'basic';
    const SCOPE_COMMENTS      = 'comments';
    const SCOPE_RELATIONSHIPS = 'relationships';
    const SCOPE_LIKES         = 'likes';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://api.instagram.com/oauth/authorize/');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://api.instagram.com/oauth/access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_QUERY_STRING;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        // Instagram tokens evidently never expire...
        $token->setEndOfLife(StdOAuth2Token::EOL_NEVER_EXPIRES);
        unset($data['access_token']);

        $token->setExtraParams($data);

        return $token;
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    protected function getDefaultBaseApiUrl()
    {
        return new Uri('https://api.instagram.com/v1/');
    }
}
