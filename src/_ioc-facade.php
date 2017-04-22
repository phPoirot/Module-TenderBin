<?php
namespace Module\TenderBin\Actions
{

    /**
     * @property RenderBinAction  $renderBinAction
     * @property CreateBinAction  $createBinAction
     * @property UpdateBinAction  $updateBinAction
     * @property DeleteBinAction  $deleteBinAction
     * @property SearchBinAction  $searchBinAction
     * @property GetMetaBinAction $getMetaBinAction
     *
     */
    class IOC extends \IOC
    { }
}


namespace Module\Content\Services\Repository
{
    use Module\TenderBin\Model\Driver\Mongo\BindataRepo;

    /**
     * @method static BindataRepo Bindata()
     */
    class IOC extends \IOC
    { }
}