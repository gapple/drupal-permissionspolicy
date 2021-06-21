<?php

namespace Drupal\permissionspolicy\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\permissionspolicy\PermissionsPolicy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Feature Policy Response event subscriber.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ResponseSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE] = ['onKernelResponse'];
    return $events;
  }

  /**
   * Add Permissions-Policy header to response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $fpConfig = $this->configFactory->get('permissionspolicy.settings');

    $response = $event->getResponse();

    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheTags(['config:permissionspolicy.settings']);
    }

    foreach (['enforce'] as $policyType) {
      if (!$fpConfig->get($policyType . '.enable')) {
        continue;
      }

      $policy = new PermissionsPolicy();

      foreach (($fpConfig->get($policyType . '.features') ?: []) as $featureName => $featureOptions) {
        switch ($featureOptions['base']) {
          case 'self':
            $policy->setFeature($featureName, [PermissionsPolicy::ORIGIN_SELF]);
            break;

          case 'none':
            $policy->setFeature($featureName, [PermissionsPolicy::ORIGIN_NONE]);
            break;

          case 'any':
            $policy->setFeature($featureName, [PermissionsPolicy::ORIGIN_ANY]);
            break;

          default:
            // Initialize to an empty value so that any alter subscribers can
            // tell that this feature was enabled.
            $policy->setFeature($featureName, []);
        }

        if (!empty($featureOptions['sources'])) {
          $policy->appendFeature($featureName, $featureOptions['sources']);
        }
      }

      if (($headerValue = $policy->getHeaderValue())) {
        $response->headers->set($policy->getHeaderName(), $headerValue);
      }
      elseif ($response->headers->has($policy->getHeaderName())) {
        $response->headers->remove($policy->getHeaderName());
      }
    }
  }

}
