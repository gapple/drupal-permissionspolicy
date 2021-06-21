/**
 * @file
 * Defines Javascript behaviors for the permissionspolicy module admin form.
 */

(function ($, Drupal) {
  /**
   * Sets summary of policy tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour for policy form tabs.
   */
  Drupal.behaviors.permissionspolicyPolicySummary = {
    attach(context) {
      $(context)
        .find('[data-drupal-selector="edit-policies"] > details')
        .each(function () {
          var $details = $(this);
          var elementPrefix = $details.data('drupal-selector');
          var createPolicyElementSelector = function (name) {
            return '[data-drupal-selector="' + elementPrefix + '-' + name + '"]';
          };

          $details.drupalSetSummary(function () {
            if ($details.find(createPolicyElementSelector('enable')).prop('checked')) {
              var featureCount = $details
                .find(createPolicyElementSelector('features') + ' [name$="[enable]"]:checked')
                .length;
              return Drupal.formatPlural(
                featureCount,
                'Enabled, @featureCount feature',
                'Enabled, @featureCount features',
                { '@featureCount': featureCount },
              );
            }

            return Drupal.t('Disabled');
          });
        });
    },
  };

}(jQuery, Drupal));
