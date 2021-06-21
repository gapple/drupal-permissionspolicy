<?php

namespace Drupal\permissionspolicy\Event;

use Drupal\permissionspolicy\PermissionsPolicy;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Event for altering a response Permissions Policy.
 */
class PolicyAlterEvent extends Event {

  /**
   * A Permissions policy.
   *
   * @var \Drupal\permissionspolicy\PermissionsPolicy
   */
  private $policy;

  /**
   * The Response the policy is being applied to.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  private $response;

  /**
   * Create a new PolicyAlterEvent instance.
   *
   * @param \Drupal\permissionspolicy\PermissionsPolicy $policy
   *   A Permissions policy.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The Response the policy is being applied to.
   */
  public function __construct(PermissionsPolicy $policy, Response $response) {
    $this->policy = $policy;
    $this->response = $response;
  }

  /**
   * Retrieve the defined Permissions policy.
   *
   * @return \Drupal\permissionspolicy\PermissionsPolicy
   *   The Permissions policy.
   */
  public function getPolicy(): PermissionsPolicy {
    return $this->policy;
  }

  /**
   * Retrieve the Response the policy is applied to.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The Response the policy is applied to.
   */
  public function getResponse(): Response {
    return $this->response;
  }

}
