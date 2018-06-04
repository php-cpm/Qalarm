<?php
namespace App\Components\ClientApi\Middleware;

use App\Components\ClientApi\Request as ClientRequest;
use App\Components\ClientApi\Response as ClientResponse;
use Closure;

class Guest
{
    protected $clientRequest;

    public function __construct(ClientRequest $client_request)
    {
        $this->clientRequest = $client_request;
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
