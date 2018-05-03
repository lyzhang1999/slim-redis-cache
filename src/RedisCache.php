<?php

namespace Slim\Middleware;
use \Predis\ClientInterface;
class RedisCache extends \Slim\Middleware
{
    protected $client;
    protected $settings;
    public function __construct(ClientInterface $client, array $settings = [])
    {
        $this->client = $client;
        $this->settings = $settings;
    }
    public function call()
    {
        $app = $this->app;
        $env = $app->environment;
        $key = $env['SCRIPT_NAME'] . $env['PATH_INFO'];
        if (!empty($env['QUERY_STRING']))
            $key .= '?' . $env['QUERY_STRING'];
        $response = $app->response;
        if ($this->client->exists($key)) {
            $response->setBody($this->client->get($key));
            return;
        }
        $this->next->call();
        if ($response->getStatus() == 200) {
            $this->client->set($key, $response->getBody());
            if (array_key_exists('timeout', $this->settings))
                $this->client->expire($key, $this->settings['timeout']);
        }
    }
}