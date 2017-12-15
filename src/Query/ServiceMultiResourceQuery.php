<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Utility\ResourcesWrapper;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ServiceMultiResourceQuery extends ServiceSingleResourceQuery
{
    public function type()
    {
        return Type::listOf(parent::type());
    }

    /**
     * @param             $root
     * @param             $args
     * @param             $context
     * @param ResolveInfo $info
     * @return array
     * @throws RestException
     * @throws \Exception
     */
    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        $result = parent::resolve($root, $args, $context, $info);
        $response = ResourcesWrapper::unwrapResources($result);

        return $response;
    }
}