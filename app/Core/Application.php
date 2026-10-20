<?php

namespace App\Core;

class Application
{
    private static ?Application $instance = null;
    private Router $router;
    private Request $request;
    private Response $response;
    private array $config = [];

    private function __construct()
    {
        $this->request = new Request();
        // Make this exact Request instance globally accessible
        RequestContext::set($this->request);
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run(): void
    {
        try {
            $this->router->resolve();
        } catch (\Throwable $e) {
            $statusCode = 500;
            if (is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599) {
                $statusCode = $e->getCode();
            }
            // $this->response->json([
            //     'success' => false,
            //     'message' => $e->getMessage().' '.$e->getFile().':'.$e->getLine().' '.$e->getTraceAsString(),
            //     'exception' => get_class($e),
            //     'code' => $e->getCode(),
            // ], $statusCode);
            $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $response = [
                'success' => false,
                'message' => $debug
                    ? $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getTraceAsString()
                    : 'An unexpected error occurred.',
            ];

            if ($debug) {
                $response['exception'] = get_class($e);
                $response['code'] = $e->getCode();
            }
            $this->response->json($response);
        }
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getConfig(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
}