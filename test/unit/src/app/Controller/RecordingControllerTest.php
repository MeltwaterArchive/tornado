<?php

namespace Test\Controller;

use Mockery;

use Controller\RecordingController;

use Tornado\Project\Recording;

use Test\DataSift\ReflectionAccess;

/**
 * RecordingControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Controller\RecordingController
 */
class RecordingControllerTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     */
    public function testIsBrandDataAware()
    {
        $controller = $this->getController($this->getMocks());
        $this->assertInstanceOf('Tornado\Controller\Brand\DataAwareInterface', $controller);
    }

    /**
     * @covers ::createForm
     */
    public function testCreateForm()
    {
        $mocks = $this->getMocks();
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);

        $mocks['brand']->shouldReceive('getTargetPermissions')
            ->andReturn([])
            ->once();

        $dimensions = [
            ['target' => 'fb.author.gender'],
            ['target' => 'fb.author.age'],
            ['target' => 'fb.author.location']
        ];
        $targets = ['fb.author.gender', 'fb.author.age', 'fb.author.location'];

        $mocks['schema']->shouldReceive('getObjects')
            ->andReturn($dimensions)
            ->once();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->createForm($mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);

        $this->assertArrayHasKey('targets', $resultData);
        $this->assertEquals($targets, $resultData['targets']);
    }

    /**
     * @covers ::create
     */
    public function testCreate()
    {
        $mocks = $this->getMocks();
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['postParams']);
        $mocks['createForm']->shouldReceive('submit')
            ->once()
            ->with(array_merge($mocks['postParams'], ['brand_id' => $mocks['brandId']]))
            ->andReturn(true);
        $mocks['createForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(true);
        $mocks['createForm']->shouldReceive('getData')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['recording']);
        $mocks['createForm']->shouldReceive('getErrors')
            ->never();
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['recordingRepository']->shouldReceive('create')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(true);
        $mocks['dataSiftRecording']->shouldReceive('start')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->once()
            ->with('recording.update_form', ['recordingId' => $mocks['recordingId']])
            ->andReturn('/recordings/' . $mocks['recordingId'] . '/edit');

        $ctrl = $this->getController($mocks);
        $result = $ctrl->create($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(201, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->assertArrayHasKey('redirect_uri', $resultMeta);

        $this->assertEquals('/recordings/' . $mocks['recordingId'] . '/edit', $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::create
     */
    public function testCreateUnlessDataSiftRecordingFailed()
    {
        $mocks = $this->getMocks();
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['postParams']);
        $mocks['createForm']->shouldReceive('submit')
            ->once()
            ->with(array_merge($mocks['postParams'], ['brand_id' => $mocks['brandId']]))
            ->andReturn(true);
        $mocks['createForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(true);
        $mocks['createForm']->shouldReceive('getData')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['recording']);
        $mocks['createForm']->shouldReceive('getErrors')
            ->never();
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['recordingRepository']->shouldReceive('create')
            ->never();
        $mocks['dataSiftRecordingException']->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(429);
        $mocks['dataSiftRecording']->shouldReceive('start')
            ->once()
            ->with($mocks['recording'])
            ->andThrow($mocks['dataSiftRecordingException']);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->create($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(429, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);
        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertEquals($mocks['brand'], $resultData['selectedBrand']);
        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->assertArrayHasKey('csdl', $resultMeta);
    }

    /**
     * @covers ::create
     */
    public function testCreateUnlessFormInvalid()
    {
        $mocks = $this->getMocks();
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['postParams']);
        $mocks['createForm']->shouldReceive('submit')
            ->once()
            ->with(array_merge($mocks['postParams'], ['brand_id' => $mocks['brandId']]))
            ->andReturn(true);
        $mocks['createForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(false);
        $mocks['createForm']->shouldReceive('getData')
            ->never();
        $mocks['createForm']->shouldReceive('getErrors')
            ->once()
            ->withNoArgs()
            ->andReturn(['name' => 'Invalid name']);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['recordingRepository']->shouldReceive('create')
            ->never();
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->create($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('name', $resultMeta);
        $this->assertEquals('Invalid name', $resultMeta['name']);
    }

    /**
     * @covers ::getRecording
     */
    public function testGetRecording()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);

        $ctrl = $this->getController($mocks);
        $result = $this->invokeMethod($ctrl, 'getRecording', ['recordingId' => $mocks['recordingId']]);

        $this->assertInstanceOf('\Tornado\Project\Recording', $result);
    }

    /**
     * @covers ::getRecording
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetRecordingUnlessNotFound()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn(null);
        $mocks['authManager']->shouldReceive('isGranted')
            ->never();

        $ctrl = $this->getController($mocks);
        $this->invokeMethod($ctrl, 'getRecording', ['recordingId' => $mocks['recordingId']]);
    }

    /**
     * @covers ::getRecording
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testGetRecordingUnlessAccessDenied()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(false);

        $ctrl = $this->getController($mocks);
        $this->invokeMethod($ctrl, 'getRecording', ['recordingId' => $mocks['recordingId']]);
    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['recordingId']])
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['dataSiftRecording']->shouldReceive('delete')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->once()
            ->with('brand.get.recordings', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId'] . '/recordings');

        $ctrl = $this->getController($mocks);
        $result = $ctrl->delete($mocks['recordingId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultMeta = $result->getMeta();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultMeta);

        $this->arrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteUnlessDataSiftRecordingFailed()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['recordingId']])
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['dataSiftRecordingException']->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(429);
        $mocks['dataSiftRecording']->shouldReceive('delete')
            ->once()
            ->with($mocks['recording'])
            ->andThrow($mocks['dataSiftRecordingException']);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->delete($mocks['recordingId']);

        $resultData = $result->getData();
        $this->assertEquals(429, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('error', $resultMeta);
    }

    /**
     * @covers ::updateForm
     */
    public function testUpdateForm()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);

        $ctrl = $this->getController($mocks);
        $result = $ctrl->updateForm($mocks['recordingId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);
    }

    /**
     * @covers ::update
     */
    public function testUpdate()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->never();
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['postParams']);
        $mocks['updateForm']->shouldReceive('submit')
            ->once()
            ->with(array_merge($mocks['postParams'], ['brand_id' => $mocks['brandId']]), $mocks['recording'])
            ->andReturn(true);
        $mocks['updateForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(true);
        $mocks['updateForm']->shouldReceive('getData')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['recording']);
        $mocks['createForm']->shouldReceive('getErrors')
            ->never();
        $mocks['recordingRepository']->shouldReceive('update')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->once()
            ->with('recording.update_form', ['recordingId' => $mocks['recordingId']])
            ->andReturn('/recordings/' . $mocks['recordingId'] . '/edit');

        $ctrl = $this->getController($mocks);
        $result = $ctrl->update($mocks['request'], $mocks['recordingId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertEquals('/recordings/' . $mocks['recordingId'] . '/edit', $result->getTargetUrl());
    }

    /**
     * @covers ::update
     */
    public function testUpdateUnlessFormInvalid()
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['postParams']);
        $mocks['updateForm']->shouldReceive('submit')
            ->once()
            ->with(array_merge($mocks['postParams'], ['brand_id' => $mocks['brandId']]), $mocks['recording'])
            ->andReturn(true);
        $mocks['updateForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(false);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['updateForm']->shouldReceive('getData')
            ->never();
        $mocks['updateForm']->shouldReceive('getErrors')
            ->once()
            ->withNoArgs()
            ->andReturn(['name' => 'Invalid name']);
        $mocks['recordingRepository']->shouldReceive('update')
            ->never();
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->update($mocks['request'], $mocks['recordingId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('name', $resultMeta);
        $this->assertEquals('Invalid name', $resultMeta['name']);
    }

    /**
     * @covers ::pause
     */
    public function testPause()
    {
        $mocks = $this->getMocks();
        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['recording']->setHash($mocks['csdlHash']);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['recordingRepository']->shouldReceive('update')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['dataSiftRecording']->shouldReceive('pause')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->once()
            ->with('brand.get.recordings', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId'] . '/recordings');

        $ctrl = $this->getController($mocks);
        $result = $ctrl->pause($mocks['recordingId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('recording', $resultData);
        $this->assertInstanceOf('Tornado\Project\Recording', $resultData['recording']);
        $this->assertSame($mocks['recording'], $resultData['recording']);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::pause
     */
    public function testPauseUnlessDataSiftRecordingFailed()
    {
        $mocks = $this->getMocks();
        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['recording']->setHash($mocks['csdlHash']);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['recordingRepository']->shouldReceive('update')
            ->never();
        $mocks['dataSiftRecordingException']->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(429);
        $mocks['dataSiftRecording']->shouldReceive('pause')
            ->once()
            ->with($mocks['recording'])
            ->andThrow($mocks['dataSiftRecordingException']);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->pause($mocks['recordingId']);

        $resultData = $result->getData();
        $this->assertEquals(429, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('error', $resultMeta);
    }

    /**
     * @covers ::resume
     */
    public function testResume()
    {
        $mocks = $this->getMocks();
        $mocks['recording']->setStatus(Recording::STATUS_STOPPED);
        $mocks['recording']->setHash($mocks['csdlHash']);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['recordingRepository']->shouldReceive('update')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['dataSiftRecording']->shouldReceive('resume')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->once()
            ->with('brand.get.recordings', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId'] . '/recordings');

        $ctrl = $this->getController($mocks);
        $result = $ctrl->resume($mocks['recordingId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('recording', $resultData);
        $this->assertInstanceOf('Tornado\Project\Recording', $resultData['recording']);
        $this->assertSame($mocks['recording'], $resultData['recording']);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::resume
     */
    public function testResumeUnlessDataSiftRecordingFailed()
    {
        $mocks = $this->getMocks();
        $mocks['recording']->setStatus(Recording::STATUS_STOPPED);
        $mocks['recording']->setHash($mocks['csdlHash']);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->once()
            ->andReturn($mocks['recording']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['recording'])
            ->once()
            ->andReturn(true);
        $mocks['recordingRepository']->shouldReceive('update')
            ->never();
        $mocks['dataSiftRecordingException']->shouldReceive('getStatusCode')
            ->once()
            ->once()
            ->withNoArgs()
            ->andReturn(429);
        $mocks['dataSiftRecording']->shouldReceive('resume')
            ->once()
            ->with($mocks['recording'])
            ->andThrow($mocks['dataSiftRecordingException']);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $result = $ctrl->resume($mocks['recordingId']);

        $resultData = $result->getData();
        $this->assertEquals(429, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);

        $this->assertArrayHasKey('error', $resultMeta);
    }

    /**
     * @covers ::batch
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testThrowExceptionUnlessSupportedBatchActionCalled()
    {
        $mocks = $this->getMocks();
        $ids = [1,2];
        $postParams = ['action' => 'notSupported', 'ids' => $ids];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $ctrl->batch($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::batch
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testThrowExceptionUnlessBatchDeleteActionGiven()
    {
        $mocks = $this->getMocks();
        $ids = [1,2];
        $postParams = ['ids' => $ids];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();

        $ctrl = $this->getController($mocks);
        $ctrl->batch($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::batch
     */
    public function testBatchDeleteRedirectUnlessIdsGiven()
    {
        $mocks = $this->getMocks();
        $mocks['urlGenerator']->shouldReceive('generate')
            ->with('brand.get.recordings', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId'] . '/recordings');
        $mocks['recordingRepository']->shouldReceive('findRecordingsByBrand')
            ->never();

        // empty ids
        $ids = [];
        $postParams = ['action' => 'Delete', 'ids' => $ids];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $ctrl = $this->getController($mocks);
        $result = $ctrl->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('\Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->arrayHasKey('redirect_uri', $resultMeta['redirect_uri']);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);

        // missing ids
        $postParams = ['action' => 'Delete'];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $ctrl = $this->getController($mocks);
        $result = $ctrl->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('\Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->arrayHasKey('redirect_uri', $resultMeta['redirect_uri']);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);

        // no array ids
        $ids = 'noArray';
        $postParams = ['action' => 'Delete', 'ids' => $ids];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $ctrl = $this->getController($mocks);
        $result = $ctrl->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('\Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->arrayHasKey('redirect_uri', $resultMeta['redirect_uri']);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::batch
     * @covers ::batchDelete
     */
    public function testBatchDelete()
    {
        $mocks = $this->getMocks();
        $ids = [1,2,3,4,10];
        $postParams = ['action' => 'Delete', 'ids' => $ids];

        $recordings = [$mocks['recording']];
        for ($i = 1; $i < 5; $i++) {
            $rec = new Recording();
            $rec->setId($i);
            $recordings[] = $rec;
        }

        $mocks['dataSiftRecording']->shouldReceive('deleteRecordings')
            ->once()
            ->with($recordings)
            ->andReturn(true);
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $mocks['recordingRepository']->shouldReceive('findRecordingsByBrand')
            ->once()
            ->with($mocks['brand'], $ids)
            ->andReturn($recordings);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->with('brand.get.recordings', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId'] . '/recordings');

        $ctrl = $this->getController($mocks);
        $result = $ctrl->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('\Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->arrayHasKey('redirect_uri', $resultMeta['redirect_uri']);
        $this->assertEquals('/brands/' . $mocks['brandId'] . '/recordings', $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::batch
     * @covers ::batchDelete
     */
    public function testBatchDeleteUnlessDataSiftRecordingRaiseError()
    {
        $mocks = $this->getMocks();
        $ids = [1,2,3,4,10];
        $postParams = ['action' => 'Delete', 'ids' => $ids];

        $recordings = [$mocks['recording']];
        for ($i = 1; $i < 5; $i++) {
            $rec = new Recording();
            $rec->setId($i);
            $recordings[] = $rec;
        }

        $mocks['dataSiftRecordingException']->shouldReceive('getStatusCode')
            ->once()
            ->withNoArgs()
            ->andReturn(429);
        $mocks['dataSiftRecording']->shouldReceive('deleteRecordings')
            ->once()
            ->with($recordings)
            ->andThrow($mocks['dataSiftRecordingException']);
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($postParams);
        $mocks['recordingRepository']->shouldReceive('findRecordingsByBrand')
            ->once()
            ->with($mocks['brand'], $ids)
            ->andReturn($recordings);

        $ctrl = $this->getController($mocks);
        $result = $ctrl->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('\Tornado\Controller\Result', $result);
        $this->assertEquals(429, $result->getHttpCode());

        $resultMeta = $result->getMeta();
        $this->assertInternalType('array', $resultMeta);
        $this->arrayHasKey('error', $resultMeta);
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $brandId = 10;
        $brand = Mockery::mock('Tornado\Organization\Brand', [
            'getId' => $brandId,
            'getPrimaryKey' => $brandId
        ]);

        $brands = [$brand];
        for ($i = 1; $i < 5; $i++) {
            $brands[] = Mockery::mock('Tornado\Organization\Brand', [
                'getPrimaryKey' => $i,
                'getId' => $i
            ]);
        }

        $recordingId = 10;
        $recording = new Recording();
        $recording->setId($recordingId);
        $recording->setBrandId($brandId);

        $session = Mockery::mock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $urlGenerator = Mockery::mock('Symfony\Component\Routing\Generator\UrlGenerator');
        $brandRepository = Mockery::mock('Tornado\Organization\Brand\DataMapper');
        $recordingRepository = Mockery::mock('Tornado\Project\Recording\DataMapper');
        $createForm = Mockery::mock('Tornado\Project\Recording\Form\Create');
        $updateForm = Mockery::mock('Tornado\Project\Recording\Form\Update');
        $authManager = Mockery::mock('Tornado\Security\Authorization\AccessDecisionManagerInterface');
        $user = Mockery::mock('Tornado\Organization\User');

        $schema = Mockery::mock('DataSift\Pylon\Schema\Schema');
        $schemaProvider = Mockery::mock('DataSift\Pylon\Schema\Provider', [
            'getSchema' => $schema
        ]);

        $dataSiftRecording = Mockery::mock('\Tornado\Project\Recording\DataSiftRecording');
        $dataSiftRecordingException = Mockery::mock('\Tornado\Project\Recording\DataSiftRecordingException', [
            'getMessage' => 'Error'
        ]);

        $session->shouldReceive('get')
            ->with('user')
            ->andReturn($user);
        $request = Mockery::mock('DataSift\Http\Request');
        $postParams = ['name' => 'recording1'];
        $csdlHash = '32414c6fc9f822f4d5c7add027031939';

        return [
            'brandId' => $brandId,
            'brand' => $brand,
            'brands' => $brands,
            'session' => $session,
            'urlGenerator' => $urlGenerator,
            'brandRepository' => $brandRepository,
            'recordingRepository' => $recordingRepository,
            'createForm' => $createForm,
            'updateForm' => $updateForm,
            'authManager' => $authManager,
            'user' => $user,
            'request' => $request,
            'postParams' => $postParams,
            'recordingId' => $recordingId,
            'recording' => $recording,
            'dataSiftRecording' => $dataSiftRecording,
            'csdlHash' => $csdlHash,
            'dataSiftRecordingException' => $dataSiftRecordingException,
            'schemaProvider' => $schemaProvider,
            'schema' => $schema
        ];
    }

    /**
     * @param array $mocks
     *
     * @return RecordingController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(RecordingController::class, [
            $mocks['session'],
            $mocks['urlGenerator'],
            $mocks['dataSiftRecording'],
            $mocks['recordingRepository'],
            $mocks['createForm'],
            $mocks['updateForm'],
            $mocks['schemaProvider']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock Brand\DataAwareTrait methods
        $controller->shouldReceive('getBrand')
            ->with($mocks['brandId'])
            ->andReturn($mocks['brand']);

        $controller->setBrandRepository($mocks['brandRepository']);
        $controller->setAuthorizationManager($mocks['authManager']);

        return $controller;
    }
}
