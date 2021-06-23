<?php

namespace Drupal\permissionspolicy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * Get the features that should be configurable.
   *
   * @return array
   *   An array of feature names.
   */
  private function getConfigurableFeatures() {
    $features = PermissionsPolicy::getFeatureNames();

    // Reorder features so they're not grouped by status on the form
    // (standardized, proposed, experimental).
    sort($features);

    return $features;
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

    $featureNames = static::getConfigurableFeatures();

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

      $form[$policyTypeKey]['features'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Features'),
        '#description_display' => 'before',
        '#tree' => TRUE,
      ];

      foreach ($featureNames as $featureName) {
        $form[$policyTypeKey]['features'][$featureName] = [
          '#type' => 'container',
        ];

        $form[$policyTypeKey]['features'][$featureName]['enable'] = [
          '#type' => 'checkbox',
          '#title' => $featureName,
          '#default_value' => !is_null($config->get($policyTypeKey . '.features.' . $featureName)),
        ];

        $form[$policyTypeKey]['features'][$featureName]['options'] = [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="' . $policyTypeKey . '[features][' . $featureName . '][enable]"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $sourceListBase = $config->get($policyTypeKey . '.features.' . $featureName . '.base');
        if (
          $sourceListBase === NULL
          &&
          !empty(PermissionsPolicy::FEATURE_DEFAULT_ALLOWLIST[$featureName])
        ) {
          $sourceListBase = PermissionsPolicy::FEATURE_DEFAULT_ALLOWLIST[$featureName];
          if ($sourceListBase == PermissionsPolicy::ORIGIN_ANY) {
            $sourceListBase = 'any';
          }
        }
        $form[$policyTypeKey]['features'][$featureName]['options']['base'] = [
          '#type' => 'radios',
          '#parents' => [$policyTypeKey, 'features', $featureName, 'base'],
          '#options' => [
            'none' => "None",
            'empty' => '<em>empty</em>',
            'self' => "Self",
            'any' => "Any",
          ],
          '#default_value' => $sourceListBase ?: 'empty',
        ];

        $form[$policyTypeKey]['features'][$featureName]['options']['sources'] = [
          '#type' => 'textarea',
          '#parents' => [$policyTypeKey, 'features', $featureName, 'sources'],
          '#title' => $this->t('Additional Sources'),
          '#description' => $this->t('Additional domains to allow for this feature.'),
          '#default_value' => implode(' ', $config->get($policyTypeKey . '.features.' . $featureName . '.sources') ?: []),
          '#states' => [
            'visible' => [
              [':input[name="' . $policyTypeKey . '[features][' . $featureName . '][base]"]' => ['value' => 'self']],
              'or',
              [':input[name="' . $policyTypeKey . '[features][' . $featureName . '][base]"]' => ['value' => 'empty']],
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
    $featureNames = PermissionsPolicy::getFeatureNames();
    foreach ($policyTypes as $policyTypeKey) {
      foreach ($featureNames as $featureName) {
        if (($origins = $form_state->getValue([$policyTypeKey, 'features', $featureName, 'sources']))) {
          $invalidSources = array_reduce(
            preg_split('/,?\s+/', $origins),
            function ($return, $value) {
              return $return || !(preg_match('<^([a-z]+:)?$>', $value) || static::isValidHost($value));
            },
            FALSE
            );
          if ($invalidSources) {
            $form_state->setError(
              $form[$policyTypeKey]['features'][$featureName]['options']['sources'],
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
        (?:[a-z][a-z0-9\-.+]+:\/\/)?                            # Scheme (optional)
        (?:
          (?:                                                   # A domain name or a IPv4 address
            (?:\*\.)?                                           # Wildcard prefix (optional)
            (?:(?:[a-z0-9\-\.]|%[0-9a-f]{2})+\.)+
            (?:[a-z0-9\-\.]|%[0-9a-f]{2})+
          )
          |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
          |localhost
        )
        (?::(?:[0-9]+|\*))?                                     # Server port number or wildcard (optional)
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

    $featureNames = PermissionsPolicy::getFeatureNames();
    $policyTypes = array_keys($this->getPolicyTypes());
    foreach ($policyTypes as $policyTypeKey) {
      $config->clear($policyTypeKey);

      $policyFormData = $form_state->getValue($policyTypeKey);

      $config->set($policyTypeKey . '.enable', !empty($policyFormData['enable']));

      foreach ($featureNames as $featureName) {
        if (empty($policyFormData['features'][$featureName])) {
          continue;
        }

        $featureFormData = $policyFormData['features'][$featureName];
        $featureOptions = [];

        if (empty($featureFormData['enable'])) {
          continue;
        }

        if (in_array($featureFormData['base'], ['empty', 'self'])) {
          if (!empty($featureFormData['sources'])) {
            $featureOptions['sources'] = array_filter(preg_split('/,?\s+/', $featureFormData['sources']));
          }
        }

        $featureOptions['base'] = $featureFormData['base'];
        if ($featureFormData['base'] == 'empty') {
          $featureOptions['base'] = '';
        }

        if (!empty($featureOptions)) {
          $config->set($policyTypeKey . '.features.' . $featureName, $featureOptions);
        }
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
