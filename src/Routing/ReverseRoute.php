<?php
namespace Ostoandel\Routing;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class ReverseRoute extends \CakeRoute
{

    /**
     * {@inheritDoc}
     * @see \CakeRoute::parse()
     */
    public function parse($url)
    {
        return false;
    }

    protected function parseAction(\Illuminate\Routing\Route $route)
    {
        list($controller, $action) = explode('@', $route->getActionName() . '@');
        $controller = preg_replace('/.*?([^\\\\]+)Controller$/', '$1', $controller);
        $controller = Str::snake($controller);

        return compact('controller', 'action');
    }

    /**
     * {@inheritDoc}
     * @see \CakeRoute::match()
     */
    public function match($url)
    {
        $action = Arr::only($url, ['controller', 'action']);
        $parameters = Arr::except($url, ['controller', 'action']);

        $pathPrefix = null;
        foreach (\Router::prefixes() as $prefix) {
            if (!empty($parameters[$prefix])) {
                $actionPrefix = $prefix . '_';
                if (!Str::startsWith($action['action'], $actionPrefix)) {
                    $action['action'] = $actionPrefix . $action['action'];
                }

                $pathPrefix = '/' . $prefix;
            }
            unset($parameters[$prefix]);
        }

        if ($parameters['plugin'] === null) {
            unset($parameters['plugin']);
        }

        /** @var \Illuminate\Routing\Route $route */
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->isFallback) {
                continue;
            }

            $expectedAction = $this->parseAction($route);
            if ($action == $expectedAction && $pathPrefix === $route->getPrefix()) {
                $expectedParameterNames = $route->parameterNames();
                $parameterKeys = array_keys($parameters);
                if (!array_diff($parameterKeys, $expectedParameterNames) || !array_diff($parameterKeys, array_keys($expectedParameterNames))) {
                    try {
                        return URL::toRoute($route, $parameters, false);
                    } catch (UrlGenerationException $e) {
                    }
                }
            }
        }

        return false;
    }

}
