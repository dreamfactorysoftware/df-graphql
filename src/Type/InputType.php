<?php

namespace DreamFactory\Core\GraphQL\Type;

use GraphQL\Type\Definition\InputObjectType;

class InputType extends Type
{
    public function toType()
    {
        return new InputObjectType($this->toArray());
    }
}
