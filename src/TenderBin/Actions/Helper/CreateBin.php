<?php
namespace Module\TenderBin\Actions\Helper;

use Module\TenderBin\Actions\aAction;
use Module\TenderBin\Events\EventHeapOfTenderBin;
use Module\TenderBin\Interfaces\Model\iBindata;
use Module\TenderBin\Model\Entity;
use Module\TenderBin\Interfaces\Model\Repo\iRepoBindata;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Psr7\UploadedFile;
use Poirot\Std\Exceptions\exUnexpectedValue;
use Poirot\Stream\Psr\StreamBridgeFromPsr;
use Poirot\Stream\Streamable\STemporary;


class CreateBin
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
     * Store Given Entity Bin Data
     *
     * @param iBindata $entity
     *
     * @return array
     * @throws \Exception
     */
    function __invoke(iBindata $entity = null)
    {
        try
        {
            $this->_assertValidateEntity($entity);

        } catch (exUnexpectedValue $e)
        {
            // TODO Handle Validation ...
            throw new exUnexpectedValue('Validation Failed', null,  400, $e);
        }



        # Persist Data
        #
        $uploadStream = null;
        if ($entity->getContent() instanceof UploadedFile) {
            // This stream is seekable
            $uploadStream = new STemporary(
                new StreamBridgeFromPsr( $entity->getContent()->getStream() )
            );
        }


        // Event
        //
        $e = $this->event()
            ->trigger(EventHeapOfTenderBin::BEFORE_CREATE_BIN, [
                /** @see DataCollector */
                'binObject' => $entity, 'uploadStream' => $uploadStream,
            ]);


        $pBinEntity = $this->repoBins->insert($entity);
        $pBinID     = $pBinEntity->getIdentifier();


        // Event
        //
        try {

            $e->trigger(EventHeapOfTenderBin::AFTER_BIN_CREATED, [
                /** @see DataCollector */
                'binObject' => clone $pBinEntity
            ]);

        } catch (\Exception $e) {
            // Delete Bin Stored If We Have Error ....
            $this->repoBins->deleteOneByHash($pBinID);

            throw $e;
        }


        return $pBinEntity;
    }


    // ..

    /**
     * Assert Validate Entity By System Settings
     *
     * @param $entity
     *
     * @throws exUnexpectedValue
     */
    private function _assertValidateEntity($entity)
    {
        $validatorConfig = $this->sapi()->config()
            ->get(\Module\TenderBin\Module::CONF_KEY);

        __(new Entity\BindataValidate($entity, $validatorConfig['validator']))
            ->assertValidate();
    }

}