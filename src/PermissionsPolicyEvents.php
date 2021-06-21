<?php

namespace Drupal\permissionspolicy;

/**
 * Contains all events dispatched by Permissions Policy module.
 *
 * @package Drupal\csp
 */
final class PermissionsPolicyEvents {

  /**
   * Name of event fired to alter Permissions Policies for the current request.
   *
   * The event listener receives a
   * \Drupal\permissionspolicy\Event\PolicyAlterEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const POLICY_ALTER = 'permissionspolicy.policy_alter';

}
