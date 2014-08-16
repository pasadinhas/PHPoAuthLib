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
 * Dailymotion service.
 *
 * @author Mouhamed SEYE <mouhamed@seye.pro>
 * @link http://www.dailymotion.com/doc/api/authentication.html
 */
class Dailymotion extends AbstractService
{
    /**
     * Scopes
     *
     * @var string
     */
    const SCOPE_EMAIL         = 'email',
          SCOPE_PROFILE       = 'userinfo',
          SCOPE_VIDEOS        = 'manage_videos',
          SCOPE_COMMENTS      = 'manage_comments',
          SCOPE_PLAYLIST      = 'manage_playlists',
          SCOPE_TILES         = 'manage_tiles',
          SCOPE_SUBSCRIPTIONS = 'manage_subscriptions',
          SCOPE_FRIENDS       = 'manage_friends',
          SCOPE_FAVORITES     = 'manage_favorites',
          SCOPE_GROUPS        = 'manage_groups';

    /**
     * Dialog form factors
     *
     * @var string
     */
    const DISPLAY_PAGE   = 'page',
          DISPLAY_POPUP  = 'popup',
          DISPLAY_MOBILE = 'mobile';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://api.dailymotion.com/oauth/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://api.dailymotion.com/oauth/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_OAUTH;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        return $this->parseAccessToken($data, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtraOAuthHeaders()
    {
        return array('Accept' => 'application/json');
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    protected function getDefaultBaseApiUrl()
    {
        return new Uri('https://api.box.com/2.0/');
    }
}
