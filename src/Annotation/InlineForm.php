<?php

namespace Drupal\inline_entity_form\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the inline form plugin annotation object.
 *
 * Plugin namespace: Plugin\InlineForm.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class InlineForm extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
