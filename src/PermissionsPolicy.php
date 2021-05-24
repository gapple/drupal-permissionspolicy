<?php

namespace Drupal\permissionspolicy;

/**
 * A PermissionsPolicy Header.
 */
class PermissionsPolicy {

  const POLICY_ANY = "*";
  const POLICY_NONE = "'none'";
  const POLICY_SELF = "'self'";

  // https://www.w3.org/TR/permissions-policy-1/#allowlists
  const DIRECTIVE_SCHEMA_ALLOW_LIST = 'serialized-allow-list';

  /**
   * The schema type for each directive.
   *
   * @var array
   */
  const DIRECTIVES = [
    // permissions Directives.
    // @see https://w3c.github.io/webappsec-permissions-policy/#policy-directive
    // @see https://github.com/w3c/webappsec-permissions-policy/blob/master/permissionss.md
    'accelerometer' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'ambient-light-sensor' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'autoplay' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'battery' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'camera' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'display-capture' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'document-domain' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'encrypted-media' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'fullscreen' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'execution-while-not-rendered' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'execution-while-out-of-viewport' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'geolocation' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'gyroscope' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'magnetometer' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'microphone' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'midi' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'navigation-override' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'payment' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'picture-in-picture' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'publickey-credentials' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'sync-xhr' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'usb' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    // 'vr' is deprecated in favour of 'xr-spatial-tracking'.
    'vr' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'wake-lock' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
    'xr-spatial-tracking' => PermissionsPolicy::DIRECTIVE_SCHEMA_ALLOW_LIST,
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

    if (empty($value)) {
      return;
    }

    if (gettype($value) === 'string') {
      $value = explode(' ', $value);
    }
    elseif (gettype($value) !== 'array') {
      throw new \InvalidArgumentException("Invalid directive value provided");
    }

    if (!isset($this->directives[$name])) {
      $this->directives[$name] = [];
    }

    $this->directives[$name] = array_merge($this->directives[$name], $value);
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
    $output = [];

    foreach ($this->directives as $name => $value) {
      if (empty($value)) {
        continue;
      }
      $output[] = $name . ' ' . implode(' ', self::reduceSourceList($value));
    }

    return implode('; ', $output);
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
      return [static::POLICY_NONE];
    }

    // Global wildcard covers all network scheme sources.
    if (in_array(static::POLICY_ANY, $sources)) {
      $sources = array_filter($sources, function ($source) {
        // Keep any values that are a quoted string, or non-network scheme.
        // e.g. '* https: data: example.com' -> '* data:'
        // https://www.w3.org/TR/CSP/#match-url-to-source-expression
        return strpos($source, "'") === 0 || preg_match('<^(?!(?:https?):)([a-z]+:)>', $source);
      });

      array_unshift($sources, static::POLICY_ANY);
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
