<?php

namespace Modules\Twitterauth\Libs;

class TwitterAuth
{
    protected $parameters = [];

    protected $without = null;

    protected $fields = [];

    protected $method = 'POST';
    protected $version = '1.0';
    protected $url;
    protected $signature_method = 'HMAC-SHA1';

    protected $callback;

    protected $consumer_key;
    protected $consumer_secret;

    protected $token;
    protected $token_secret;

    protected $timestamp;
    protected $nonce;

    protected $signature_base;

    protected $signing_key;

    protected $signature;

    protected $authorization_header;

    protected $errors = [];

    protected $result;

    protected $debug_mode = false;

    public function exec()
    {
        $this->fillParameters();

        if (!is_null($this->getWithout())) {
            if (is_array($this->getWithout())) {
                foreach ($this->getWithout() as $key) {
                    unset($this->parameters[$key]);
                }
            } else {
                unset($this->parameters[$this->getWithout()]);
            }
        }

        $this->createSignatureBase()
            ->createSigningKey()
            ->createSignature();

        $this->addParameter('oauth_signature', $this->getSignature());
        $this->createAuthorizationHeader();

        $this->makeRequest();

        return $this;
    }

    protected function makeRequest()
    {
        $req = curl_init();
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_URL, $this->getUrl());
        curl_setopt($req, CURLOPT_POST, $this->getMethod() === 'POST' ? 1 : 0);
        curl_setopt($req, CURLOPT_HTTPHEADER, ['Authorization: '.$this->getAuthorizationHeader()]);

        if ($this->debug_mode === true) {
            curl_setopt($req, CURLOPT_VERBOSE, true);
        }

        if (count($this->getFields()) > 0) {
            curl_setopt($req, CURLOPT_POST, count($this->getFields()));
            curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($this->getFields()));
        }

        $res = curl_exec($req);
        $http_status_code = curl_getinfo($req, CURLINFO_HTTP_CODE);

        if ($http_status_code === 200) {
            if (curl_getinfo($req, CURLINFO_CONTENT_TYPE) == 'application/json;charset=utf-8') {
                $this->setResult(json_decode($res));
            } else {
                parse_str($res, $res);
                $this->setResult($res);
            }
        } else {
            $res = json_decode($res);
            $this->setErrors($res->errors);
        }
    }

    protected function fillParameters()
    {
        $this->addParameter('oauth_nonce', $this->generateNonce())
            ->addParameter('oauth_consumer_key', $this->getConsumerKey())
            ->addParameter('oauth_timestamp', $this->generateTimestamp())
            ->addParameter('oauth_token', $this->getToken())
            ->addParameter('oauth_signature_method', $this->getSignatureMethod())
            ->addParameter('oauth_version', $this->getVersion())
            ->addParameter('oauth_callback', $this->getCallback());

        return $this;
    }

    public function createAuthorizationHeader()
    {
        $paramCount = count($this->getParameters());
        $i = 0;

        $header = 'OAuth ';

        foreach ($this->getParameters() as $key => $value) {
            ++$i;
            $header .= rawurlencode($key).'="'.rawurlencode($value).'"';

            if ($i !== $paramCount) {
                $header .= ', ';
            }
        }

        $this->setAuthorizationHeader($header);

        return $this;
    }

    public function createSignatureBase()
    {
        $this->setSignatureBase(
            strtoupper($this->getMethod()).'&'.rawurlencode($this->getUrl()).'&'.rawurlencode(http_build_query($this->getParameters()))
        );

        return $this;
    }

    public function createSigningKey()
    {
        $this->setSigningKey(rawurlencode($this->getConsumerSecret()).'&'.rawurlencode($this->getTokenSecret()));

        return $this;
    }

    public function createSignature()
    {
        $this->setSignature(base64_encode(hash_hmac('sha1', $this->getSignatureBase(), $this->getSigningKey(), true)));

        return $this;
    }

    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    protected function getParameters()
    {
        ksort($this->parameters);

        return $this->parameters;
    }

    protected function generateTimestamp()
    {
        $this->timestamp = time();

        return $this->timestamp;
    }

    protected function generateNonce()
    {
        $this->nonce = base64_encode(openssl_random_pseudo_bytes(32));

        return $this->nonce;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getSignatureMethod()
    {
        return $this->signature_method;
    }

    public function setSignatureMethod($signature_method)
    {
        $this->signature_method = $signature_method;

        return $this;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function getConsumerKey()
    {
        return $this->consumer_key;
    }

    public function setConsumerKey($consumer_key)
    {
        $this->consumer_key = $consumer_key;

        return $this;
    }

    public function getConsumerSecret()
    {
        return $this->consumer_secret;
    }

    public function setConsumerSecret($consumer_secret)
    {
        $this->consumer_secret = $consumer_secret;

        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenSecret()
    {
        return $this->token_secret;
    }

    public function setTokenSecret($token_secret)
    {
        $this->token_secret = $token_secret;

        return $this;
    }

    public function getSignatureBase()
    {
        return $this->signature_base;
    }

    public function setSignatureBase($signature_base)
    {
        $this->signature_base = $signature_base;

        return $this;
    }

    public function getNonce()
    {
        return $this->nonce;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getSigningKey()
    {
        return $this->signing_key;
    }

    public function setSigningKey($signing_key)
    {
        $this->signing_key = $signing_key;

        return $this;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    public function getAuthorizationHeader()
    {
        return $this->authorization_header;
    }

    public function setAuthorizationHeader($authorization_header)
    {
        $this->authorization_header = $authorization_header;

        return $this;
    }

    public function getDebugMode()
    {
        return $this->debug_mode;
    }

    public function setDebugMode($debug_mode)
    {
        $this->debug_mode = $debug_mode;

        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function addField($key, $value)
    {
        $this->fields[$key] = $value;

        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasError()
    {
        return count($this->getErrors()) > 0;
    }

    public function setWithout($without)
    {
        $this->without = $without;

        return $this;
    }

    public function getWithout()
    {
        return $this->without;
    }
}
