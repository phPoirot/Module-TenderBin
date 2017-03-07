<?php
namespace Module\TenderBin\Actions;

use Module\Foundation\Actions\IOC;
use Module\TenderBin\Exception\exDuplicateEntry;
use Module\TenderBin\Exception\exResourceNotFound;
use Module\TenderBin\Interfaces\Model\iEntityBindata;
use Module\TenderBin\Interfaces\Model\Repo\iRepoBindata;
use Module\TenderBin\Model\Bindata;
use Module\TenderBin\Model\BindataOwnerObject;
use Poirot\Application\Sapi\Server\Http\ListenerDispatch;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\HttpResponse;
use Poirot\Http\Interfaces\iHttpRequest;
use Psr\Http\Message\UploadedFileInterface;


class FindBinAction
    extends aAction
{
    /** @var iRepoBindata */
    protected $repoBins;


    /**
     * ValidatePage constructor.
     *
     * @param iRepoBindata $repoBins @IoC /module/tenderbin/services/repository/Bindata
     */
    function __construct(iRepoBindata $repoBins)
    {
        $this->repoBins = $repoBins;
    }


    /**
     * Create New Bin and Persist
     *
     * @param string $resource_hash
     * 
     * @return array
     * @throws \Exception
     */
    function __invoke($resource_hash = null)
    {
        if (false === $binData = $this->repoBins->findOneByHash($resource_hash))
            throw new exResourceNotFound(sprintf(
                'Resource (%s) not found.'
                , $resource_hash
            ));
        
        
        return ['binData' => $binData];
    }


    // Action Chain Helpers:

    /**
     * Response BinData Info GET Method
     *
     * @return \Closure
     */
    static function functorResponseGetInfoResult()
    {
        /**
         * @param iEntityBindata $binData
         * @return array
         */
        return function ($binData = null)
        {
            if ($expiration = $binData->getDatetimeExpiration()) {
                $currDateTime   = new \DateTime();
                $currDateTime   = $currDateTime->getTimestamp();
                $expireDateTime = $expiration->getTimestamp();

                $expiration     = $expireDateTime - $currDateTime;
            }

            // TODO available versions
            return [
                ListenerDispatch::RESULT_DISPATCH => [
                    '_self'      => [
                        'hash' => $binData->getIdentifier(),
                    ],
                    'title'        => $binData->getTitle(),
                    'mime_type'    => $binData->getMimeType(),
                    'expiration'   => $expiration,
                    'is_protected' => (boolean) $binData->isProtected(),
                    'meta'         => \Poirot\Std\cast($binData->getMeta())->toArray(function($_, $k) {
                        return substr($k, 0, 2) === '__'; // filter system specific meta data
                    }),
                ],
            ];
        };
    }

    /**
     * Response BinData Info HEAD Method
     *
     * @return \Closure
     */
    static function functorResponseHeadInfoResult()
    {
        /**
         * @param iEntityBindata $binData
         * @return array
         */
        return function ($binData = null)
        {
            if ($expiration = $binData->getDatetimeExpiration()) {
                $currDateTime   = new \DateTime();
                $currDateTime   = $currDateTime->getTimestamp();
                $expireDateTime = $expiration->getTimestamp();

                $expiration     = $expireDateTime - $currDateTime;
            }

            $r = [
                '_self'      => [
                    'hash' => $binData->getIdentifier(),
                ],
                'title'        => $binData->getTitle(),
                'content_type' => $binData->getMimeType(),
                'expiration'   => $expiration,
                'is_protected' => (boolean) $binData->isProtected(),
                'meta'         => \Poirot\Std\cast($binData->getMeta())->toArray(function($_, $k) {
                    return substr($k, 0, 2) === '__'; // filter system specific meta data
                }),
            ];

            // TODO Test Response Headers
            $response = new HttpResponse;
            foreach ($r as $k => $v) {
                if (is_array($v))
                    continue;

                $name = strtr($k, '_', ' ');
                $name = 'X-'.strtr(ucwords(strtolower($name)), ' ', '-');
                $valuable = [$name, $v];
                $header   = FactoryHttpHeader::of($valuable);
                $response->headers()->insert($header);
            }

            return [
                ListenerDispatch::RESULT_DISPATCH => $response,
            ];
        };
    }
}
