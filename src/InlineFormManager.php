<?php

namespace Drupal\inline_entity_form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\inline_entity_form\Annotation\InlineForm;
use Drupal\inline_entity_form\Plugin\InlineForm\EntityInlineFormInterface;
use Drupal\inline_entity_form\Plugin\InlineForm\InlineFormInterface;

/**
 * Manages discovery and instantiation of inline form plugins.
 *
 * @see \Drupal\inline_entity_form\Annotation\InlineForm
 * @see plugin_api
 */
class InlineFormManager extends DefaultPluginManager {

  /**
   * Constructs a new InlineFormManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/InlineForm', $namespaces, $module_handler, InlineFormInterface::class, InlineForm::class);

    $this->alterInfo('inline_form_info');
    $this->setCacheBackend($cache_backend, 'inline_form_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\inline_entity_form\Plugin\InlineForm\InlineFormInterface
   *   The inline form plugin.
   */
  public function createInstance($plugin_id, array $configuration = [], EntityInterface $entity = NULL) {
    $plugin = parent::createInstance($plugin_id, $configuration);
    if ($plugin instanceof EntityInlineFormInterface) {
      if (!$entity) {
        throw new \RuntimeException(sprintf('The %s inline form requires an entity.', $plugin_id));
      }
      $plugin->setEntity($entity);
    }
    // Guard against plugins with an incorrect base class / interface.
    if ($entity) {
      assert($plugin instanceof EntityInlineFormInterface);
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The inline form %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
