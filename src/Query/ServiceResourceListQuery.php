<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\Exceptions\RestException;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ServiceResourceListQuery extends ServiceMultiResourceQuery
{
    public function type()
    {
        return Type::listOf(Type::string());
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
        $args['as_list'] = true;

        return parent::resolve($root, $args, $context, $info);
    }

    protected function getFieldSelection(ResolveInfo $info)
    {
        return []; // no fields expected here
    }
}