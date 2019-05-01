<?php

namespace Drupal\inline_entity_form\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inline_entity_form\InlineFormManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'inline_entity_form_embedded' widget.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_embedded",
 *   label = @Translation("Inline Entity Form - Embedded"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = false
 * )
 */
class Embedded extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inline form manager.
   *
   * @var \Drupal\inline_entity_form\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new Embedded object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\inline_entity_form\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'form_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $target_entity_type_id = $this->getFieldSetting('target_type');
    $element = [];
    $element['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form mode'),
      '#default_value' => $this->getSetting('form_mode'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions($target_entity_type_id),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $target_entity_type_id = $this->getFieldSetting('target_type');
    $form_mode = $this->getSetting('form_mode');
    $form_modes = $this->entityDisplayRepository->getFormModeOptions($target_entity_type_id);
    $summary = [];
    if (isset($form_modes[$form_mode])) {
      $form_mode_label = $form_modes[$form_mode];
    }
    else {
      $form_mode_label = $this->t('Default');
    }
    $summary[] = t('Form mode: @mode', ['@mode' => $form_mode_label]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // Do not allow this widget to be used as a default value widget.
    if ($this->isDefaultValueWidget($form_state)) {
      return $form;
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);
    $entity = $item->entity ?: $this->createEntity($items->getEntity());
    $inline_form = $this->inlineFormManager->createInstance('content_entity', [
      'form_mode' => $this->getSetting('form_mode'),
    ], $entity);

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      // Remove the "required" cue, it's display-only and confusing.
      '#required' => FALSE,
      '#after_build' => [
        [get_class($this), 'removeTranslatabilityClue'],
      ],
      '#field_title' => $this->fieldDefinition->getLabel(),
    ] + $element;

    $element['entity'] = [
      '#parents' => array_merge($element['#field_parents'], [$items->getName(), $delta, 'entity']),
      '#inline_form' => $inline_form,
    ];
    $element['entity'] = $inline_form->buildInlineForm($element['entity'], $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = parent::formMultipleElements($items, $form, $form_state);

    // By default, Drupal core shows an empty item when the field is unlimited.
    // This is unsuitable for editing entities, because field validation will
    // ensure that the form is not submittable without creating another entity.
    if ($element['#cardinality'] == -1 && $element['#max_delta'] > 0 && !$form_state->isSubmitted()) {
      $max = $element['#max_delta'];
      unset($element[$max]);
      $element['#max_delta'] = $max - 1;
      $items->removeItem($max);
      // Decrement the items count.
      $field_name = $element['#field_name'];
      $parents = $element[0]['#field_parents'];
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      $field_state['items_count']--;
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    return $element;
  }

  /**
   * After-build callback for removing the translatability clue from the widget.
   *
   * Entity reference fields are usually not translatable, to avoid different
   * translations having different references. However, that causes
   * ContentTranslationHandler to add an "(all languages)" suffix to the widget
   * title. That suffix is incorrect, since the content_entity inline form does
   * ensure that specific entity translations are being edited.
   *
   * @see ContentTranslationHandler::addTranslatabilityClue()
   */
  public static function removeTranslatabilityClue(array $element, FormStateInterface $form_state) {
    $element['#title'] = $element['#field_title'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    foreach ($values as $delta => $value) {
      $original_delta = $value['_original_delta'];
      $element = NestedArray::getValue($form, [$field_name, 'widget', $original_delta]);
      /** @var \Drupal\inline_entity_form\Plugin\InlineForm\EntityInlineFormInterface $inline_form */
      $inline_form = $element['entity']['#inline_form'];

      $values[$delta] = [
        '_original_delta' => $original_delta,
        'entity' => $inline_form->getEntity(),
      ];
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_entity_type_id = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    $target_entity_type = \Drupal::entityTypeManager()->getDefinition($target_entity_type_id);
    if (!$target_entity_type->getKey('bundle')) {
      // The target entity type doesn't use bundles, no need to validate them.
      return TRUE;
    }
    $handler_settings = $field_definition->getSetting('handler_settings');
    $target_bundles = [];
    if (!empty($handler_settings['target_bundles'])) {
      $target_bundles = $handler_settings['target_bundles'];
    }

    return count($target_bundles) === 1;
  }

  /**
   * Creates a new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent_entity
   *   The parent entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  protected function createEntity(EntityInterface $parent_entity) {
    $target_entity_type_id = $this->getFieldSetting('target_type');
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $values = [];
    if ($bundle_key = $target_entity_type->getKey('bundle')) {
      $values[$bundle_key] = $this->getBundle();
    }
    if ($langcode_key = $target_entity_type->getKey('langcode')) {
      $values[$langcode_key] = $parent_entity->language()->getId();
    }
    $entity = $this->entityTypeManager->getStorage($target_entity_type_id)->create($values);

    return $entity;
  }

  /**
   * Gets the entity bundle specified for the reference field.
   *
   * @return string|null
   *   The bundle, or NULL if not known.
   */
  protected function getBundle() {
    if (!empty($this->getFieldSetting('handler_settings')['target_bundles'])) {
      return reset($this->getFieldSetting('handler_settings')['target_bundles']);
    }
  }

}
