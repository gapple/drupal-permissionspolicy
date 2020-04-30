<?php

namespace Drupal\featurepolicy\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\LibraryPolicyBuilder;
use Drupal\csp\ReportingHandlerPluginManager;
use Drupal\featurepolicy\FeaturePolicy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
   * Add Feature-Policy header to response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $fpConfig = $this->configFactory->get('featurepolicy.settings');

    $response = $event->getResponse();

    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheTags(['config:featurepolicy.settings']);
    }

    foreach (['enforce'] as $policyType) {
      if (!$fpConfig->get($policyType . '.enable')) {
        continue;
      }

      $policy = new FeaturePolicy();

      foreach (($fpConfig->get($policyType . '.directives') ?: []) as $directiveName => $directiveOptions) {
        switch ($directiveOptions['base']) {
          case 'self':
            $policy->setDirective($directiveName, [FeaturePolicy::POLICY_SELF]);
            break;

          case 'none':
            $policy->setDirective($directiveName, [FeaturePolicy::POLICY_NONE]);
            break;

          case 'any':
            $policy->setDirective($directiveName, [FeaturePolicy::POLICY_ANY]);
            break;

          default:
            // Initialize to an empty value so that any alter subscribers can
            // tell that this directive was enabled.
            $policy->setDirective($directiveName, []);
        }

        if (!empty($directiveOptions['sources'])) {
          $policy->appendDirective($directiveName, $directiveOptions['sources']);
        }
      }

      if (($headerValue = $policy->getHeaderValue())) {
        $response->headers->set($policy->getHeaderName(), $headerValue);
      }
    }
  }

}
