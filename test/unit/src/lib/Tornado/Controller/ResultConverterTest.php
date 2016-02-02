<?php
namespace Test\Tornado\ResponseConverter;

use Mockery;
use Symfony\Component\HttpFoundation\Request;

use Tornado\Controller\ResultConverter;

/**
 * @coversDefaultClass \Tornado\Controller\ResultConverter
 */
class ResultConverterTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     */
    public function testNotReplacingAlreadyResponse()
    {
        $twig = Mockery::mock('Twig_Environment');
        $event = Mockery::mock('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent', [
            'getControllerResult' => Mockery::mock('Symfony\Component\HttpFoundation\Response')
        ]);
        $event->shouldNotReceive('setResponse');

        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     */
    public function testConvertingToJsonResponse()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html;q=0.6,application/json;q=0.8,*/*;q=0.5'
        ]);


        $resultData = [
            'lorem' => 'ipsum',
            'dolor' => 'sitamet'
        ];
        $resultMetaData = new \StdClass();
        $responseContent = [
            'data' => $resultData,
            'meta' => $resultMetaData
        ];

        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => $resultData,
            'getMeta' => new \StdClass(),
            'getHttpCode' => 200
        ]);

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getControllerResult')
            ->will($this->returnValue($result));

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) use ($responseContent) {
                $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
                $this->assertSame(json_encode($responseContent), $response->getContent());
                return true;
            }));

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $twig = Mockery::mock('Twig_Environment');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     */
    public function testConvertingToJsonResponseWithHttpCode()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html;q=0.6,application/json;q=0.8,*/*;q=0.5'
        ]);

        $resultData = [];
        $resultMetaData = 'Invalid form';

        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => $resultData,
            'getMeta' => $resultMetaData,
            'getHttpCode' => 400
        ]);

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getControllerResult')
            ->will($this->returnValue($result));

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) use ($resultMetaData) {
                $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
                $this->assertSame(
                    json_encode(['data' => new \StdClass, 'meta' => $resultMetaData]),
                    $response->getContent()
                );
                $this->assertEquals(400, $response->getStatusCode());
                return true;
            }));

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $twig = Mockery::mock('Twig_Environment');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     */
    public function testRenderingExplicitTemplate()
    {
        $resultData = [
            'lorem' => 'ipsum',
            'dolor' => 'sitamet'
        ];
        $metaData = [
            'count' => 7
        ];
        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => $resultData,
            'getMeta' => $metaData,
            'getHttpCode' => 200
        ]);

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.8,application/json;q=0.6,*/*;q=0.5'
        ]);
        $request->attributes->set('_template', 'controllers/explicit/view.html.twig');

        $event = Mockery::mock('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent', [
            'getControllerResult' => $result,
            'getRequest' => $request
        ]);
        $event->shouldReceive('setResponse')
            ->with(Mockery::on(function ($response) {
                if (!$response instanceof \Symfony\Component\HttpFoundation\Response) {
                    return false;
                }

                return $response->getContent() === 'rendered_twig_template';
            }))
            ->once();

        $twig = Mockery::mock('Twig_Environment');
        $twig->shouldReceive('render')
            ->with('controllers/explicit/view.html.twig', ['data' => $resultData, 'meta' => $metaData])
            ->andReturn('rendered_twig_template');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     *
     * @dataProvider provideControllerNames
     */
    public function testRenderingTemplateFromControllerName($controllerName, $templateName)
    {
        $resultData = [
            'lorem' => 'ipsum',
            'dolor' => 'sitamet'
        ];
        $metaData = [
            'count' => 7
        ];
        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => $resultData,
            'getMeta' => $metaData,
            'getHttpCode' => 200
        ]);

        $request = Request::create('/');
        $request->attributes->set('_controller', $controllerName);

        $event = Mockery::mock('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent', [
            'getControllerResult' => $result,
            'getRequest' => $request
        ]);
        $event->shouldReceive('setResponse')
            ->with(Mockery::on(function ($response) {
                if (!$response instanceof \Symfony\Component\HttpFoundation\Response) {
                    return false;
                }

                return $response->getContent() === 'rendered_twig_template';
            }))
            ->once();

        $twig = Mockery::mock('Twig_Environment');
        $twig->shouldReceive('render')
            ->with($templateName, ['data' => $resultData, 'meta' => $metaData])
            ->andReturn('rendered_twig_template');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    public function provideControllerNames()
    {
        return [
            ['controller.index:index', 'index/index.html.twig'],
            ['controller.lorem.ipsum:dolor', 'lorem/ipsum/dolor.html.twig'],
            ['dashboard:action', 'dashboard/action.html.twig'],
            ['dashboard.projects.my:list', 'dashboard/projects/my/list.html.twig']
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     */
    public function testRenderingTemplateWithHttpCode()
    {
        $resultData = [
            'error' => 'Password too simple.'
        ];
        $metaData = [
            'count' => 7
        ];
        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => $resultData,
            'getMeta' => $metaData,
            'getHttpCode' => 400
        ]);

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.8,application/json;q=0.6,*/*;q=0.5'
        ]);
        $request->attributes->set('_template', 'controllers/explicit/view.html.twig');

        $event = Mockery::mock('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent', [
            'getControllerResult' => $result,
            'getRequest' => $request
        ]);
        $event->shouldReceive('setResponse')
            ->with(Mockery::on(function ($response) {
                if (!$response instanceof \Symfony\Component\HttpFoundation\Response) {
                    return false;
                }

                $this->assertEquals('rendered_twig_template', $response->getContent());
                $this->assertEquals(400, $response->getStatusCode());
                return true;
            }))
            ->once();

        $twig = Mockery::mock('Twig_Environment');
        $twig->shouldReceive('render')
            ->with('controllers/explicit/view.html.twig', ['data' => $resultData, 'meta' => $metaData])
            ->andReturn('rendered_twig_template');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    /**
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     */
    public function testConvertingNullDataToJSONEmptyResponseObjects()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html;q=0.6,application/json;q=0.8,*/*;q=0.5'
        ]);

        $responseContent = [
            'data' => new \StdClass,
            'meta' => new \StdClass,
        ];
        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => null,
            'getMeta' => [],
            'getHttpCode' => 200
        ]);

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getControllerResult')
            ->will($this->returnValue($result));

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) use ($responseContent) {
                $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
                $this->assertSame(json_encode($responseContent), $response->getContent());
                return true;
            }));

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $twig = Mockery::mock('Twig_Environment');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }

    /**
     * @expectedException \RuntimeException
     *
     * @covers ::__construct
     * @covers ::onControllerResult
     * @covers ::convertControllerResult
     */
    public function testNotDeterminingTemplateName()
    {
        $resultData = [
            'lorem' => 'ipsum',
            'dolor' => 'sitamet'
        ];
        $result = Mockery::mock('Tornado\Controller\Result', [
            'getData' => $resultData,
            'getMeta' => new \StdClass(),
            'getHttpCode' => 200
        ]);

        $request = Request::create('/');

        $event = Mockery::mock('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent', [
            'getControllerResult' => $result,
            'getRequest' => $request
        ]);

        $twig = Mockery::mock('Twig_Environment');

        // do the test
        $converter = new ResultConverter($twig);
        $converter->onControllerResult($event);
    }
}
