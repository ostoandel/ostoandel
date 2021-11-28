<?php
namespace Ostoandel\Routing;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

\App::uses('Dispatcher', 'Routing');

class LaravelDispatcher extends \Dispatcher
{
    protected $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * {@inheritDoc}
     * @see \Dispatcher::parseParams()
     */
    public function parseParams($event) {
        if ($this->route->controller === null) {
            parent::parseParams($event);
        } else {
            $this->parseLaravelParams($event);
        }

        $request = $event->data['request'];
        $laravelRequest = request();
        $laravelRequest->attributes->add($request->params);
    }

    protected function parseLaravelParams($event)
    {
        list($controller, $action) = explode('@', $this->route->getActionName() . '@');
        $controller = preg_replace('/.*?([^\\\\]+)Controller$/', '$1', $controller);
        $controller = Str::snake($controller);

        $action = $this->route->getActionMethod();

        $params = $this->route->parameters;

        $reserved = [
            'controller' => $controller,
            'action' => $action,
            'pass' => [],
            'named' => [],
        ];

        $conflict = array_intersect_key($params, $reserved);
        if ($conflict) {
            $first = key($conflict);
            throw new \LogicException("Routing parameter [$first] is reserved");
        }

        $pass = array_values($params);

        $params = $reserved + $params;
        $params['pass'] = $pass;

        $request = $event->data['request'];
        \Router::setRequestInfo($request);
        $request->addParams($params);
    }

    /**
     * {@inheritDoc}
     * @see \Dispatcher::_getController()
     */
    protected function _getController($request, $response)
    {
        $controller = $this->route->controller;

        if ($controller === null) {
            $controller = parent::_getController($request, $response);
            if ($controller === false) {
                return false;
            }

            $this->route->controller = $controller;
        }

        $controller->setRequest($request);
        $controller->response = $response;

        return $controller;
    }

    /**
     * {@inheritDoc}
     * @see \Dispatcher::_invoke()
     */
    protected function _invoke($controller, $request)
    {
        $pass = $request['pass'];
        $action = $request['action'];

        if (method_exists($controller, $action)) {
            $method = new \ReflectionMethod($controller, $action);
            $pass = $this->route->resolveMethodDependencies($pass, $method);
        }

        if ($pass !== $request->pass) {
            $request = clone $request;
            $request['pass'] = $pass;
        }

        return parent::_invoke($controller, $request);
    }
}
