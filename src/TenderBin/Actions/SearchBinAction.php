<?php
namespace Module\TenderBin\Actions;

use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Module\TenderBin\Interfaces\Model\iBindata;
use Module\TenderBin\Interfaces\Model\Repo\iRepoBindata;
use Module\TenderBin\Model\Entity\BindataEntity;
use Poirot\Http\HttpMessage\Request\Plugin\ParseRequestData;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\OAuth2Client\Interfaces\iAccessTokenEntity;
use Poirot\Std\Type\StdArray;


class SearchBinAction
    extends aAction
{
    /** @var iRepoBindata */
    protected $repoBins;


    /**
     * ValidatePage constructor.
     *
     * @param iHttpRequest $httpRequest @IoC /HttpRequest
     * @param iRepoBindata $repoBins    @IoC /module/tenderbin/services/repository/Bindata
     */
    function __construct(iHttpRequest $httpRequest, iRepoBindata $repoBins)
    {
        parent::__construct($httpRequest);

        $this->repoBins = $repoBins;
    }


    /**
     * Search and Filter Stored BinData
     *
     * @param iAccessTokenEntity $token
     *
     *
     * Query Term:
     *
     * [
     *   'meta' => [
     *     'is_file' => [
     *       '$eq' => [
     *         true,
     *       ]
     *     ],
     *     'file_size' => [
     *       '$gt' => [
     *         40000,
     *       ]
     *     ],
     *   ],
     *   'version_tag' => [
     *     '$eq' => [
     *       'latest',
     *       'low_quality',
     *     ],
     *   ],
     * ];
     *
     * @return array
     */
    function __invoke($token = null)
    {
        # Parse Request Query params
        $q      = ParseRequestData::_($this->request)->parseQueryParams();
        // offset is Mongo ObjectID "58e107fa6c6b7a00136318e3"
        $offset = (isset($q['offset'])) ? $q['offset']       : null;
        $limit  = (isset($q['limit']))  ? (int) $q['limit']  : 30;


        # Build Expression Query Term

        // Search Bins Belong To This Owner Determine By Token
        $q['owner_identifier'] = $this->buildOwnerObjectFromToken($token);

        $expression = StdArray::of($q)
            ->exclude(['meta', 'mime_type', 'version', 'owner_identifier'], StdArray::EXCLUDE_ALLOW)
            ->value
        ;

        $bins = $this->repoBins->findAll(
            $expression
            , $offset
            , (int) $limit + 1
        );


        $bins = \Poirot\Std\cast($bins)->toArray();

        // Check whether to display fetch more link in response?
        $linkMore = null;
        if (count($bins) > $limit) {
            array_pop($bins);                     // skip augmented content to determine has more?
            /** @var BindataEntity $nextOffset */
            $nextOffset = $bins[count($bins)-1]; // retrieve the next from this offset (less than this)
            $linkMore   = \Module\HttpFoundation\Actions::url();
            $linkMore   = (string) $linkMore->uri()->withQuery('offset='.($nextOffset->getIdentifier()).'&limit='.$limit);
        }

        # Build Response

        $items = [];
        /** @var iBindata $bin */
        foreach ($bins as $bin) {
            $linkParams = [
                'resource_hash' => $bin->getIdentifier(), ];

            if ( $bin->getMeta()->has('is_file') )
                $linkParams += [
                    'filename' => $bin->getMeta()->get('filename'), ];


            $items[] = \Module\TenderBin\toResponseArrayFromBinEntity($bin)
                + [
                    '_link' => (string) \Module\HttpFoundation\Actions::url(
                        'main/tenderbin/resource/'
                        , $linkParams
                    ),
                ];
        }


        # Build Response:

        $r = [
            'count' => count($items),
            'items' => $items,
            '_link_more' => $linkMore,
            '_self' => [
                'offset' => $offset,
                'limit'  => $limit,
            ],
        ];

        return [
            ListenerDispatch::RESULT_DISPATCH => $r,
        ];
    }
}
