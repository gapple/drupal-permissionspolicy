<?php

namespace Drupal\Tests\permissionspolicy\Unit\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\permissionspolicy\EventSubscriber\ResponseSubscriber;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\permissionspolicy\EventSubscriber\ResponseSubscriber
 * @group permissionspolicy
 */
class ResponseSubscriberTest extends UnitTestCase {

  /**
   * Mock HTTP Response.
   *
   * @var \Drupal\Core\Render\HtmlResponse|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $response;

  /**
   * Mock Response Event.
   *
   * @var \Symfony\Component\HttpKernel\Event\FilterResponseEvent|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $event;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->response = $this->getMockBuilder(HtmlResponse::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->response->headers = $this->getMockBuilder(ResponseHeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $responseCacheableMetadata = $this->getMockBuilder(CacheableMetadata::class)
      ->getMock();
    $this->response->method('getCacheableMetadata')
      ->willReturn($responseCacheableMetadata);

    /** @var \Symfony\Component\HttpKernel\Event\FilterResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
    $this->event = $this->getMockBuilder(FilterResponseEvent::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->event->expects($this->any())
      ->method('isMasterRequest')
      ->willReturn(TRUE);
    $this->event->expects($this->any())
      ->method('getResponse')
      ->willReturn($this->response);
  }

  /**
   * Check that the subscriber listens to the Response event.
   *
   * @covers ::getSubscribedEvents
   */
  public function testSubscribedEvents() {
    $this->assertArrayHasKey(KernelEvents::RESPONSE, ResponseSubscriber::getSubscribedEvents());
  }

  /**
   * An empty or missing directive list should not output a header.
   *
   * @covers ::onKernelResponse
   */
  public function testEmptyPolicy() {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
        ],
      ],
    ]);

    $subscriber = new ResponseSubscriber($configFactory);

    $this->response->headers->expects($this->never())
      ->method('set');
    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheTags')
      ->with(['config:permissionspolicy.settings']);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Header shouldn't be applied if policy is disabled.
   *
   * @covers ::onKernelResponse
   */
  public function testDisabledPolicy() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => FALSE,
          'directives' => [
            'geolocation' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $subscriber = new ResponseSubscriber($configFactory);

    $this->response->headers->expects($this->never())
      ->method('set');
    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheTags')
      ->with(['config:permissionspolicy.settings']);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Test a single directive.
   *
   * @covers ::onKernelResponse
   */
  public function testSingleDirective() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'geolocation' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Permissions-Policy'),
        $this->equalTo("geolocation 'self'")
      );

    $subscriber = new ResponseSubscriber($configFactory);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Test a multiple directives.
   *
   * @covers ::onKernelResponse
   */
  public function testMultipleDirectives() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'geolocation' => [
              'base' => 'self',
            ],
            'camera' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Permissions-Policy'),
        $this->equalTo("geolocation 'self'; camera 'self'")
      );

    $subscriber = new ResponseSubscriber($configFactory);

    $subscriber->onKernelResponse($this->event);
  }

}
