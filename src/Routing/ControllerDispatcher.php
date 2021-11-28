<?php
namespace Ostoandel\Routing;

use Symfony\Component\HttpFoundation\Cookie;

\App::uses('CakeRequest', 'Network');
\App::uses('CakeResponse', 'Network');

class ControllerDispatcher extends \Illuminate\Routing\ControllerDispatcher
{

    /**
     * {@inheritDoc}
     * @see \Illuminate\Routing\ControllerDispatcher::dispatch()
     */
    public function dispatch(\Illuminate\Routing\Route $route, $controller, $method)
    {
        if ($controller !== null && !$controller instanceof \Controller) {
            return parent::dispatch($route, $controller, $method);
        }

        $request = new \CakeRequest();
        $response = new \CakeResponse();

        $dispatcher = new LaravelDispatcher($route);
        $request['return'] = -1;
        $content = $dispatcher->dispatch($request, $response);

        $laravelResponse = response($content, $response->statusCode(), $response->header());
        foreach ($response->cookie() as $cookie) {
            $laravelResponse->cookie(new Cookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure']
            ));
        }

        return $laravelResponse;
    }

}
