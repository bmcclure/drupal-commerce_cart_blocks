<?php

namespace Drupal\commerce_cart_blocks\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "commerce_cart_blocks_cart",
 *   admin_label = @Translation("Cart"),
 *   category = @Translation("Commerce cart blocks")
 * )
 */
class CartBlock extends CartBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaultConfig = [
      'display_heading' => FALSE,
      'heading_text' => '@items in your cart',
      'show_items' => TRUE,
    ];

    return array_merge(parent::defaultConfiguration(), $defaultConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['display_heading'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display heading'),
      '#description' => $this->t('Shows heading text within the block content.'),
      '#default_value' => $this->configuration['display_heading'],
    ];

    $form['heading_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading text'),
      '#description' => $this->t('The text to use for the heading, which can include the @items and @total placeholders.'),
      '#default_value' => $this->configuration['heading_text'],
    ];

    $form['show_items'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show items'),
      '#description' => $this->t('Show the cart items in a table.'),
      '#default_value' => $this->configuration['show_items'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_heading'] = $form_state->getValue('display_heading');
    $this->configuration['heading_text'] = $form_state->getValue('heading_text');
    $this->configuration['show_items'] = $form_state->getValue('show_items');

    parent::blockSubmit($form, $form_state);
  }

  /**
   * Builds the cart block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    if ($this->shouldHide()) {
      return [];
    }

    return [
      '#attached' => [
        'library' => $this->getLibraries(),
      ],
      '#theme' => 'commerce_cart_blocks_cart',
      '#count' => $this->getCartCount(),
      '#heading' => $this->buildHeading(),
      '#content' => $this->buildItems(),
      '#links' => $this->buildLinks(),
      '#in_cart' => $this->isInCart(),
      '#cache' => $this->buildCache(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildItems() {
    $showItems = $this->configuration['show_items'];

    $items = [];

    if ($showItems) {
      $items = $this->getCartViews();
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildHeading() {
    $displayHeading = $this->configuration['display_heading'];

    $result = [];

    if ($displayHeading) {
      $heading = str_replace(array('@items', '@total'), array($this->getCountText(), $this->getTotalText()), $this->configuration['heading_text']);
      $result['#type'] = 'markup';
      $result['#markup'] = $heading;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLibraries() {
    return ['commerce_cart_blocks/commerce_cart_blocks_cart'];
  }

}
