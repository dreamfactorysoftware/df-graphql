<?php

namespace DreamFactory\Core\GraphQL\Query;

use GraphQL\Type\Definition\Type;
use GraphQL;

class BaseListQuery extends BaseQuery
{
    public function type()
    {
        if ($this->type instanceof GraphQL\Type\Definition\ListOfType) {
            return $this->type;
        }
        if (($this->type instanceof GraphQL\Type\Definition\ObjectType) ||
            ($this->type instanceof GraphQL\Type\Definition\ScalarType)) {
            return Type::listOf($this->type);
        }

        return Type::listOf(GraphQL::type($this->type));
    }
}