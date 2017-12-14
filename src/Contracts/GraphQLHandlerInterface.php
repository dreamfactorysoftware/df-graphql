<?php
namespace DreamFactory\Core\GraphQL\Contracts;

/**
 * Something that can handle requests
 */
interface GraphQLHandlerInterface
{
    /**
     *
     * @param bool $refresh
     * @return array
     */
    public function getGraphQLSchema($refresh = false);
}
