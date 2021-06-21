<?php

namespace Drupal\Tests\permissionspolicy\Unit;

use Drupal\permissionspolicy\PermissionsPolicy;
use Drupal\Tests\UnitTestCase;

/**
 * Test PermissionsPolicy behaviour.
 *
 * @coversDefaultClass \Drupal\permissionspolicy\PermissionsPolicy
 * @group permissionspolicy
 */
class PermissionsPolicyTest extends UnitTestCase {

  /**
   * Test that invalid directive names cause an exception.
   *
   * @covers ::setFeature
   * @covers ::isValidFeatureName
   * @covers ::validateDirectiveName
   */
  public function testSetInvalidPolicy() {
    $policy = new PermissionsPolicy();

    $this->expectException(\InvalidArgumentException::class);
    $policy->setFeature('foo', PermissionsPolicy::ORIGIN_SELF);
  }

  /**
   * Test that invalid directive names cause an exception.
   *
   * @covers ::appendFeature
   * @covers ::isValidFeatureName
   * @covers ::validateDirectiveName
   */
  public function testAppendInvalidPolicy() {
    $policy = new PermissionsPolicy();

    $this->expectException(\InvalidArgumentException::class);
    $policy->appendFeature('foo', PermissionsPolicy::ORIGIN_SELF);
  }

  /**
   * Test setting a single value to a directive.
   *
   * @covers ::setFeature
   * @covers ::hasFeature
   * @covers ::getFeature
   * @covers ::isValidFeatureName
   * @covers ::validateDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetSingle() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);

    $this->assertTrue($policy->hasFeature('geolocation'));
    $this->assertEquals(
      $policy->getFeature('geolocation'),
      ['self']
    );
    $this->assertEquals(
      "geolocation=self",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test appending a single value to an uninitialized directive.
   *
   * @covers ::appendFeature
   * @covers ::hasFeature
   * @covers ::getFeature
   * @covers ::isValidFeatureName
   * @covers ::validateDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendSingle() {
    $policy = new PermissionsPolicy();

    $policy->appendFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);

    $this->assertTrue($policy->hasFeature('geolocation'));
    $this->assertEquals(
      $policy->getFeature('geolocation'),
      ['self']
    );
    $this->assertEquals(
      "geolocation=self",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that a directive is overridden when set with a new value.
   *
   * @covers ::setFeature
   * @covers ::isValidFeatureName
   * @covers ::getHeaderValue
   */
  public function testSetMultiple() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', PermissionsPolicy::ORIGIN_ANY);
    $policy->setFeature('geolocation', [PermissionsPolicy::ORIGIN_SELF, 'one.example.com']);

    $this->assertEquals(
      'geolocation=(self "one.example.com")',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that appending to a directive extends the existing value.
   *
   * @covers ::appendFeature
   * @covers ::isValidFeatureName
   * @covers ::getHeaderValue
   */
  public function testAppendMultiple() {
    $policy = new PermissionsPolicy();

    $policy->appendFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);
    $policy->appendFeature('camera', [PermissionsPolicy::ORIGIN_SELF, 'two.example.com']);
    $policy->appendFeature('geolocation', 'one.example.com');

    $this->assertEquals(
      'camera=(self "two.example.com"), geolocation=(self "one.example.com")',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that setting an empty value removes a directive.
   *
   * @covers ::setFeature
   * @covers ::isValidFeatureName
   * @covers ::getHeaderValue
   */
  public function testSetEmpty() {
    $policy = new PermissionsPolicy();
    $policy->setFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);
    $policy->setFeature('camera', [PermissionsPolicy::ORIGIN_SELF]);
    $policy->setFeature('camera', []);

    $this->assertEquals(
      "camera=(), geolocation=self",
      $policy->getHeaderValue()
    );


    $policy = new PermissionsPolicy();
    $policy->setFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);
    $policy->setFeature('camera', [PermissionsPolicy::ORIGIN_SELF]);
    $policy->setFeature('camera', '');

    $this->assertEquals(
      "camera=(), geolocation=self",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that appending an empty value doesn't change the directive.
   *
   * @covers ::appendFeature
   * @covers ::isValidFeatureName
   * @covers ::getHeaderValue
   */
  public function testAppendEmpty() {
    $policy = new PermissionsPolicy();

    $policy->appendFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);
    $this->assertEquals(
      'geolocation=self',
      $policy->getHeaderValue()
    );

    //@todo should appending an empty value result in an empty list?
    $policy->appendFeature('geolocation', '');
    $policy->appendFeature('camera', []);
    $this->assertEquals(
      'camera=(), geolocation=self',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that source values are not repeated in the header.
   *
   * @covers ::setFeature
   * @covers ::appendFeature
   * @covers ::isValidFeatureName
   * @covers ::getHeaderValue
   */
  public function testDuplicate() {
    $policy = new PermissionsPolicy();

    // Provide identical sources in an array.
    $policy->setFeature('geolocation', [PermissionsPolicy::ORIGIN_SELF, PermissionsPolicy::ORIGIN_SELF]);
    // Provide identical sources in a string.
    $policy->setFeature('camera', 'one.example.com one.example.com');

    // Provide identical sources through both set and append.
    $policy->setFeature('microphone', ['two.example.com', 'two.example.com']);
    $policy->appendFeature('microphone', ['two.example.com', 'two.example.com']);

    $this->assertEquals(
      'camera="one.example.com", geolocation=self, microphone="two.example.com"',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that removed directives are not output in the header.
   *
   * @covers ::removeFeature
   * @covers ::isValidFeatureName
   * @covers ::getHeaderValue
   */
  public function testRemove() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', [PermissionsPolicy::ORIGIN_SELF]);
    $policy->setFeature('camera', 'example.com');

    $policy->removeFeature('camera');

    $this->assertEquals(
      'geolocation=self',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that removing an invalid directive name causes an exception.
   *
   * @covers ::removeFeature
   * @covers ::isValidFeatureName
   * @covers ::validateDirectiveName
   */
  public function testRemoveInvalid() {
    $policy = new PermissionsPolicy();

    $this->expectException(\InvalidArgumentException::class);
    $policy->removeFeature('foo');
  }

  /**
   * Test that invalid directive values cause an exception.
   *
   * @covers ::appendFeature
   */
  public function testInvalidValue() {
    $policy = new PermissionsPolicy();

    $this->expectException(\InvalidArgumentException::class);
    $policy->appendFeature('geolocation', 12);
  }

  /**
   * Test reducing the source list when 'none' is included.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListWithNone() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', [
      PermissionsPolicy::ORIGIN_NONE,
      'example.com',
    ]);
    $this->assertEquals(
      'geolocation=()',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test reducing source list when any host allowed.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListAny() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', [
      PermissionsPolicy::ORIGIN_ANY,
      'example.com',
      'https://example.com',
      'http:',
      'https:',
    ]);
    $this->assertEquals(
      "geolocation=*",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test reducing the source list when 'http:' is included.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListWithHttp() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', [
      'http:',
      // Hosts without protocol should be kept.
      // (e.g. this would allow ftp://example.com)
      'example.com',
      // HTTP hosts should be removed.
      'http://example.org',
      'https://example.net',
    ]);

    $this->assertEquals(
      'geolocation=("http:" "example.com")',
      $policy->getHeaderValue()
    );
  }

  /**
   * Test reducing the source list when 'https:' is included.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListWithHttps() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', [
      'https:',
      // Non-secure hosts should be kept.
      'example.com',
      'http://example.org',
      // Secure Hosts should be removed.
      'https://example.net',
    ]);

    $this->assertEquals(
      'geolocation=("https:" "example.com" "http://example.org")',
      $policy->getHeaderValue()
    );
  }

  /**
   * @covers ::__toString
   */
  public function testToString() {
    $policy = new PermissionsPolicy();

    $policy->setFeature('geolocation', PermissionsPolicy::ORIGIN_SELF);
    $policy->setFeature('camera', [PermissionsPolicy::ORIGIN_SELF, 'example.com']);

    $this->assertEquals(
      'Permissions-Policy: camera=(self "example.com"), geolocation=self',
      $policy->__toString()
    );
  }

}
