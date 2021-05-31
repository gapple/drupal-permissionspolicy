<?php

namespace Drupal\permissionspolicy;

use gapple\StructuredFields\Serializer;
use gapple\StructuredFields\Token;

/**
 * A PermissionsPolicy Header.
 */
class PermissionsPolicy {

  const POLICY_ANY = '*';
  const POLICY_NONE = 'none';
  const POLICY_SELF = 'self';

  // https://www.w3.org/TR/permissions-policy-1/#allowlists
  const DIRECTIVE_SCHEMA_ALLOWLIST = 'allowlist';

  /**
   * The schema type for each directive.
   *
   * @see https://github.com/w3c/webappsec-permissions-policy/blob/main/features.md
   *
   * @var array
   */
  const DIRECTIVES = [
    // Standardized Features
    'accelerometer'                   => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'ambient-light-sensor'            => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'autoplay'                        => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'battery'                         => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'camera'                          => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'cross-origin-isolated'           => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'display-capture'                 => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'document-domain'                 => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'encrypted-media'                 => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'execution-while-not-rendered'    => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'execution-while-out-of-viewport' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'fullscreen'                      => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'geolocation'                     => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'gyroscope'                       => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'magnetometer'                    => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'microphone'                      => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'midi'                            => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'navigation-override'             => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'payment'                         => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'picture-in-picture'              => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'publickey-credentials-get'       => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'screen-wake-lock'                => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'sync-xhr'                        => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'usb'                             => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'web-share'                       => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'xr-spatial-tracking'             => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,

    // Proposed Features
    'clipboard-read'                  => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'clipboard-write'                 => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'gamepad'                         => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'speaker-selection'               => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,

    // Experimental Features
    'conversion-measurement'          => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'focus-without-user-activation'   => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'hid'                             => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'idle-detection'                  => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'interest-cohort'                 => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'serial'                          => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'sync-script'                     => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'trust-token-redemption'          => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
    'vertical-scroll'                 => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOWLIST,
  ];

  /**
   * The policy directives.
   *
   * @var array
   */
  protected $directives = [];

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

  /**
   * Check if the policy currently has the specified directive.
   *
   * @param string $name
   *   The directive name.
   *
   * @return bool
   *   If the policy has the specified directive.
   */
  public function hasDirective($name) {
    return isset($this->directives[$name]);
  }

  /**
   * Get the value of a directive.
   *
   * @param string $name
   *   The directive name.
   *
   * @return array
   *   The directive's values.
   */
  public function getDirective($name) {
    self::validateDirectiveName($name);

    return $this->directives[$name];
  }

  /**
   * Add a new directive to the policy, or replace an existing directive.
   *
   * @param string $name
   *   The directive name.
   * @param array|bool|string $value
   *   The directive value.
   */
  public function setDirective($name, $value) {
    self::validateDirectiveName($name);

    $this->directives[$name] = [];
    if (empty($value)) {
      return;
    }
    $this->appendDirective($name, $value);
  }

  /**
   * Append values to an existing directive.
   *
   * @param string $name
   *   The directive name.
   * @param array|string $value
   *   The directive value.
   */
  public function appendDirective($name, $value) {
    self::validateDirectiveName($name);

    if (gettype($value) === 'string') {
      $value = explode(' ', $value);
    }
    elseif (gettype($value) !== 'array') {
      throw new \InvalidArgumentException("Invalid directive value provided");
    }

    if (!isset($this->directives[$name])) {
      $this->directives[$name] = [];
    }

    $this->directives[$name] = array_merge(
      $this->directives[$name],
      array_filter($value)
    );
  }

  /**
   * Remove a directive from the policy.
   *
   * @param string $name
   *   The directive name.
   */
  public function removeDirective($name) {
    self::validateDirectiveName($name);

    unset($this->directives[$name]);
  }

  /**
   * Get the header name.
   *
   * @return string
   *   The header name.
   */
  public function getHeaderName() {
    return 'Permissions-Policy';
  }

  /**
   * Get the header value.
   *
   * @return string
   *   The header value.
   */
  public function getHeaderValue() {
    $output = new \stdClass();

    ksort($this->directives);

    foreach ($this->directives as $name => $value) {
      // Convert to Structured Fields inner list.
      $allowlist = array_map(function ($item) {
        if (in_array($item, [self::POLICY_ANY, self::POLICY_SELF])) {
          $item = new Token($item);
        }
        return [$item, new \stdClass()];
      }, self::reduceSourceList($value));

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
   * Reduce a list of sources to a minimal set.
   *
   * @param array $sources
   *   The array of sources.
   *
   * @return array
   *   The reduced set of sources.
   */
  private static function reduceSourceList(array $sources) {
    $sources = array_unique($sources);

    // 'none' overrides any other sources.
    if (in_array(static::POLICY_NONE, $sources)) {
      return [];
    }

    // Global wildcard covers all network scheme sources.
    if (in_array(static::POLICY_ANY, $sources)) {
      return [static::POLICY_ANY];
    }

    // Remove protocol-prefixed hosts if protocol is allowed.
    // e.g. 'http: data: example.com https://example.com' -> 'http: data: example.com'
    $protocols = array_filter($sources, function ($source) {
      return preg_match('<^(https?):$>', $source);
    });
    if (!empty($protocols)) {
      if (in_array('http:', $protocols)) {
        $protocols[] = 'https:';
      }
      $sources = array_filter($sources, function ($source) use ($protocols) {
        return !preg_match('<^(' . implode('|', $protocols) . ')//>', $source);
      });
    }

    return $sources;
  }

  /**
   * Create the string header representation.
   *
   * @return string
   *   The full header string.
   */
  public function __toString() {
    return $this->getHeaderName() . ': ' . $this->getHeaderValue();
  }

}
