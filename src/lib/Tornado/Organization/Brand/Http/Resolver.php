<?php

namespace Tornado\Organization\Brand\Http;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use DataSift\Http\Request;

use Tornado\Organization\Brand;
use Tornado\Organization\Brand\DataMapper as BrandRepository;
use Tornado\Project\Project\DataMapper as ProjectRepository;
use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Workbook\DataMapper as WorkbookRepository;
use Tornado\Project\Worksheet\DataMapper as WorksheetRepository;
use Tornado\Organization\User;

/**
 * Resolver
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Brand\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Resolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const RESOLVER_ATTRIBUTE = '_brand_resolver';

    /**
     * R
     * @var string
     */
    protected $resolverParamKey;

    /**
     * @var SessionInterface|null
     */
    protected $sessionUser = null;

    /**
     * @var \Tornado\Organization\Brand\DataMapper
     */
    protected $brandRepository;

    /**
     * @var \Tornado\Project\Project\DataMapper
     */
    protected $projectRepository;

    /**
     * @var \Tornado\Project\Recording\DataMapper
     */
    protected $recordingRepository;

    /**
     * @var \Tornado\Project\Workbook\DataMapper
     */
    protected $workbookRepository;

    /**
     * @var \Tornado\Project\Worksheet\DataMapper
     */
    protected $worksheetRepository;

    /**
     * @param \Tornado\Organization\Brand\DataMapper $brandRepository
     * @param \Tornado\Project\Project\DataMapper    $projectRepository
     * @param \Tornado\Project\Recording\DataMapper  $recordingRepository
     * @param \Tornado\Project\Workbook\DataMapper   $workbookRepository
     * @param \Tornado\Project\Worksheet\DataMapper  $worksheetRepository
     * @param \Tornado\Organization\User             $user
     */
    public function __construct(
        BrandRepository $brandRepository,
        ProjectRepository $projectRepository,
        RecordingRepository $recordingRepository,
        WorkbookRepository $workbookRepository,
        WorksheetRepository $worksheetRepository,
        User $user = null
    ) {
        $this->sessionUser = $user;
        $this->brandRepository = $brandRepository;
        $this->projectRepository = $projectRepository;
        $this->recordingRepository = $recordingRepository;
        $this->workbookRepository = $workbookRepository;
        $this->worksheetRepository = $worksheetRepository;
        $this->logger = $this->logger ?: new NullLogger();
    }

    /**
     * Resolves Brand based on the Request data.
     *
     * It does not check if user is granted to access given the resource because it isn't the scope
     * of this class and should be provided in further application dispatching, for instance by
     * AccessDecisionManger.
     *
     * @param \DataSift\Http\Request $request
     *
     * @return Brand|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function resolve(Request $request)
    {
        if (!$this->sessionUser) {
            return;
        }

        if (!$resolverParam = $this->getResolverParam($request)) {
            $this->logger->info(sprintf(
                '%s: no needs to resolve Brand for user "%s"[id: %d] and request params: %s.',
                __METHOD__,
                $this->sessionUser->getUsername(),
                $this->sessionUser->getId(),
                json_encode($request->attributes->all())
            ));

            return;
        }

        if ('brandId' === $this->resolverParamKey) {
            $brand = $this->brandRepository->findOne(['id' => $resolverParam]);
        } elseif ('projectId' === $this->resolverParamKey) {
            $brand = $this->resolveByProjectId($resolverParam);
        } elseif ('recordingId' === $this->resolverParamKey) {
            $recording = $this->recordingRepository->findOne(['id' => $resolverParam]);
            $brand = $this->brandRepository->findOne(['id' => $recording->getBrandId()]);
        } elseif ('workbookId' === $this->resolverParamKey) {
            $brand = $this->resolveByWorkbookId($resolverParam);
        } elseif ('worksheetId' === $this->resolverParamKey) {
            $worksheet = $this->worksheetRepository->findOne(['id' => $resolverParam]);
            $brand = $this->resolveByWorkbookId($worksheet->getWorkbookId());
        }

        if ($brand) {
            $this->logger->info(sprintf(
                '%s: resolved Brand "%s"[brand_id: %d] for user "%s"[id: %d] and request params: %s',
                __METHOD__,
                $brand->getName(),
                $brand->getId(),
                $this->sessionUser->getUsername(),
                $this->sessionUser->getId(),
                json_encode($request->attributes->all())
            ));
        }

        return $brand;
    }

    /**
     * Normalizes Request post params for easier and unified processing.
     *
     * @param array $postParams
     *
     * @return array
     */
    protected function normalizePostParams(array $postParams)
    {
        $map = [
            'brand_id' => 'brandId',
            'project_id' => 'projectId',
            'recording_id' => 'recordingId',
            'workbook_id' => 'workbookId',
            'worksheet_id' => 'worksheetId',
        ];

        foreach ($postParams as $param => $value) {
            if (array_key_exists($param, $map)) {
                unset($postParams[$param]);
                $postParams[$map[$param]] = $value;
            }
        }

        return $postParams;
    }

    /**
     * Resolves Brand by projectId request param
     *
     * @param int $projectId
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    protected function resolveByProjectId($projectId)
    {
        $project = $this->projectRepository->findOne(['id' => $projectId]);

        if (!$project) {
            return null;
        }

        return $this->brandRepository->findOne(['id' => $project->getBrandId()]);
    }

    /**
     * Resolves Brand by workbookId request param
     *
     * @param int $workbookId
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    protected function resolveByWorkbookId($workbookId)
    {
        $workbook = $this->workbookRepository->findOne(['id' => $workbookId]);

        if (!$workbook) {
            return null;
        }

        return $this->resolveByProjectId($workbook->getProjectId());
    }

    /**
     * Finds param by which Resolver finds Brand which is 2 step process.
     * Initially Resolver checks request attributes, if resolver param not found and request
     * is POST, PUT or PATCH than checks post params.
     *
     * @param \DataSift\Http\Request $request
     *
     * @return mixed
     */
    protected function getResolverParam(Request $request)
    {
        // checks if route is "tagged" as route to Brand resolve
        if (!$request->attributes->has(self::RESOLVER_ATTRIBUTE)) {
            return;
        }

        $this->resolverParamKey = $request->attributes->get(self::RESOLVER_ATTRIBUTE);

        // checks if resolver param is in request attributes
        if ($request->attributes->has($this->resolverParamKey)) {
            return $request->attributes->get($this->resolverParamKey);
        }

        // checks if resolver param is in post params
        if (in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
            $postParams = $this->normalizePostParams($request->getPostParams());

            if (isset($postParams[$this->resolverParamKey])) {
                return $postParams[$this->resolverParamKey];
            }
        }
    }
}
