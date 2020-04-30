<?php

namespace Drupal\featurepolicy;

/**
 * A FeaturePolicy Header.
 */
class FeaturePolicy {

  // https://w3c.github.io/webappsec-feature-policy/#ascii-serialization
  const DIRECTIVE_SCHEMA_ALLOW_LIST = 'serialized-allow-list';

  /**
   * The schema type for each directive.
   *
   * @var array
   */
  const DIRECTIVES = [
    // Feature Directives.
    // @see https://w3c.github.io/webappsec-feature-policy/#policy-directive
    // @see https://github.com/w3c/webappsec-feature-policy/blob/master/features.md
    'accelerometer' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'ambient-light-sensor' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'autoplay' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'battery' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'camera' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'display-capture' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'document-domain' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'encrypted-media' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'fullscreen' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'execution-while-not-rendered' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'execution-while-out-of-viewport' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'geolocation' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'gyroscope' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'magnetometer' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'microphone' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'midi' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'navigation-override' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'payment' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'picture-in-picture' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'publickey-credentials' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'sync-xhr' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'usb' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    // 'vr' is deprecated in favour of 'xr-spatial-tracking'.
    'vr' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'wake-lock' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'xr-spatial-tracking' => FeaturePolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
  ];

  /**
   * Check if a directive name is valid.
   *
   * @param string $name
   *   The directive name.
   *
   * @return bool
   *   True if the directive name is valid.
   */
  public static function isValidDirectiveName($name) {
    return array_key_exists($name, static::DIRECTIVES);
  }

  /**
   * Check if a directive name is valid, throwing an exception if not.
   *
   * @param string $name
   *   The directive name.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateDirectiveName($name) {
    if (!static::isValidDirectiveName($name)) {
      throw new \InvalidArgumentException("Invalid directive name provided");
    }
  }

  /**
   * Get the valid directive names.
   *
   * @return array
   *   An array of directive names.
   */
  public static function getDirectiveNames() {
    return array_keys(self::DIRECTIVES);
  }

  /**
   * Get the schema constant for a directive.
   *
   * @param string $name
   *   The directive name.
   *
   * @return string
   *   A DIRECTIVE_SCHEMA_* constant value
   */
  public static function getDirectiveSchema($name) {
    self::validateDirectiveName($name);

    return self::DIRECTIVES[$name];
  }

}
