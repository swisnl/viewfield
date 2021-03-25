<?php

namespace Drupal\viewfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\views\Element\View;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'viewfield_default' formatter.
 *
 * @FieldFormatter(
 *   id = "viewfield_default",
 *   label = @Translation("Rendered view"),
 *   field_types = {
 *     "viewfield"
 *   }
 * )
 */
class ViewfieldDefaultFormatter extends ViewfieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $elements = [
      '#entity_type' => $entity->getEntityTypeId(),
      '#entity_id' => $entity->id(),
      '#pre_render' => [[static::class, 'preRenderItem']],
      '#post_render' => [[static::class, 'postRenderItem']],
    ];

    foreach ($items as $delta => $item) {
      $view = Views::getView($item->view_name);
      $arguments = $this->expandViewArguments($item->view_args, $entity);

      if ($view->access($item->view_display)) {
        // We ask ViewExecutable::buildRenderable() to avoid creating a render
        // cache entry for the view output by passing FALSE, because we're
        // going to cache the whole field's entity instead.
        $elements[$delta] = $view->buildRenderable($item->view_display, $arguments, FALSE);
        // Don't use embed as it disables pagers
        // $elements[$delta]['#embed'] = TRUE;
        // We expect to get a final render array, without another
        // top-level #pre_render callback. So, here we make sure that Views'
        // #pre_render callback has already been applied.
        $elements[$delta] = View::preRenderViewElement($elements[$delta]);

        // When view_build is empty, the actual render array output for this
        // View is going to be empty. In that case, return just #cache, so that
        // the render system knows the reasons (cache contexts & tags) why this
        // Views block is empty, and can cache it accordingly.
        if (empty($elements[$delta]['view_build'])) {
          $elements[$delta] = ['#cache' => $elements[$delta]['#cache']];
        }
      }
    }
    return $elements;
  }

}
