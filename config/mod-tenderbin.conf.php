<?php
use Module\HttpRenderer\Services\ServiceRenderStrategiesContainer;
use Module\TenderBin\Events\EventHeapOfTenderBin;
use Module\TenderBin\RenderStrategy\RenderTenderStrategy;

return [

    \Module\TenderBin\Module::CONF_KEY => [

        'validator' => [
            // 'allowed_mime_types' => ['image/*', ],
            //'denied_mime_types'  => ['*', ],
        ],
        'aliases_client' => [
            // 'client_id_alias' => 'client_id',
        ],

        \Module\TenderBin\Actions\aAction::CONF => [
            // Events Section Of Events Builder
            /** @see \Poirot\Events\Event\BuildEvent */
            EventHeapOfTenderBin::BEFORE_CREATE_BIN => [
                'listeners' => [
                    ['priority' => 1000,  'listener' => function($binObject) {
                        // Implement this
                        /** @var \Module\TenderBin\Model\Entity\BindataEntity $binObject */
                    }],
                ],
            ],
            EventHeapOfTenderBin::AFTER_BIN_CREATED => [
                'listeners' => [
                    ['priority' => 1000, 'listener' => function($binObject) {
                        // Implement this
                    }],
                ],
            ]
        ],

    ],

    # Renderer
    #
    \Module\HttpRenderer\Module::CONF => [
        ServiceRenderStrategiesContainer::CONF => [
            'services' => [
                'tenderbin' => RenderTenderStrategy::class,
            ],
        ],
    ],

    # Mongo Driver:
    #
    Module\MongoDriver\Module::CONF_KEY =>
    [
        \Module\MongoDriver\Services\aServiceRepository::CONF_REPOSITORIES =>
        [
            \Module\TenderBin\Model\Driver\Mongo\BindataRepoService::class => [

                // Generate Unique ID While Persis Bin Data
                // with different situation we need vary id generated;
                // when we use storage as URL shortener we need more comfortable hash_id
                // but consider when we store lots of files
                'unique_id_generator' => function($id = null, $self = null)
                {
                    /** @var $self \Module\TenderBin\Model\Driver\Mongo\BindataRepo */
                    // note: currently generated hash allows 14,776,336 unique entry
                    do {
                        $id = \Poirot\Std\generateShuffleCode(4, \Poirot\Std\CODE_NUMBERS | \Poirot\Std\CODE_STRINGS);
                    } while ($self->findOneByHash($id));

                    // TODO iObjectID interface
                    return $id;
                }, // (callable) null = using default

                // ..

                'collection' => [
                    // query on which collection
                    'name' => 'fs.tenderbin',
                    // which client to connect and query with
                    'client' => 'master',
                    // ensure indexes
                    'indexes' => [
                        ['key' => ['_id' => 1]],
                        ['key' => ['meta' => 1]],
                        ['key' => ['mime_type' => 1]],
                        ['key' => ['owner_identifier' => 1]],
                        // db.tenderbin.bins.createIndex({"datetime_expiration_mongo": 1}, {expireAfterSeconds: 0});
                        [ 'key' => ['datetime_expiration_mongo' => 1 ], /*'expireAfterSeconds'=> 0*/],
                        [ 'key' => ['version.subversion_of' => 1 ], 'version.tag'=> 1],
                    ],],],
        ],
    ],
];
