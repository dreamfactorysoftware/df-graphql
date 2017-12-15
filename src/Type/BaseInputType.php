<?php

namespace DreamFactory\Core\GraphQL\Type;

class BaseInputType extends BaseType
{
    /*
    * Set the following to true to make the type input object.
    * http://graphql.org/learn/schema/#input-types
    */
    protected $inputObject = true;
}