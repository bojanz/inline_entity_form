<?php

/**
 * @file
 * Hooks provided by the Inline Entity Form module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations before an inline form is rendered.
 *
 * In addition to hook_inline_form_alter(), which is called for all
 * inline forms, there is also hook_inline_form_PLUGIN_ID_alter()
 * which allows targeting an inline form via plugin ID.
 *
 * Generic alter hooks are called before the plugin-specific alter hooks.
 *
 * @param array $inline_form
 *   The inline form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $complete_form
 *   The complete form structure.
 *
 * @see hook_inline_form_PLUGIN_ID_alter()
 *
 * @ingroup inline_entity_form
 */
function hook_inline_form_alter(array &$inline_form, \Drupal\Core\Form\FormStateInterface $form_state, array &$complete_form) {
  /** @var \Drupal\inline_entity_form\Plugin\InlineForm\EntityInlineFormInterface $plugin */
  $plugin = $inline_form['#inline_form'];
  if ($plugin->getPluginId() == 'content_entity') {
    $entity = $plugin->getEntity();
    if ($entity->getEntityTypeId() == 'profile' && $entity->bundle() == 'customer') {
      // Hide the address field.
      $inline_form['address']['#access'] = FALSE;
    }
  }
}

/**
 * Provide a plugin-specific inline form alteration.
 *
 * Modules can implement hook_inline_form_PLUGIN_ID_alter()
 * to modify a specific inline form, rather than implementing
 * hook_inline_form_alter() and checking the plugin ID.
 *
 * Plugin-specific alter hooks are called after the general alter hooks.
 *
 * @param array $inline_form
 *   The inline form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $complete_form
 *   The complete form structure.
 *
 * @see hook_inline_form_alter()
 *
 * @ingroup inline_entity_form
 */
function hook_inline_form_PLUGIN_ID_alter(array &$inline_form, \Drupal\Core\Form\FormStateInterface $form_state, array &$complete_form) {
  // Modification for the inline form with the given plugin ID goes here.
  // For example, if PLUGIN_ID is "content_entity" this code would run only
  // for the content_entity form.
  /** @var \Drupal\inline_entity_form\Plugin\InlineForm\EntityInlineFormInterface $plugin */
  $plugin = $inline_form['#inline_form'];
  $entity = $plugin->getEntity();
  if ($entity->getEntityTypeId() == 'profile' && $entity->bundle() == 'customer') {
    // Hide the address field.
    $inline_form['address']['#access'] = FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
