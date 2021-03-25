<?php

namespace Drupal\viewfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for viewsfield field formatters.
 */
abstract class ViewfieldFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * The stack of viewviews being rendered recursively.
   *
   * @var array
   */
  protected static $viewfieldStack = [];

  /**
   * The token replacement system.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Token $token_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->tokenService = $token_service;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderItem', 'postRenderItem'];
  }

  /**
   * Expand a view arguments string to an array of arguments.
   *
   * @param string $arguments_str
   *   The views arguments string to be expanded.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be used as token data.
   *
   * @return array
   *   The view arguments array.
   */
  protected function expandViewArguments($arguments_str, EntityInterface $entity) {
    $args = [];

    if (!empty($arguments_str)) {
      $pos = 0;
      while ($pos < strlen($arguments_str)) {
        $found = FALSE;
        // If string starts with a quote, start after quote and get everything
        // before next quote.
        if (strpos($arguments_str, '"', $pos) === $pos) {
          if (($quote = strpos($arguments_str, '"', ++$pos)) !== FALSE) {
            // Skip pairs of quotes.
            while (!(($ql = strspn($arguments_str, '"', $quote)) & 1)) {
              $quote = strpos($arguments_str, '"', $quote + $ql);
            }
            $args[] = str_replace('""', '"', substr($arguments_str, $pos, $quote + $ql - $pos - 1));
            $pos = $quote + $ql + 1;
            $found = TRUE;
          }
        }
        elseif (($comma = strpos($arguments_str, ',', $pos)) !== FALSE) {
          // Otherwise, get everything before next comma.
          $args[] = substr($arguments_str, $pos, $comma - $pos);
          // Skip to after comma and repeat.
          $pos = $comma + 1;
          $found = TRUE;
        }
        if (!$found) {
          $args[] = substr($arguments_str, $pos);
          $pos = strlen($arguments_str);
        }
      }

      $token_data = [$entity->getEntityTypeId() => $entity];
      foreach ($args as $key => $value) {
        $args[$key] = $this->tokenService->replace($value, $token_data);
      }
    }

    return $args;
  }

  /**
   * The #pre_render callback for a viewfield field.
   *
   * @see ViewfieldFormatterBase::postRenderItem()
   */
  public static function preRenderItem($element) {
    // Abort rendering in case of recursion.
    if (isset(self::$viewfieldStack[$element['#entity_type'] . ':' . $element['#entity_id']])) {
      $element['#printed'] = TRUE;
    }
    // Otherwise, add the rendered entity to the stack to prevent recursion.
    else {
      self::$viewfieldStack[$element['#entity_type'] . ':' . $element['#entity_id']] = TRUE;
    }
    return $element;
  }

  /**
   * The #post_render callback for a viewfield field.
   *
   * @see ViewfieldFormatterBase::preRenderItem()
   */
  public static function postRenderItem($content, $element) {
    unset(self::$viewfieldStack[$element['#entity_type'] . ':' . $element['#entity_id']]);
    return $content;
  }

}
