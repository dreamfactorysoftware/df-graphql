<?php

$schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';

$router->group(array(
    'prefix' => config('graphql.prefix'),
    'middleware' => config('graphql.middleware', [])
), function ($router) use ($schemaParameterPattern) {
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
    if ($queryRoute) {
        // Remove optional parameter in Lumen. Instead, creates two routes.
        if (!$router instanceof \Illuminate\Routing\Router &&
            preg_match($schemaParameterPattern, $queryRoute)
        ) {
            $router->get(preg_replace($schemaParameterPattern, '', $queryRoute), array(
                'as' => 'graphql.query',
                'uses' => $controller
            ));
            $router->get(preg_replace($schemaParameterPattern, '{graphql_schema}', $queryRoute), array(
                'as' => 'graphql.query.with_schema',
                'uses' => $controller
            ));
            $router->post(preg_replace($schemaParameterPattern, '', $queryRoute), array(
                'as' => 'graphql.query.post',
                'uses' => $controller
            ));
            $router->post(preg_replace($schemaParameterPattern, '{graphql_schema}', $queryRoute), array(
                'as' => 'graphql.query.post.with_schema',
                'uses' => $controller
            ));
        } else {
            $router->get($queryRoute, array(
                'as' => 'graphql.query',
                'uses' => $controller
            ));
            $router->post($queryRoute, array(
                'as' => 'graphql.query.post',
                'uses' => $controller
            ));
        }
    }

    //Mutation routes (define only if different than query)
    if ($mutationRoute && $mutationRoute !== $queryRoute) {
        // Remove optional parameter in Lumen. Instead, creates two routes.
        if (!$router instanceof \Illuminate\Routing\Router &&
            preg_match($schemaParameterPattern, $mutationRoute)
        ) {
            $router->post(preg_replace($schemaParameterPattern, '', $mutationRoute), array(
                'as' => 'graphql.mutation',
                'uses' => $controller
            ));
            $router->post(preg_replace($schemaParameterPattern, '{graphql_schema}', $mutationRoute), array(
                'as' => 'graphql.mutation.with_schema',
                'uses' => $controller
            ));
            $router->get(preg_replace($schemaParameterPattern, '', $mutationRoute), array(
                'as' => 'graphql.mutation.get',
                'uses' => $controller
            ));
            $router->get(preg_replace($schemaParameterPattern, '{graphql_schema}', $mutationRoute), array(
                'as' => 'graphql.mutation.get.with_schema',
                'uses' => $controller
            ));
        } else {
            $router->post($mutationRoute, array(
                'as' => 'graphql.mutation',
                'uses' => $controller
            ));
            $router->get($mutationRoute, array(
                'as' => 'graphql.mutation.get',
                'uses' => $controller
            ));
        }
    }
});
