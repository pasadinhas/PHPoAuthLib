<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Reddit extends AbstractService
{
    /**
     * Defined scopes
     *
     * @link http://www.reddit.com/dev/api/oauth
     */
    // User scopes
    const SCOPE_EDIT                         = 'edit';
    const SCOPE_HISTORY                      = 'history';
    const SCOPE_IDENTITY                     = 'identity';
    const SCOPE_MYSUBREDDITS                 = 'mysubreddits';
    const SCOPE_PRIVATEMESSAGES              = 'privatemessages';
    const SCOPE_READ                         = 'read';
    const SCOPE_SAVE                         = 'save';
    const SCOPE_SUBMIT                       = 'submit';
    const SCOPE_SUBSCRIBE                    = 'subscribe';
    const SCOPE_VOTE                         = 'vote';
    // Mod Scopes
    const SCOPE_MODCONFIG                    = 'modconfig';
    const SCOPE_MODFLAIR                     = 'modflair';
    const SCOPE_MODLOG                       = 'modlog';
    const SCOPE_MODPOST                      = 'modpost';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://ssl.reddit.com/api/v1/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://ssl.reddit.com/api/v1/access_token');
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
        // Reddit uses a Basic OAuth header
        return array('Authorization' => 'Basic ' .
            base64_encode($this->credentials->getConsumerId() . ':' . $this->credentials->getConsumerSecret()));
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    protected function getDefaultBaseApiUrl()
    {
        return new Uri('https://oauth.reddit.com');
    }
}
