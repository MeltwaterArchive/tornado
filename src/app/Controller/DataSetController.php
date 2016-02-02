<?php

namespace Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use DataSift\Http\Request;

use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;

/**
 * Returns list of available DataSet
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @author      Daniel Waligora <danielwaligora@gmail.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataSetController
{
    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $dataSetRepository;

    /**
     * @param \Tornado\DataMapper\DataMapperInterface $dataSetRepository
     */
    public function __construct(DataMapperInterface $dataSetRepository)
    {
        $this->dataSetRepository = $dataSetRepository;
    }

    /**
     * Returns list of all available system DataSets
     *
     * @return \Tornado\Controller\Result
     */
    public function index()
    {
        $datasets = $this->dataSetRepository->find();
        return new Result($datasets, ['count' => count($datasets)]);
    }
}
