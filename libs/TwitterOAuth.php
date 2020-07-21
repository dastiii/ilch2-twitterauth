<?php

namespace Modules\Twitterauth\Libs;

use Ilch\Database\Exception;
use Ilch\Request;
use Modules\Twitterauth\Mappers\DbLog;

class TwitterOAuth {
    /**
     * Request type for obtaining the initial access token for your app
     */
    const OBTAIN_ACCESS_TOKEN = 1;

    /**
     * Request type for converting the request token to an access token
     */
    const CONVERT_REQUEST_TO_ACCESS_TOKEN = 2;

    /**
     * Url to the authenticate endpoint
     */
    const AUTHENTICATE_URL = 'https://api.twitter.com/oauth/authenticate';

    /**
     * Url to the access token endpoint
     */
    const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';

    /**
     * Twitter Application API Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Twitter Application API Secret
     *
     * @var string
     */
    protected $apiSecret;

    /**
     * Twitter Application Token
     *
     * @var string
     */
    protected $token;

    /**
     * Twitter Application Token Secret
     *
     * @var string
     */
    protected $tokenSecret;

    /**
     * The current nonce
     *
     * @var string
     */
    protected $nonce;

    /**
     * The current timestamp
     *
     * @var string
     */
    protected $timestamp;

    /**
     * The signature method
     *
     * @var string
     */
    protected $signatureMethod = 'HMAC-SHA1';

    /**
     * The oauth version to use
     *
     * @var string
     */
    protected $oauthVersion = '1.0';

    /**
     * The OAuth header
     *
     * @var string
     */
    protected $authorizationHeader;

    /**
     * The signature
     *
     * @var string
     */
    protected $signature;

    /**
     * The HTTP method
     *
     * @var string
     */
    protected $httpMethod = 'POST';

    /**
     * The API url
     *
     * @var string
     */
    protected $apiUrl = 'https://api.twitter.com/oauth/request_token';

    /**
     * The parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * The callback url
     *
     * @var string
     */
    protected $callbackUrl;

    /**
     * Debug mode
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * The result of the curl request
     *
     * @var mixed
     */
    protected $result;

    /**
     * DbLog mapper instance
     *
     * @var DbLog
     */
    protected $dbLog;

    /**
     * The OAuth verifier
     *
     * @var string
     */
    protected $oauthVerifier;

    /**
     * POST data
     *
     * @var array
     */
    protected $post;

    /**
     * TwitterOAuth constructor.
     *
     * @param $apiKey       string|null  Twitter Application API Key
     * @param $apiSecret    string|null  Twitter Application API Secret
     * @param $token        string|null  OAuth Token
     * @param $tokenSecret  string|null  OAuth Token Secret
     * @param $callbackUrl  string|null  The callback Url
     */
    public function __construct($apiKey = null, $apiSecret = null, $token = null, $tokenSecret = null, $callbackUrl = null)
    {
        $this->dbLog = new DbLog();

        $this->setApiKey($apiKey);
        $this->setApiSecret($apiSecret);
        $this->setToken($token);
        $this->setTokenSecret($tokenSecret);
        $this->setCallbackUrl($callbackUrl);

        $this->generateNonce();
        $this->generateTimestamp();
    }

    /**
     * Builds the authorization header
     */
    protected function buildAuthorizationHeader()
    {
        $this->setParameters([
            'oauth_nonce'               => $this->getNonce(),
            'oauth_consumer_key'        => $this->getApiKey(),
            'oauth_timestamp'           => $this->getTimestamp(),
            'oauth_token'               => $this->getToken(),
            'oauth_signature_method'    => $this->getSignatureMethod(),
            'oauth_version'             => $this->getOauthVersion(),
            'oauth_callback'            => $this->getCallbackUrl(),
        ]);

        if (! $this->getSignature()) {
            $this->generateSignature();
        }

        $this->setParameter('oauth_signature', $this->getSignature());

        $parameterCount = count($this->getParameters());
        $i = 0;

        $header = 'OAuth ';

        foreach ($this->getParameters() as $key => $value) {
            if (empty($value) || is_null($value)) {
                continue;
            }

            ++$i;
            $header .= rawurlencode($key).'="'.rawurlencode($value).'"';

            if ($i !== $parameterCount) {
                $header .= ', ';
            }
        }

        $this->setAuthorizationHeader($header);
    }

    /**
     * Obtains the needed tokens
     */
    public function obtainTokens()
    {
        $this->buildAuthorizationHeader();

        $this->makeRequest(self::OBTAIN_ACCESS_TOKEN);
    }

    /**
     * Converts the tokens
     */
    public function convertTokens()
    {
        $this->setApiUrl(self::ACCESS_TOKEN_URL);
        $this->setCallbackUrl(null);
        $this->setPost(['oauth_verifier' => $this->getOauthVerifier()]);

        $this->buildAuthorizationHeader();

        $this->makeRequest(self::CONVERT_REQUEST_TO_ACCESS_TOKEN);
    }

    /**
     * Makes the actual request
     *
     * @param $type
     *
     * @throws Exception
     *
     * @return void
     */
    protected function makeRequest($type)
    {
        $request = curl_init();

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_URL, $this->getApiUrl());
        curl_setopt($request, CURLOPT_POST, $this->getHttpMethod() === 'POST' ? 1 : 0);
        curl_setopt($request, CURLOPT_HTTPHEADER, ['Authorization: '.$this->getAuthorizationHeader()]);

        if ($this->hasPost()) {
            curl_setopt($request, CURLOPT_POST, count($this->getPost()));
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($this->getPost()));
        }

        if ($this->isDebug()) {
            curl_setopt($request, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($request);
        $httpStatusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);

        var_dump($response);

        if ($httpStatusCode !== 200) {
            $this->dbLog->error('Twitter API responded with an error code', $response);

            throw new Exception('An error occured.', $httpStatusCode);
        }

        switch($type) {
            case self::OBTAIN_ACCESS_TOKEN:
                parse_str($response, $response);

                if (! isset($response['oauth_callback_confirmed'])
                    || ! $response['oauth_callback_confirmed']
                    || ! isset($response['oauth_token'])
                    || ! isset($response['oauth_token_secret'])
                ) {
                    $this->dbLog->error('Missing query parameters or oauth_callback_confirmed is not true', $response);

                    throw new Exception('An error occured');
                }

                array_dot_set($_SESSION, 'twitterauth.initial_oauth_token', $response['oauth_token']);

                $this->setResult($response);

                break;

            case self::CONVERT_REQUEST_TO_ACCESS_TOKEN:
                parse_str($response, $response);

                if (! isset($response['oauth_token']) || ! isset($response['oauth_token_secret'])) {
                    $this->dbLog->error('Error while converting tokens.', $response);

                    throw new Exception('An error occured');
                }

                $this->setResult($response);

                break;
        }
    }

    /**
     * Handles the Twitter callback
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return void
     */
    public function handleCallback(Request $request)
    {
        if (is_null($request->getQuery('oauth_token')) || is_null($request->getQuery('oauth_verifier'))) {
            if ($request->getQuery('denied')) {
                $this->dbLog->error('Access has been denied by user.', $request->getQuery());

                throw new Exception('Access has been denied by user.');
            }

            $this->dbLog->error('An unknown error occured', $request->getQuery());

            throw new Exception('An unknown error occured');
        }

        $initialOAuthToken = array_dot($_SESSION, 'twitterauth.initial_oauth_token');

        if (! $initialOAuthToken || $request->getQuery('oauth_token') !== $initialOAuthToken) {
            $this->dbLog->error('Token mismatch.', [
                'expected' => $initialOAuthToken,
                'received' => $request->getQuery('oauth_token')
            ]);

            unset($_SESSION['twitterauth']['initial_oauth_token']);

            throw new Exception('Token mismatch.');
        }

        $this->setToken($request->getQuery('oauth_token'));
        $this->setOauthVerifier($request->getQuery('oauth_verifier'));
        unset($_SESSION['twitterauth']['initial_oauth_token']);
    }

    /**
     * Returns the full authentication url including the oauth_token
     *
     * @return string
     */
    public function getAuthenticationEndpoint()
    {
        return self::AUTHENTICATE_URL . '?oauth_token=' . $this->getResult()['oauth_token'];
    }

    /**
     * Generates the signature for the request
     *
     * @see https://dev.twitter.com/oauth/overview/creating-signatures
     *
     * @return void
     */
    protected function generateSignature()
    {
        $base = strtoupper($this->getHttpMethod()).'&'.rawurlencode($this->getApiUrl()).'&'.rawurlencode(http_build_query($this->getParameters()));
        $signingKey = rawurlencode($this->getApiSecret()).'&'.rawurlencode($this->getTokenSecret());

        $this->setSignature(base64_encode(hash_hmac('sha1', $base, $signingKey, true)));
    }

    /**
     * Returns a timestamp
     *
     * @see https://dev.twitter.com/oauth/overview/authorizing-requests
     *
     * @return void
     */
    protected function generateTimestamp()
    {
        $this->setTimestamp(time());
    }

    /**
     * Returns a unique nonce
     *
     * @see https://dev.twitter.com/oauth/overview/authorizing-requests
     *
     * @return void
     */
    protected function generateNonce()
    {
        $nonce = str_replace(['=', '/', '+'], '', base64_encode(openssl_random_pseudo_bytes(32)));

        $this->setNonce($nonce);
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * @param string $apiSecret
     */
    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getTokenSecret()
    {
        return $this->tokenSecret;
    }

    /**
     * @param string $tokenSecret
     */
    public function setTokenSecret($tokenSecret)
    {
        $this->tokenSecret = $tokenSecret;
    }

    /**
     * @return string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param string $nonce
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getSignatureMethod()
    {
        return $this->signatureMethod;
    }

    /**
     * @param string $signatureMethod
     */
    public function setSignatureMethod($signatureMethod)
    {
        $this->signatureMethod = $signatureMethod;
    }

    /**
     * @return string
     */
    public function getOauthVersion()
    {
        return $this->oauthVersion;
    }

    /**
     * @param string $oauthVersion
     */
    public function setOauthVersion($oauthVersion)
    {
        $this->oauthVersion = $oauthVersion;
    }

    /**
     * @return string
     */
    public function getAuthorizationHeader()
    {
        return $this->authorizationHeader;
    }

    /**
     * @param string $authorizationHeader
     */
    public function setAuthorizationHeader($authorizationHeader)
    {
        $this->authorizationHeader = $authorizationHeader;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * @param string $httpMethod
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        ksort($this->parameters);

        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return string
     */
    public function getOauthVerifier()
    {
        return $this->oauthVerifier;
    }

    /**
     * @param string $oauthVerifier
     */
    public function setOauthVerifier($oauthVerifier)
    {
        $this->oauthVerifier = $oauthVerifier;
    }

    /**
     * @return array
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param array $post
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * @return bool
     */
    public function hasPost()
    {
        return !empty($this->getPost());
    }
}
