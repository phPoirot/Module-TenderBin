<?php
namespace Module\OAuth2\Services\Repository;

use Module\MongoDriver\Services\aServiceRepository;
use Module\OAuth2\Model\Mongo\BindataRepo;


class BindataRepoService
    extends aServiceRepository
{
    /** @var string Service Name */
    protected $name = 'Bindata';

    
    /**
     * Repository Class Name
     *
     * @return string
     */
    function getRepoClassName()
    {
        return BindataRepo::class;
    }
}
