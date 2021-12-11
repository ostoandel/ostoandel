<?php
namespace Ostoandel\Routing;

use Illuminate\Support\Facades\Response;

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

        return Response::fromCake($response, $content);
    }

}
