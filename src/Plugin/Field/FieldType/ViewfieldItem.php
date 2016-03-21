<?php

/**
 * @file
 * Contains \Drupal\viewfield\Plugin\Field\FieldType\ViewfieldItem.
 */

namespace Drupal\viewfield\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;


/**
 * Plugin implementation of the 'viewfield' field type.
 *
 * @FieldType(
 *   id = "viewfield",
 *   label = @Translation("Viewfield"),
 *   description = @Translation("Viewfield field type. Stores view name and arguments."),
 *   default_widget = "viewfield_select",
 *   default_formatter = "viewfield_default"
 * )
 */
class ViewfieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getValue();
    return empty($value['view_name']) || empty($value['view_display']);
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'force_default' => 0,
      'allowed_views' => array(),
    ) + parent::defaultFieldSettings();
  }


  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'view_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => 128,
        ),
        'view_display' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => 128,
        ),
        'view_args' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => 255,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['view_name'] = DataDefinition::create('string')
      ->setLabel(t('View name'));
    $properties['view_display'] = DataDefinition::create('string')
      ->setLabel(t('View display'));
    $properties['view_args'] = DataDefinition::create('string')
      ->setLabel(t('View arguments'));
    return $properties;
  }

 /**
  * {@inheritdoc}
  */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['force_default'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Always use default value'),
      '#default_value' => $this->getSetting('force_default'),
      '#description'   => t('Hides this field in forms and enforces the configured default value. If this is checked, you must provide a default value.'),
    );

    $element['allowed_views'] = array(
      '#type'          => 'checkboxes',
      '#title'         => t('Allowed views'),
      '#options'       => Views::getViewsAsOptions(TRUE, 'enabled'),
      '#default_value' => $this->getSetting('allowed_views'),
      '#description'   => t('Only selected views will be available for content authors. Leave empty to allow all.'),
    );

    $element['#element_validate'] = [[get_class($this), 'fieldSettingsFormValidate']];
    return $element;
  }

  /**
   * Form element validation handler; Invokes selection plugin's validation.
   *
   * @param array $element
   *   The form element where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function fieldSettingsFormValidate(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = $form_state->getFormObject()->getEntity();
    $field_name = $field->getName();

    $form_input = $form_state->getValues();
    $widget_values = $form_input['default_value_input'][$field_name][0];
    if ($element['force_default']['#value']) {
      if (empty($widget_values['view_name']) || empty($widget_values['view_display'])) {
        $form_state->setErrorByName('default_value_input', t('%title requires a default value.', array(
          '%title' => $element['force_default']['#title'],
        )));
      }
    }

    $form_state->setValueForElement($element['allowed_views'], array_filter($element['allowed_views']['#value']));
  }
}
