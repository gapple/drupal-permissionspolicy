<?php

namespace Drupal\permissionspolicy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\permissionspolicy\PermissionsPolicy;

/**
 * Form for editing Feature Policy module settings.
 */
class PermissionsPolicySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permissionspolicy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'permissionspolicy.settings',
    ];
  }

  /**
   * Constructs a \Drupal\permissionspolicy\Form\FeaturePolicySettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Get the directives that should be configurable.
   *
   * @return array
   *   An array of directive names.
   */
  private function getConfigurableDirectives() {
    $directives = PermissionsPolicy::getDirectiveNames();

    // Reorder directives so they're not grouped by status on the form
    // (standardized, proposed, experimental).
    sort($directives);

    return $directives;
  }

  /**
   * Function to get the policy types.
   *
   * @return array
   *   The policy types.
   */
  public function getPolicyTypes() {
    return [
      'enforce' => $this->t('Enforced'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('permissionspolicy.settings');

    $form['#attached']['library'][] = 'permissionspolicy/admin';

    $form['policies'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Policies'),
    ];

    $directiveNames = static::getConfigurableDirectives();

    $policyTypes = $this->getPolicyTypes();
    foreach ($policyTypes as $policyTypeKey => $policyTypeName) {
      $form[$policyTypeKey] = [
        '#type' => 'details',
        '#title' => $policyTypeName,
        '#group' => 'policies',
        '#tree' => TRUE,
      ];

      if ($config->get($policyTypeKey . '.enable')) {
        $form['policies']['#default_tab'] = 'edit-' . $policyTypeKey;
      }

      $form[$policyTypeKey]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Enable '@type'", ['@type' => $policyTypeName]),
        '#default_value' => $config->get($policyTypeKey . '.enable'),
      ];

      $form[$policyTypeKey]['directives'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Directives'),
        '#description_display' => 'before',
        '#tree' => TRUE,
      ];

      foreach ($directiveNames as $directiveName) {
        $form[$policyTypeKey]['directives'][$directiveName] = [
          '#type' => 'container',
        ];

        $form[$policyTypeKey]['directives'][$directiveName]['enable'] = [
          '#type' => 'checkbox',
          '#title' => $directiveName,
          '#default_value' => !is_null($config->get($policyTypeKey . '.directives.' . $directiveName)),
        ];

        $form[$policyTypeKey]['directives'][$directiveName]['options'] = [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="' . $policyTypeKey . '[directives][' . $directiveName . '][enable]"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $sourceListBase = $config->get($policyTypeKey . '.directives.' . $directiveName . '.base');
        $form[$policyTypeKey]['directives'][$directiveName]['options']['base'] = [
          '#type' => 'radios',
          '#parents' => [$policyTypeKey, 'directives', $directiveName, 'base'],
          '#options' => [
            'none' => "None",
            'empty' => '<em>empty</em>',
            'self' => "Self",
            'any' => "Any",
          ],
          '#default_value' => $sourceListBase ?: 'empty',
        ];

        $form[$policyTypeKey]['directives'][$directiveName]['options']['sources'] = [
          '#type' => 'textarea',
          '#parents' => [$policyTypeKey, 'directives', $directiveName, 'sources'],
          '#title' => $this->t('Additional Sources'),
          '#description' => $this->t('Additional domains or protocols to allow for this directive.'),
          '#default_value' => implode(' ', $config->get($policyTypeKey . '.directives.' . $directiveName . '.sources') ?: []),
          '#states' => [
            'visible' => [
              [':input[name="' . $policyTypeKey . '[directives][' . $directiveName . '][base]"]' => ['value' => 'self']],
              'or',
              [':input[name="' . $policyTypeKey . '[directives][' . $directiveName . '][base]"]' => ['value' => 'empty']],
            ],
          ],
        ];
      }
    }

    // Skip this check when building the form before validation/submission.
    if (empty($form_state->getUserInput())) {
      $enabledPolicies = array_filter(array_keys($policyTypes), function ($policyTypeKey) use ($config) {
        return $config->get($policyTypeKey . '.enable');
      });
      if (empty($enabledPolicies)) {
        $this->messenger()
          ->addWarning($this->t('No policies are currently enabled.'));
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $policyTypes = array_keys($this->getPolicyTypes());
    $directiveNames = PermissionsPolicy::getDirectiveNames();
    foreach ($policyTypes as $policyTypeKey) {
      foreach ($directiveNames as $directiveName) {
        if (($directiveSources = $form_state->getValue([$policyTypeKey, 'directives', $directiveName, 'sources']))) {
          $invalidSources = array_reduce(
            preg_split('/,?\s+/', $directiveSources),
            function ($return, $value) {
              return $return || !(preg_match('<^([a-z]+:)?$>', $value) || static::isValidHost($value));
            },
            FALSE
            );
          if ($invalidSources) {
            $form_state->setError(
              $form[$policyTypeKey]['directives'][$directiveName]['options']['sources'],
              $this->t('Invalid domain or protocol provided.')
              );
          }
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Verifies the syntax of the given URL.
   *
   * Similar to UrlHelper::isValid(), except:
   * - protocol is optional; can only be http or https.
   * - domains must have at least a top-level and secondary domain.
   * - query is not allowed.
   *
   * @param string $url
   *   The URL to verify.
   *
   * @return bool
   *   TRUE if the URL is in a valid format, FALSE otherwise.
   */
  private static function isValidHost($url) {
    return (bool) preg_match("
        /^                                                      # Start at the beginning of the text
        (?:https?:\/\/)?                                        # Look for http or https schemes (optional)
        (?:
          (?:                                                   # A domain name or a IPv4 address
            (?:\*\.)?                                           # Wildcard prefix (optional)
            (?:(?:[a-z0-9\-\.]|%[0-9a-f]{2})+\.)+
            (?:[a-z0-9\-\.]|%[0-9a-f]{2})+
          )
          |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
        )
        (?::[0-9]+)?                                            # Server port number (optional)
        (?:[\/|\?]
          (?:[\w#!:\.\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})     # The path (optional)
        *)?
      $/xi", $url);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('permissionspolicy.settings');

    $directiveNames = PermissionsPolicy::getDirectiveNames();
    $policyTypes = array_keys($this->getPolicyTypes());
    foreach ($policyTypes as $policyTypeKey) {
      $config->clear($policyTypeKey);

      $policyFormData = $form_state->getValue($policyTypeKey);

      $config->set($policyTypeKey . '.enable', !empty($policyFormData['enable']));

      foreach ($directiveNames as $directiveName) {
        if (empty($policyFormData['directives'][$directiveName])) {
          continue;
        }

        $directiveFormData = $policyFormData['directives'][$directiveName];
        $directiveOptions = [];

        if (empty($directiveFormData['enable'])) {
          continue;
        }

        if (in_array($directiveFormData['base'], ['empty', 'self'])) {
          if (!empty($directiveFormData['sources'])) {
            $directiveOptions['sources'] = array_filter(preg_split('/,?\s+/', $directiveFormData['sources']));
          }
        }

        $directiveOptions['base'] = $directiveFormData['base'];
        if ($directiveFormData['base'] == 'empty') {
          $directiveOptions['base'] = '';
        }

        if (!empty($directiveOptions)) {
          $config->set($policyTypeKey . '.directives.' . $directiveName, $directiveOptions);
        }
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
