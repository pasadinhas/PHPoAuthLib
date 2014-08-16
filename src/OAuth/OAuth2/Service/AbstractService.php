<?php

namespace OAuth\OAuth2\Service;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Exception\Exception;
use OAuth\Common\Service\AbstractService as BaseAbstractService;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\OAuth2\Service\Exception\InvalidAuthorizationStateException;
use OAuth\OAuth2\Service\Exception\InvalidScopeException;
use OAuth\OAuth2\Service\Exception\MissingRefreshTokenException;
use OAuth\Common\Token\TokenInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;

abstract class AbstractService extends BaseAbstractService implements ServiceInterface
{
    /** @const OAUTH_VERSION */
    const OAUTH_VERSION = 2;

    /** @var array */
    protected $scopes;

    /** @var UriInterface|null */
    protected $baseApiUri;

    /** @var bool */
    protected $stateParameterInAuthUrl;

    /**
     * @param CredentialsInterface  $credentials
     * @param ClientInterface       $httpClient
     * @param TokenStorageInterface $storage
     * @param array                 $scopes
     * @param UriInterface|null     $baseApiUri
     * @param bool $stateParameterInAuthUrl
     *
     * @throws InvalidScopeException
     */
    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null,
        $stateParameterInAuthUrl = false
    ) {
        parent::__construct($credentials, $httpClient, $storage);
        $this->stateParameterInAuthUrl = $stateParameterInAuthUrl;

        $this->validateScopes($scopes);

        $this->scopes = $scopes;

        $this->baseApiUri = $baseApiUri ?: $this->getDefaultBaseApiUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUri(array $additionalParameters = array())
    {
        $parameters = $this->getAuthorizationUriParams($additionalParameters);

        if ($this->needsStateParameterInAuthUrl()) {
            $parameters = $this->setStateParameter($parameters);
        }

        return $this->buildAuthorizationUrl($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAccessToken($code, $state = null)
    {
        if (null !== $state) {
            $this->validateAuthorizationState($state);
        }

        $responseBody = $this->httpClient->retrieveResponse(
            $this->getAccessTokenEndpoint(),
            $this->getAccessTokenRequestBodyParams($code),
            $this->getExtraOAuthHeaders()
        );

        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }

    /**
     * Sends an authenticated API request to the path provided.
     * If the path provided is not an absolute URI, the base API Uri (must be passed into constructor) will be used.
     *
     * @param string|UriInterface $path
     * @param string              $method       HTTP method
     * @param array               $body         Request body if applicable.
     * @param array               $extraHeaders Extra headers if applicable. These will override service-specific
     *                                          any defaults.
     *
     * @return string
     *
     * @throws ExpiredTokenException
     * @throws Exception
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        $uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);
        $token = $this->storage->retrieveAccessToken($this->service());

        if ($this->tokenIsExpired($token)) {
            throw new ExpiredTokenException(
                sprintf(
                    'Token expired on %s at %s',
                    date('m/d/Y', $token->getEndOfLife()),
                    date('h:i:s A', $token->getEndOfLife())
                )
            );
        }

        // add the token where it may be needed
        $extraHeaders = $this->addTokenToExtraHeaders($extraHeaders, $token, $uri);

        $extraHeaders = $this->mergeExtraApiHeaders($extraHeaders);

        return $this->httpClient->retrieveResponse($uri, $body, $extraHeaders, $method);
    }

    /**
     * Accessor to the storage adapter to be able to retrieve tokens
     *
     * @return TokenStorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Refreshes an OAuth2 access token.
     *
     * @param TokenInterface $token
     *
     * @return TokenInterface $token
     *
     * @throws MissingRefreshTokenException
     */
    public function refreshAccessToken(TokenInterface $token)
    {
        $refreshToken = $token->getRefreshToken();

        if (empty($refreshToken)) {
            throw new MissingRefreshTokenException();
        }

        $responseBody = $this->httpClient->retrieveResponse(
            $this->getAccessTokenEndpoint(),
            $this->getRefreshAccessTokenParams($refreshToken),
            $this->getExtraOAuthHeaders()
        );

        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }

    /**
     * Return whether or not the passed scope value is valid.
     *
     * @param string $scope
     *
     * @return bool
     */
    public function isValidScope($scope)
    {
        $reflectionClass = new \ReflectionClass(get_class($this));

        return in_array($scope, $reflectionClass->getConstants(), true);
    }

    /**
     * Check if the given service need to generate a unique state token to build the authorization url
     *
     * @return bool
     */
    public function needsStateParameterInAuthUrl()
    {
        return $this->stateParameterInAuthUrl;
    }

    /**
     * Validates the authorization state against a given one
     *
     * @param string $state
     * @throws InvalidAuthorizationStateException
     */
    protected function validateAuthorizationState($state)
    {
        if ($this->retrieveAuthorizationState() !== $state) {
            throw new InvalidAuthorizationStateException();
        }
    }

    /**
     * Generates a random string to be used as state
     *
     * @return string
     */
    protected function generateAuthorizationState()
    {
        return md5(rand());
    }

    /**
     * Retrieves the authorization state for the current service
     *
     * @return string
     */
    protected function retrieveAuthorizationState()
    {
        return $this->storage->retrieveAuthorizationState($this->service());
    }

    /**
     * Stores a given authorization state into the storage
     *
     * @param string $state
     */
    protected function storeAuthorizationState($state)
    {
        $this->storage->storeAuthorizationState($this->service(), $state);
    }

    /**
     * Return any additional headers always needed for this service implementation's OAuth calls.
     *
     * @return array
     */
    protected function getExtraOAuthHeaders()
    {
        return array();
    }

    /**
     * Return any additional headers always needed for this service implementation's API calls.
     *
     * @return array
     */
    protected function getExtraApiHeaders()
    {
        return array();
    }

    /**
     * Parses the access token response and returns a TokenInterface.
     *
     * @abstract
     *
     * @param string $responseBody
     *
     * @return TokenInterface
     *
     * @throws TokenResponseException
     */
    abstract protected function parseAccessTokenResponse($responseBody);

    /**
     * Returns a class constant from ServiceInterface defining the authorization method used for the API
     * Header is the sane default.
     *
     * @return int
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_OAUTH;
    }

    /**
     * @param $code
     * @return array
     */
    protected function getAccessTokenRequestBodyParams($code)
    {
        return array(
            'code' => $code,
            'client_id' => $this->credentials->getConsumerId(),
            'client_secret' => $this->credentials->getConsumerSecret(),
            'redirect_uri' => $this->credentials->getCallbackUrl(),
            'grant_type' => 'authorization_code',
        );
    }

    /**
     * @param array $additionalParameters
     * @return array
     */
    protected function getAuthorizationUriParams(array $additionalParameters)
    {
        $parameters = array_merge(
            $additionalParameters,
            array(
                'type' => 'web_server',
                'client_id' => $this->credentials->getConsumerId(),
                'redirect_uri' => $this->credentials->getCallbackUrl(),
                'response_type' => 'code',
            )
        );

        $parameters['scope'] = implode(' ', $this->scopes);

        return $parameters;
    }

    /**
     * @param $scope
     * @throws InvalidScopeException
     */
    protected function validateScope($scope)
    {
        if (!$this->isValidScope($scope)) {
            throw new InvalidScopeException('Scope ' . $scope . ' is not valid for service ' . get_class($this));
        }
    }

    /**
     * @param $scopes
     */
    protected function validateScopes($scopes)
    {
        foreach ($scopes as $scope) {
            $this->validateScope($scope);
        }
    }

    /**
     * @param $parameters
     * @return mixed
     */
    protected function setStateParameter($parameters)
    {
        if (!isset($parameters['state'])) {
            $parameters['state'] = $this->generateAuthorizationState();
        }
        $this->storeAuthorizationState($parameters['state']);
        return $parameters;
    }

    /**
     * @param array $extraHeaders
     * @param TokenInterface $token
     * @param UriInterface $uri
     * @return array
     */
    protected function addTokenToExtraHeaders(array $extraHeaders, TokenInterface $token, UriInterface $uri)
    {
        if (static::AUTHORIZATION_METHOD_HEADER_OAUTH === $this->getAuthorizationMethod()) {
            $extraHeaders = array_merge(array('Authorization' => 'OAuth ' . $token->getAccessToken()), $extraHeaders);
            return $extraHeaders;
        } elseif (static::AUTHORIZATION_METHOD_QUERY_STRING === $this->getAuthorizationMethod()) {
            $uri->addToQuery('access_token', $token->getAccessToken());
            return $extraHeaders;
        } elseif (static::AUTHORIZATION_METHOD_QUERY_STRING_V2 === $this->getAuthorizationMethod()) {
            $uri->addToQuery('oauth2_access_token', $token->getAccessToken());
            return $extraHeaders;
        } elseif (static::AUTHORIZATION_METHOD_QUERY_STRING_V3 === $this->getAuthorizationMethod()) {
            $uri->addToQuery('apikey', $token->getAccessToken());
            return $extraHeaders;
        } elseif (static::AUTHORIZATION_METHOD_HEADER_BEARER === $this->getAuthorizationMethod()) {
            $extraHeaders = array_merge(array('Authorization' => 'Bearer ' . $token->getAccessToken()), $extraHeaders);
            return $extraHeaders;
        }
        return $extraHeaders;
    }

    /**
     * @param array $extraHeaders
     * @return array
     */
    protected function mergeExtraApiHeaders(array $extraHeaders)
    {
        return array_merge($this->getExtraApiHeaders(), $extraHeaders);
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function tokenIsExpired(TokenInterface $token)
    {
        return $token->getEndOfLife() !== TokenInterface::EOL_NEVER_EXPIRES
                && $token->getEndOfLife() !== TokenInterface::EOL_UNKNOWN
                && time() > $token->getEndOfLife();
    }

    /**
     * @param $parameters
     * @return UriInterface
     */
    protected function buildAuthorizationUrl($parameters)
    {
        $url = clone $this->getAuthorizationEndpoint();
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }
        return $url;
    }

    /**
     * @param $refreshToken
     * @return array
     */
    protected function getRefreshAccessTokenParams($refreshToken)
    {
        return array(
            'grant_type' => 'refresh_token',
            'type' => 'web_server',
            'client_id' => $this->credentials->getConsumerId(),
            'client_secret' => $this->credentials->getConsumerSecret(),
            'refresh_token' => $refreshToken,
        );
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    abstract protected function getDefaultBaseApiUrl();
    //{
    //    return null;
    //}
}
