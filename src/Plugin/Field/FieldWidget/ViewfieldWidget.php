<?php

namespace Drupal\viewfield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'viewfield' widget.
 *
 * @FieldWidget(
 *   id = "viewfield_select",
 *   label = @Translation("Select List"),
 *   field_types = {
 *     "viewfield"
 *   }
 * )
 */
class ViewfieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    $view = NULL;
    if (isset($items[$delta]->view_name) && isset($items[$delta]->view_display)) {
      $view = $items[$delta]->view_name . ':' . $items[$delta]->view_display;
    }

    $element['view'] = [
      '#type' => 'select',
      '#title' => t('View display'),
      '#options' => $this->getPotentialReferences(),
      '#empty_value' => 0,
      '#access' => !$field_settings['force_default'],
      '#default_value' => $view,
      '#required' => $element['#required'],
    ];
    $element['view_args'] = [
      '#type' => 'textfield',
      '#title' => t('Arguments'),
      '#default_value' => isset($items[$delta]->view_args) ? $items[$delta]->view_args : NULL,
      '#access' => !$field_settings['force_default'],
      '#description' => t('A comma separated list of arguments to pass to the selected view.'),
    ];
    // @todo make configurable.
    $element['view_args']['#access'] = FALSE;

    return $element;
  }

  /**
   * Returns a select options list of views displays of allowed views.
   *
   * @return array
   *   An array with the allowed and enabled views and displays.
   */
  protected function getPotentialReferences() {
    $allowed_views = $this->getFieldSetting('allowed_views');
    $allowed_display_plugins = ['block', 'embed'];

    $options = [];
    $views = Views::getEnabledViews();
    /** @var \Drupal\views\Entity\View $view */
    foreach ($views as $id => $view) {
      if (!empty($allowed_views) && isset($allowed_views[$id])) {
        $label = $view->label();
        foreach ($view->get('display') as $display_id => $display) {
          if (in_array($display['display_plugin'], $allowed_display_plugins)) {
            $options[$label][$id . ':' . $display['id']] = $display['display_title'];
          }
        }
      }
    }
    ksort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (!empty($value['view'])) {
        list($value['view_name'], $value['view_display']) = explode(':', $value['view']);
      }
    }
    return $values;
  }

}
