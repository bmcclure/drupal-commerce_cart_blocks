<?php

namespace Drupal\commerce_cart_blocks\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "commerce_cart_blocks_button",
 *   admin_label = @Translation("Cart button"),
 *   category = @Translation("Commerce cart blocks")
 * )
 */
class CartButtonBlock extends CartBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaultConfig = [
      'dropdown' => TRUE,
      'button_label' => '@items',
      'icon_type' => 'image',
      'icon_class' => 'fa fa-shopping-cart',
    ];
    return array_merge(parent::defaultConfiguration(), $defaultConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['commerce_cart_dropdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display cart contents in a dropdown'),
      '#default_value' => $this->configuration['dropdown'],
    ];

    $form['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#description' => $this->t('The text to show on the button, optionally using the @items and @total placeholders.'),
      '#default_value' => $this->configuration['button_label'],
    ];

    $form['icon_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Icon type'),
      '#description' => $this->t('Select the type of icon to display, if any.'),
      '#default_value' => $this->configuration['icon_type'],
      '#options' => [
        'image' => $this->t('Image'),
        'class' => $this->t('Icon class'),
        '' => $this->t('No icon'),
      ],
    ];

    $form['icon_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon class'),
      '#description' => $this->t('If using the Class icon type, these are the CSS classes that will be applied.'),
      '#default_value' => $this->configuration['icon_class'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['dropdown'] = $form_state->getValue('commerce_cart_dropdown');
    $this->configuration['button_label'] = $form_state->getValue('button_label');
    $this->configuration['icon_type'] = $form_state->getValue('icon_type');
    $this->configuration['icon_class'] = $form_state->getValue('icon_class');

    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->shouldHide()) {
      return [];
    }

    $content = [];
    if ($this->configuration['dropdown']) {
      $content = [
        '#theme' => 'commerce_cart_blocks_cart',
        '#count' => $this->getCartCount(),
        '#heading' => $this->buildHeading(),
        '#content' => $this->getCartViews(),
        '#links' => $this->buildLinks(),
      ];
    }

    return [
      '#attached' => [
        'library' => $this->getLibraries(),
      ],
      '#theme' => 'commerce_cart_blocks_cart_button',
      '#count' => $this->getCartCount(),
      '#button_label' => $this->getButtonLabel(),
      '#in_cart' => $this->isInCart(),
      '#icon' => $this->buildIcon(),
      '#url' => Url::fromRoute('commerce_cart.page')->toString(),
      '#content' => $content,
      '#cache' => $this->buildCache(),
    ];
  }

  /**
   * Builds the icon output
   */
  private function buildIcon() {
    $iconType = $this->configuration['icon_type'];

    return ($iconType === 'image') ? $this->getIconImage() : $this->getIconTag();
  }

  /**
   * Gets the image for the icon
   */
  private function getIconImage() {
    return [
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'commerce') . '/icons/ffffff/cart.png',
      '#alt' => $this->t('Shopping cart'),
    ];
  }

  /**
   * Gets the HTML tag used for the icon as markup
   */
  private function getIconTag() {
    $iconType = $this->configuration['icon_type'];
    return [
      '#type' => 'markup',
      '#markup' => ($iconType === 'class') ? '<i class="' . $this->configuration['icon_class'] . '"></i>' : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getLibraries() {
    return [
      'commerce_cart_blocks/commerce_cart_blocks_cart',
      'commerce_cart_blocks/commerce_cart_blocks_button',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCartViews() {
    $cartViews = [];

    if ($this->configuration['dropdown']) {
      $cartViews = parent::getCartViews();
    }

    return $cartViews;
  }

  /**
   * Gets the text representation of the count of items
   */
  protected function getButtonLabel() {
    $buttonLabel = $this->configuration['button_label'];
    return str_replace(array('@items', '@total'), array($this->getCountText(), $this->getTotalText()), $buttonLabel);
  }

}
