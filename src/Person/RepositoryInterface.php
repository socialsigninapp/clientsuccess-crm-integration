<?php

namespace SocialSignIn\ClientSuccessIntegration\Person;

interface RepositoryInterface
{
    /**
     * @param string $query
     *
     * @return Entity[]
     */
    public function search($query);

    /**
     * @param string $id
     *
     * @return Entity|null
     */
    public function get($id);
}
