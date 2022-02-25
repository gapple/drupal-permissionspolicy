<?php

namespace Drupal\permissionspolicy;

use gapple\StructuredFields\Serializer;
use gapple\StructuredFields\Token;

/**
 * A PermissionsPolicy Header.
 */
class PermissionsPolicy {

  public const ORIGIN_ANY = '*';
  public const ORIGIN_NONE = 'none';
  public const ORIGIN_SELF = 'self';

  // https://www.w3.org/TR/permissions-policy-1/#allowlists
  public const FEATURE_SCHEMA_ALLOWLIST = 'allowlist';

  /**
   * The schema type for each directive.
   *
   * @see https://github.com/w3c/webappsec-permissions-policy/blob/main/features.md
   *
   * @var array
   */
  public const FEATURES = [
    // Standardized Features.
    'accelerometer'                   => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'ambient-light-sensor'            => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'autoplay'                        => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'battery'                         => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'camera'                          => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'cross-origin-isolated'           => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'display-capture'                 => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'document-domain'                 => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'encrypted-media'                 => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'execution-while-not-rendered'    => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'execution-while-out-of-viewport' => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'fullscreen'                      => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'geolocation'                     => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'gyroscope'                       => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'magnetometer'                    => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'microphone'                      => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'midi'                            => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'navigation-override'             => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'payment'                         => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'picture-in-picture'              => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'publickey-credentials-get'       => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'screen-wake-lock'                => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'sync-xhr'                        => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'usb'                             => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'web-share'                       => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'xr-spatial-tracking'             => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,

    // Proposed Features.
    'clipboard-read'                  => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'clipboard-write'                 => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'gamepad'                         => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'speaker-selection'               => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,

    // Experimental Features.
    'conversion-measurement'          => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'focus-without-user-activation'   => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'hid'                             => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'idle-detection'                  => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'interest-cohort'                 => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'serial'                          => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'sync-script'                     => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'trust-token-redemption'          => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'vertical-scroll'                 => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
    'window-placement'                => PermissionsPolicy::FEATURE_SCHEMA_ALLOWLIST,
  ];

  /**
   * A map of default allowlist values for each directive.
   *
   * @var array
   */
  public const FEATURE_DEFAULT_ALLOWLIST = [
    // Standardized Features.
    'accelerometer'                   => PermissionsPolicy::ORIGIN_SELF,
    'ambient-light-sensor'            => PermissionsPolicy::ORIGIN_SELF,
    'autoplay'                        => PermissionsPolicy::ORIGIN_SELF,
    'battery'                         => PermissionsPolicy::ORIGIN_SELF,
    'camera'                          => PermissionsPolicy::ORIGIN_SELF,
    'cross-origin-isolated'           => PermissionsPolicy::ORIGIN_SELF,
    'display-capture'                 => PermissionsPolicy::ORIGIN_SELF,
    'document-domain'                 => PermissionsPolicy::ORIGIN_ANY,
    'encrypted-media'                 => PermissionsPolicy::ORIGIN_SELF,
    'execution-while-not-rendered'    => PermissionsPolicy::ORIGIN_ANY,
    'execution-while-out-of-viewport' => PermissionsPolicy::ORIGIN_ANY,
    'fullscreen'                      => PermissionsPolicy::ORIGIN_SELF,
    'geolocation'                     => PermissionsPolicy::ORIGIN_SELF,
    'gyroscope'                       => PermissionsPolicy::ORIGIN_SELF,
    'magnetometer'                    => PermissionsPolicy::ORIGIN_SELF,
    'microphone'                      => PermissionsPolicy::ORIGIN_SELF,
    'midi'                            => PermissionsPolicy::ORIGIN_SELF,
    'navigation-override'             => PermissionsPolicy::ORIGIN_SELF,
    'payment'                         => PermissionsPolicy::ORIGIN_SELF,
    'picture-in-picture'              => PermissionsPolicy::ORIGIN_ANY,
    'publickey-credentials-get'       => PermissionsPolicy::ORIGIN_SELF,
    'screen-wake-lock'                => PermissionsPolicy::ORIGIN_SELF,
    // 'sync-xhr'                        => PermissionsPolicy::ORIGIN_SELF,
    'usb'                             => PermissionsPolicy::ORIGIN_SELF,
    'web-share'                       => PermissionsPolicy::ORIGIN_SELF,
    'xr-spatial-tracking'             => PermissionsPolicy::ORIGIN_SELF,

    // Proposed Features.
    'clipboard-read'                  => PermissionsPolicy::ORIGIN_SELF,
    'clipboard-write'                 => PermissionsPolicy::ORIGIN_SELF,
    'gamepad'                         => PermissionsPolicy::ORIGIN_SELF,
    'speaker-selection'               => PermissionsPolicy::ORIGIN_SELF,

    // Experimental Features.
    // 'conversion-measurement'          => PermissionsPolicy::ORIGIN_SELF,
    // 'focus-without-user-activation'   => PermissionsPolicy::ORIGIN_SELF,
    'hid'                             => PermissionsPolicy::ORIGIN_SELF,
    'idle-detection'                  => PermissionsPolicy::ORIGIN_SELF,
    // 'interest-cohort'                 => PermissionsPolicy::ORIGIN_SELF,
    // 'serial'                          => PermissionsPolicy::ORIGIN_SELF,
    // 'sync-script'                     => PermissionsPolicy::ORIGIN_SELF,
    // 'trust-token-redemption'          => PermissionsPolicy::ORIGIN_SELF,
    'vertical-scroll'                 => PermissionsPolicy::ORIGIN_ANY,
    'window-placement'                => PermissionsPolicy::ORIGIN_SELF,
  ];

  /**
   * The policy features.
   *
   * @var array
   */
  protected $features = [];

  /**
   * Check if a feature name is valid.
   *
   * @param string $name
   *   The feature name.
   *
   * @return bool
   *   True if the feature name is valid.
   */
  public static function isValidFeatureName(string $name): bool {
    return array_key_exists($name, static::FEATURES);
  }

  /**
   * Check if a feature name is valid, throwing an exception if not.
   *
   * @param string $name
   *   The feature name.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateFeatureName(string $name): void {
    if (!static::isValidFeatureName($name)) {
      throw new \InvalidArgumentException("Invalid directive name provided");
    }
  }

  /**
   * Get the valid feature names.
   *
   * @return string[]
   *   An array of feature names.
   */
  public static function getFeatureNames(): array {
    return array_keys(self::FEATURES);
  }

  /**
   * Get the schema constant for a feature.
   *
   * @param string $name
   *   The feature name.
   *
   * @return string
   *   A FEATURE_SCHEMA_* constant value
   */
  public static function getFeatureSchema(string $name): string {
    self::validateFeatureName($name);

    return self::FEATURES[$name];
  }

  /**
   * Check if the policy currently has the specified feature.
   *
   * @param string $name
   *   The feature name.
   *
   * @return bool
   *   If the policy has the specified feature.
   */
  public function hasFeature(string $name): bool {
    return isset($this->features[$name]);
  }

  /**
   * Get the value of a feature.
   *
   * @param string $name
   *   The feature name.
   *
   * @return string[]
   *   The feature's values.
   */
  public function getFeature(string $name): array {
    self::validateFeatureName($name);

    return $this->features[$name];
  }

  /**
   * Add a new feature to the policy, or replace an existing feature value.
   *
   * @param string $name
   *   The feature name.
   * @param array|string $value
   *   The feature value.
   */
  public function setFeature(string $name, $value): void {
    self::validateFeatureName($name);

    $this->features[$name] = [];
    if (empty($value)) {
      return;
    }
    $this->appendFeature($name, $value);
  }

  /**
   * Append values to an existing feature.
   *
   * @param string $name
   *   The feature name.
   * @param array|string $value
   *   The feature value.
   */
  public function appendFeature(string $name, $value): void {
    self::validateFeatureName($name);

    if (gettype($value) === 'string') {
      $value = explode(' ', $value);
    }
    elseif (gettype($value) !== 'array') {
      throw new \InvalidArgumentException("Invalid feature value provided");
    }

    if (!isset($this->features[$name])) {
      $this->features[$name] = [];
    }

    $this->features[$name] = array_merge(
      $this->features[$name],
      array_filter($value)
    );
  }

  /**
   * Remove a feature from the policy.
   *
   * @param string $name
   *   The feature name.
   */
  public function removeFeature(string $name): void {
    self::validateFeatureName($name);

    unset($this->features[$name]);
  }

  /**
   * Get the header name.
   *
   * @return string
   *   The header name.
   */
  public function getHeaderName(): string {
    return 'Permissions-Policy';
  }

  /**
   * Get the header value.
   *
   * @return string
   *   The header value.
   */
  public function getHeaderValue(): string {
    $output = new \stdClass();

    ksort($this->features);

    foreach ($this->features as $name => $value) {
      // Convert to Structured Fields inner list.
      $allowlist = array_map(function ($item) {
        if (in_array($item, [self::ORIGIN_ANY, self::ORIGIN_SELF])) {
          $item = new Token($item);
        }
        return [$item, new \stdClass()];
      }, self::reduceAllowlist($value));

      if (count($allowlist) == 1) {
        $output->{$name} = reset($allowlist);
      }
      else {
        $output->{$name} = [$allowlist, new \stdClass()];
      }
    }

    return Serializer::serializeDictionary($output);
  }

  /**
   * Reduce a list of origins to a minimal set.
   *
   * @param array $origins
   *   The array of origins.
   *
   * @return string[]
   *   The reduced set of origins.
   */
  private static function reduceAllowlist(array $origins): array {
    $origins = array_unique($origins);

    // 'none' overrides any other sources.
    if (in_array(static::ORIGIN_NONE, $origins)) {
      return [];
    }

    // Global wildcard covers all network scheme sources.
    if (in_array(static::ORIGIN_ANY, $origins)) {
      return [static::ORIGIN_ANY];
    }

    // Remove protocol-prefixed hosts if protocol is allowed.
    // e.g. 'http: example.com https://example.com' -> 'http: example.com'.
    $protocols = array_filter($origins, function ($source) {
      return preg_match('<^(https?):$>', $source);
    });
    if (!empty($protocols)) {
      if (in_array('http:', $protocols)) {
        $protocols[] = 'https:';
      }
      $origins = array_filter($origins, function ($source) use ($protocols) {
        return !preg_match('<^(' . implode('|', $protocols) . ')//>', $source);
      });
    }

    return $origins;
  }

  /**
   * Create the string header representation.
   *
   * @return string
   *   The full header string.
   */
  public function __toString(): string {
    return $this->getHeaderName() . ': ' . $this->getHeaderValue();
  }

}
