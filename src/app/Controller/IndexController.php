<?php

namespace Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use DataSift\Http\Request;

use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\Paginator;

/**
 * Lists all necessary data for the dashboard homepage. So far, it is user project list & brands.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class IndexController
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var \Tornado\Organization\Brand\DataMapper
     */
    protected $brandRepository;

    /**
     * Projects repository.
     *
     * @var \Tornado\Project\Project\DataMapper
     */
    protected $projectRepository;

    /**
     * @param SessionInterface $session
     * @param DataMapperInterface                    $brandRepository
     * @param DataMapperInterface                    $projectRepository
     */
    public function __construct(
        SessionInterface $session,
        DataMapperInterface $brandRepository,
        DataMapperInterface $projectRepository
    ) {
        $this->session = $session;
        $this->brandRepository = $brandRepository;
        $this->projectRepository = $projectRepository;
    }

    /**
     * Displays an index page with list of all User brands and first fetched Brand projects.
     *
     * @param Request $request
     *
     * @return Result
     */
    public function index(Request $request)
    {
        $sessionUser = $this->session->get('user');
        $brands = $this->brandRepository->findUserAssigned($sessionUser);
        $projects = [];
        $paginator = [];
        foreach ($brands as $index => $brand) {
            if (0 === $index) {
                $paginator = new Paginator(
                    $this->projectRepository,
                    $request->get('page', 1),
                    $request->get('sort', 'name'),
                    $request->get('perPage', 5),
                    $request->get('order', DataMapperInterface::ORDER_ASCENDING)
                );
                $paginator->paginate(['brand_id' => $brand->getId()]);
                $projects = $paginator->getCurrentItems();
                $brand->projects = $projects;
            }
        }

        return new Result(
            [
                'selectedBrand' => count($brands) > 0 ? $brands[0] : null,
                'brands' => $brands
            ],
            [
                'brands' => ['count' => count($brands)],
                'projects' => ['count' => count($projects)],
                'pagination' => $paginator
            ]
        );
    }
}
