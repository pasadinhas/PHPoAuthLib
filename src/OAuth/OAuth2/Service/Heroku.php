<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

/**
 * Heroku service.
 *
 * @author Thomas Welton <thomaswelton@me.com>
 * @link https://devcenter.heroku.com/articles/oauth
 */
class Heroku extends AbstractService
{
    /**
     * Defined scopes
     * @link https://devcenter.heroku.com/articles/oauth#scopes
     */
    const SCOPE_GLOBAL          = 'global';
    const SCOPE_IDENTITY        = 'identity';
    const SCOPE_READ            = 'read';
    const SCOPE_WRITE           = 'write';
    const SCOPE_READ_PROTECTED  = 'read-protected';
    const SCOPE_WRITE_PROTECTED = 'write-protected';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://id.heroku.com/oauth/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://id.heroku.com/oauth/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        return $this->parseAccessToken($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtraOAuthHeaders()
    {
        return array('Accept' => 'application/vnd.heroku+json; version=3');
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtraApiHeaders()
    {
        return array('Accept' => 'application/vnd.heroku+json; version=3', 'Content-Type' => 'application/json');
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    protected function getDefaultBaseApiUrl()
    {
        return new Uri('https://api.heroku.com/');
    }
}
