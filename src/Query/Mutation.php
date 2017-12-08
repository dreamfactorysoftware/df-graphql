<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\GraphQL\Components\Field;
use DreamFactory\Core\GraphQL\Components\ShouldValidate;

class Mutation extends Field
{
    use ShouldValidate;
}
