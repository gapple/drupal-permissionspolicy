<?php

namespace Drupal\Tests\featurepolicy\Unit;

use Drupal\featurepolicy\FeaturePolicy;
use Drupal\Tests\UnitTestCase;

/**
 * Test FeaturePolicy behaviour.
 *
 * @coversDefaultClass \Drupal\featurepolicy\FeaturePolicy
 * @group featurepolicy
 */
class FeaturePolicyTest extends UnitTestCase {

  /**
   * Test that invalid directive names cause an exception.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   * @covers ::validateDirectiveName
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidPolicy() {
    $policy = new FeaturePolicy();

    $policy->setDirective('foo', FeaturePolicy::POLICY_SELF);
  }

  /**
   * Test that invalid directive names cause an exception.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::validateDirectiveName
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAppendInvalidPolicy() {
    $policy = new FeaturePolicy();

    $policy->appendDirective('foo', FeaturePolicy::POLICY_SELF);
  }

  /**
   * Test setting a single value to a directive.
   *
   * @covers ::setDirective
   * @covers ::hasDirective
   * @covers ::getDirective
   * @covers ::isValidDirectiveName
   * @covers ::validateDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetSingle() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', FeaturePolicy::POLICY_SELF);

    $this->assertTrue($policy->hasDirective('geolocation'));
    $this->assertEquals(
      $policy->getDirective('geolocation'),
      ["'self'"]
    );
    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test appending a single value to an uninitialized directive.
   *
   * @covers ::appendDirective
   * @covers ::hasDirective
   * @covers ::getDirective
   * @covers ::isValidDirectiveName
   * @covers ::validateDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendSingle() {
    $policy = new FeaturePolicy();

    $policy->appendDirective('geolocation', FeaturePolicy::POLICY_SELF);

    $this->assertTrue($policy->hasDirective('geolocation'));
    $this->assertEquals(
      $policy->getDirective('geolocation'),
      ["'self'"]
    );
    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that a directive is overridden when set with a new value.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetMultiple() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', FeaturePolicy::POLICY_ANY);
    $policy->setDirective('geolocation', [FeaturePolicy::POLICY_SELF, 'one.example.com']);

    $this->assertEquals(
      "geolocation 'self' one.example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that appending to a directive extends the existing value.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendMultiple() {
    $policy = new FeaturePolicy();

    $policy->appendDirective('geolocation', FeaturePolicy::POLICY_SELF);
    $policy->appendDirective('camera', [FeaturePolicy::POLICY_SELF, 'two.example.com']);
    $policy->appendDirective('geolocation', 'one.example.com');

    $this->assertEquals(
      "geolocation 'self' one.example.com; camera 'self' two.example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that setting an empty value removes a directive.
   *
   * @covers ::setDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testSetEmpty() {
    $policy = new FeaturePolicy();
    $policy->setDirective('geolocation', FeaturePolicy::POLICY_SELF);
    $policy->setDirective('camera', [FeaturePolicy::POLICY_SELF]);
    $policy->setDirective('camera', []);

    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );


    $policy = new FeaturePolicy();
    $policy->setDirective('geolocation', FeaturePolicy::POLICY_SELF);
    $policy->setDirective('camera', [FeaturePolicy::POLICY_SELF]);
    $policy->setDirective('camera', '');

    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that appending an empty value doesn't change the directive.
   *
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testAppendEmpty() {
    $policy = new FeaturePolicy();

    $policy->appendDirective('geolocation', FeaturePolicy::POLICY_SELF);
    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );

    $policy->appendDirective('geolocation', '');
    $policy->appendDirective('camera', []);
    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that source values are not repeated in the header.
   *
   * @covers ::setDirective
   * @covers ::appendDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testDuplicate() {
    $policy = new FeaturePolicy();

    // Provide identical sources in an array.
    $policy->setDirective('geolocation', [FeaturePolicy::POLICY_SELF, FeaturePolicy::POLICY_SELF]);
    // Provide identical sources in a string.
    $policy->setDirective('camera', 'one.example.com one.example.com');

    // Provide identical sources through both set and append.
    $policy->setDirective('microphone', ['two.example.com', 'two.example.com']);
    $policy->appendDirective('microphone', ['two.example.com', 'two.example.com']);

    $this->assertEquals(
      "geolocation 'self'; camera one.example.com; microphone two.example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that removed directives are not output in the header.
   *
   * @covers ::removeDirective
   * @covers ::isValidDirectiveName
   * @covers ::getHeaderValue
   */
  public function testRemove() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', [FeaturePolicy::POLICY_SELF]);
    $policy->setDirective('camera', 'example.com');

    $policy->removeDirective('camera');

    $this->assertEquals(
      "geolocation 'self'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test that removing an invalid directive name causes an exception.
   *
   * @covers ::removeDirective
   * @covers ::isValidDirectiveName
   * @covers ::validateDirectiveName
   *
   * @expectedException \InvalidArgumentException
   */
  public function testRemoveInvalid() {
    $policy = new FeaturePolicy();

    $policy->removeDirective('foo');
  }

  /**
   * Test that invalid directive values cause an exception.
   *
   * @covers ::appendDirective
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidValue() {
    $policy = new FeaturePolicy();

    $policy->appendDirective('geolocation', 12);
  }

  /**
   * Test reducing the source list when 'none' is included.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListWithNone() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', [
      FeaturePolicy::POLICY_NONE,
      'example.com',
      "'hash-123abc'",
    ]);
    $this->assertEquals(
      "geolocation 'none'",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test reducing source list when any host allowed.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListAny() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', [
      FeaturePolicy::POLICY_ANY,
      'example.com',
      'https://example.com',
      'http:',
      'https:',
    ]);
    $this->assertEquals(
      "geolocation *",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test reducing the source list when 'http:' is included.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListWithHttp() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', [
      'http:',
      // Hosts without protocol should be kept.
      // (e.g. this would allow ftp://example.com)
      'example.com',
      // HTTP hosts should be removed.
      'http://example.org',
      'https://example.net',
    ]);

    $this->assertEquals(
      "geolocation http: example.com",
      $policy->getHeaderValue()
    );
  }

  /**
   * Test reducing the source list when 'https:' is included.
   *
   * @covers ::reduceSourceList
   */
  public function testReduceSourceListWithHttps() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', [
      'https:',
      // Non-secure hosts should be kept.
      'example.com',
      'http://example.org',
      // Secure Hosts should be removed.
      'https://example.net',
    ]);

    $this->assertEquals(
      "geolocation https: example.com http://example.org",
      $policy->getHeaderValue()
    );
  }

  /**
   * @covers ::__toString
   */
  public function testToString() {
    $policy = new FeaturePolicy();

    $policy->setDirective('geolocation', FeaturePolicy::POLICY_SELF);
    $policy->setDirective('camera', [FeaturePolicy::POLICY_SELF, 'example.com']);

    $this->assertEquals(
      "Feature-Policy: geolocation 'self'; camera 'self' example.com",
      $policy->__toString()
    );
  }

}
