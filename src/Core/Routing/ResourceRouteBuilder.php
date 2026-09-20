<?php

declare(strict_types=1);

namespace Nqphp\Core\Routing;

/**
 * Helper for generating standard RESTful resource routes (index, show, store, update, destroy).
 */
final class ResourceRouteBuilder
{
    /**
     * Register RESTful resource routes on a RouteCollector instance.
     *
     * @param RouteCollector $collector
     * @param string $name Resource name (e.g. 'posts', 'users')
     * @param string $controller Controller class name
     * @param array{
     *     only?: list<string>,
     *     except?: list<string>,
     *     id_pattern?: string
     * } $options
     */
    public static function register(
        RouteCollector $collector,
        string $name,
        string $controller,
        array $options = []
    ): void {
        $cleanName = trim($name, '/');
        $singular = rtrim($cleanName, 's');
        if ($singular === '') {
            $singular = 'item';
        }

        $idPattern = $options['id_pattern'] ?? '\d+';
        $only = isset($options['only']) ? (array) $options['only'] : null;
        $except = isset($options['except']) ? (array) $options['except'] : [];

        $actions = [
            'index'   => ['GET',    '/' . $cleanName,                   $controller . '::index',   $cleanName . '.index',   []],
            'store'   => ['POST',   '/' . $cleanName,                   $controller . '::store',   $cleanName . '.store',   []],
            'show'    => ['GET',    '/' . $cleanName . '/{' . $singular . '}', $controller . '::show',    $cleanName . '.show',    [$singular => $idPattern]],
            'update'  => ['PUT',    '/' . $cleanName . '/{' . $singular . '}', $controller . '::update',  $cleanName . '.update',  [$singular => $idPattern]],
            'patch'   => ['PATCH',  '/' . $cleanName . '/{' . $singular . '}', $controller . '::update',  $cleanName . '.patch',   [$singular => $idPattern]],
            'destroy' => ['DELETE', '/' . $cleanName . '/{' . $singular . '}', $controller . '::destroy', $cleanName . '.destroy', [$singular => $idPattern]],
        ];

        foreach ($actions as $actionKey => [$method, $path, $handler, $routeName, $requirements]) {
            if ($only !== null && !in_array($actionKey, $only, true)) {
                continue;
            }
            if (in_array($actionKey, $except, true)) {
                continue;
            }

            $collector->add(
                [$method],
                $path,
                $handler,
                $routeName,
                $requirements
            );
        }
    }
}
