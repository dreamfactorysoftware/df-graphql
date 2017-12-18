<?php

$router->group([
    'prefix'     => config('graphql.prefix'),
    'middleware' => ['df.auth_check', 'df.access_check']
], function ($router) {
    //Get routes from config
    $routes = config('graphql.routes');
    $queryRoute = null;
    $mutationRoute = null;
    if (is_array($routes)) {
        $queryRoute = array_get($routes, 'query', null);
        $mutationRoute = array_get($routes, 'mutation', null);
    } else {
        $queryRoute = $routes;
        $mutationRoute = $routes;
    }

    $controller = '\DreamFactory\Core\GraphQL\Http\Controllers\GraphQLController@query';

    //Query
    if (isset($queryRoute)) {
        $router->get($queryRoute, ['as' => 'graphql.query', 'uses' => $controller]);
        $router->post($queryRoute, ['as' => 'graphql.query.post', 'uses' => $controller]);
    }

    //Mutation routes (define only if different than query)
    if (isset($mutationRoute) && ($mutationRoute !== $queryRoute)) {
        $router->post($mutationRoute, ['as' => 'graphql.mutation', 'uses' => $controller]);
        $router->get($mutationRoute, ['as' => 'graphql.mutation.get', 'uses' => $controller]);
    }
});
