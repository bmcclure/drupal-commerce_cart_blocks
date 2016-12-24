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
    return [
        'display_heading' => FALSE,
        'heading_text' => '@items items in your cart',
      ] + parent::defaultConfiguration();
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
      '#description' => $this->t('The text to use for the heading, which can include the @items placeholder.'),
      '#default_value' => $this->configuration['heading_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_heading'] = $form_state->getValue('display_heading');
    $this->configuration['heading_text'] = $form_state->getValue('heading_text');

    parent::blockSubmit($form, $form_state);
  }

  /**
   * Builds the cart block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    return [
      '#attached' => [
        'library' => $this->getLibraries(),
      ],
      '#theme' => 'commerce_cart_blocks_cart',
      '#count' => $this->getCartCount(),
      '#heading' => $this->buildHeading(),
      '#content' => $this->getCartViews(),
      '#links' => $this->buildLinks(),
      '#cache' => $this->buildCache(),
    ];
  }

  protected function buildHeading() {
    $displayHeading = $this->configuration['display_heading'];

    if (!$displayHeading) {
      return [];
    }

    $heading = $this->t($this->configuration['heading_text'], [
      '@items' => $this->getCountText(),
    ]);

    return [
      '#type' => 'markup',
      '#markup' => $heading,
    ];
  }

  protected function getLibraries() {
    return ['commerce_cart_blocks/commerce_cart_blocks_cart'];
  }
}
