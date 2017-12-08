<?php
namespace DreamFactory\Core\GraphQL\Contracts;

/**
 * Something that can handle requests
 */
interface GraphQLHandlerInterface
{
    /**
     *
     * @return array
     */
    public function getGraphQLSchema();
}
