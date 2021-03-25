<?php

namespace Drupal\viewfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'viewfield_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "viewfield_summary",
 *   label = @Translation("Summary of view"),
 *   field_types = {
 *     "viewfield"
 *   }
 * )
 */
class ViewfieldSummaryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta]['#markup'] = $item->view_name . ' ' . $item->view_display;
    }
    return $elements;
  }

}
