<?php

namespace Drupal\Tests\permissionspolicy\Unit\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\permissionspolicy\EventSubscriber\ResponseSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\permissionspolicy\EventSubscriber\ResponseSubscriber
 * @group permissionspolicy
 */
class ResponseSubscriberTest extends UnitTestCase {

  /**
   * Mock HTTP Response.
   *
   * @var \Drupal\Core\Render\HtmlResponse|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $response;

  /**
   * Mock Response Event.
   *
   * @var \Symfony\Component\HttpKernel\Event\ResponseEvent|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $event;

  /**
   * The Event Dispatcher Service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->response = $this->createMock(HtmlResponse::class);
    $this->response->headers = $this->createMock(ResponseHeaderBag::class);
    $responseCacheableMetadata = $this->createMock(CacheableMetadata::class);
    $this->response->method('getCacheableMetadata')
      ->willReturn($responseCacheableMetadata);

    $this->event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $this->createMock(Request::class),
      HttpKernelInterface::MASTER_REQUEST,
      $this->response
    );

    $this->eventDispatcher = $this->createMock(EventDispatcher::class);
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
   * An empty or missing feature list should not output a header.
   *
   * @covers ::onKernelResponse
   */
  public function testEmptyPolicy() {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
        ],
      ],
    ]);

    $subscriber = new ResponseSubscriber($configFactory, $this->eventDispatcher);

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

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => FALSE,
          'features' => [
            'geolocation' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $subscriber = new ResponseSubscriber($configFactory, $this->eventDispatcher);

    $this->response->headers->expects($this->never())
      ->method('set');
    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheTags')
      ->with(['config:permissionspolicy.settings']);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Test a single feature.
   *
   * @covers ::onKernelResponse
   */
  public function testSingleDirective() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'features' => [
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
        $this->equalTo('geolocation=self')
      );

    $subscriber = new ResponseSubscriber($configFactory, $this->eventDispatcher);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Test a multiple features.
   *
   * @covers ::onKernelResponse
   */
  public function testMultipleDirectives() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'features' => [
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
        $this->equalTo('camera=self, geolocation=self')
      );

    $subscriber = new ResponseSubscriber($configFactory, $this->eventDispatcher);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * A previously set header should be removed if policy is enabled but empty.
   *
   * @covers ::onKernelResponse
   */
  public function testOverrideWhenEmpty() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'features' => [],
        ],
      ],
    ]);

    // Default value provided by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond().
    $this->response->headers->method('has')
      ->with('Permissions-Policy')
      ->willReturn(TRUE);

    $this->response->headers->expects($this->once())
      ->method('remove')
      ->with(
        $this->equalTo('Permissions-Policy')
      );
    $this->response->headers->expects($this->never())
      ->method('set');

    $subscriber = new ResponseSubscriber($configFactory, $this->eventDispatcher);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * A previously set header should not be removed if policy is disabled.
   *
   * @covers ::onKernelResponse
   */
  public function testNoOverrideWhenDisabled() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'permissionspolicy.settings' => [
        'enforce' => [
          'enable' => FALSE,
          'features' => [],
        ],
      ],
    ]);

    // Default value provided by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond().
    $this->response->headers->method('has')
      ->with('Permissions-Policy')
      ->willReturn(TRUE);

    $this->response->headers->expects($this->never())
      ->method('remove')
      ->with(
        $this->equalTo('Permissions-Policy')
      );
    $this->response->headers->expects($this->never())
      ->method('set');

    $subscriber = new ResponseSubscriber($configFactory, $this->eventDispatcher);

    $subscriber->onKernelResponse($this->event);
  }

}
