<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Cors;

class CorsFilter implements FilterInterface
{
    protected array $cors;

    public function __construct()
    {
        $this->cors = (new Cors())->default;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $origin = $request->getHeaderLine('Origin') ?: '*';

        $allowOrigin = '*';
        if (in_array($origin, $this->cors['allowedOrigins'])) {
            $allowOrigin = $origin;
        } else {
            foreach ($this->cors['allowedOriginsPatterns'] as $pattern) {
                if (preg_match("#^$pattern$#", $origin)) {
                    $allowOrigin = $origin;
                    break;
                }
            }
        }

        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        if ($this->cors['supportsCredentials']) {
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->cors['allowedMethods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->cors['allowedHeaders']));
        header('Access-Control-Expose-Headers: ' . implode(', ', $this->cors['exposedHeaders']));

        // Preflight OPTIONS request
        if ($request->getMethod() === 'options') {
            exit(0);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed here
    }
}
