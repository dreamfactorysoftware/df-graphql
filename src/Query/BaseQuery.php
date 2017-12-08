<?php

namespace DreamFactory\Core\GraphQL\Query;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL;

class BaseQuery extends Query
{
    protected $type;
    protected $args;
    protected $resolver;

    public function __construct($attributes = [])
    {
        $this->type = array_get($attributes, 'type');
        $this->args = array_get($attributes, 'args');
        $this->resolver = array_get($attributes, 'resolve');

        parent::__construct(array_except($attributes, ['type','args','resolve']));
    }

    public function type()
    {
        if (($this->type instanceof GraphQL\Type\Definition\ScalarType) ||
            ($this->type instanceof GraphQL\Type\Definition\ObjectType) ||
            ($this->type instanceof GraphQL\Type\Definition\ListOfType)) {
            return $this->type;
        }

        return GraphQL::type($this->type);
    }

    public function args()
    {
        return (array)$this->args;
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        if ($this->resolver) {
            $method = $this->resolver;
            return $method($root, $args, $context, $info);
        }

        return [];
    }
}