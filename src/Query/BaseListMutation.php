<?php

namespace DreamFactory\Core\GraphQL\Query;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL;

class BaseListMutation extends Mutation
{
    public function type()
    {
        return Type::listOf(GraphQL::type(array_get($this->attributes, 'type')));
    }

    public function args()
    {
        return array_get($this->attributes, 'args');
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        if ($function = array_get($this->attributes, 'resolve')) {
            return $function($root, $args, $context, $info);
        }

        return [];
    }
}