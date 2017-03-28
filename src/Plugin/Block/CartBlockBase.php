<?php

namespace Drupal\commerce_cart_blocks\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class CartBlockBase extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CartBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hide_if_empty' => FALSE,
      'display_links' => ['cart' => 'cart'],
      'cart_link_text' => 'Cart',
      'checkout_link_text' => 'Checkout',
      'count_text_singular' => '@count item',
      'count_text_plural' => '@count items',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['hide_if_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide if empty'),
      '#description' => $this->t('When checked, then the block will be hidden if the cart is empty.'),
      '#default_value' => $this->configuration['hide_if_empty'],
    ];

    $form['display_links'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display links'),
      '#description' => $this->t('Choose which links to display within the block content.'),
      '#options' => [
        'cart' => 'Cart',
        'checkout' => 'Checkout',
      ],
      '#default_value' => $this->configuration['display_links'],
    ];

    $form['cart_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cart link text'),
      '#description' => $this->t('Enter the text for the Cart link, if shown.'),
      '#default_value' => $this->configuration['cart_link_text'],
    ];

    $form['checkout_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checkout link text'),
      '#description' => $this->t('Enter the text for the Checkout link, if shown.'),
      '#default_value' => $this->configuration['checkout_link_text'],
    ];

    $form['count_text_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count text (plural)'),
      '#description' => $this->t('The text to use when describing the number of cart items, including the @count placeholder.'),
      '#default_value' => $this->configuration['count_text_plural'],
    ];

    $form['count_text_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count text (singular)'),
      '#description' => $this->t('The text to use when describing a single cart item, including the @count placeholder.'),
      '#default_value' => $this->configuration['count_text_singular'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('hide_if_empty', $form_state->getValue('hide_if_empty'));
    $this->setConfigurationValue('display_links', $form_state->getValue('display_links'));
    $this->setConfigurationValue('cart_link_text', $form_state->getValue('cart_link_text'));
    $this->setConfigurationValue('checkout_link_text', $form_state->getValue('checkout_link_text'));
    $this->setConfigurationValue('count_text_plural', $form_state->getValue('count_text_plural'));
    $this->setConfigurationValue('count_text_singular', $form_state->getValue('count_text_singular'));
  }

  protected function buildCache() {
    $cachableMetadata = $this->getCacheabilityMetadata();

    return [
      'contexts' => $cachableMetadata->getCacheContexts(),
      'tags' => $cachableMetadata->getCacheTags(),
      'max-age' => $cachableMetadata->getCacheMaxAge(),
    ];
  }

  protected function isInCart() {
    return \Drupal::routeMatch()->getRouteName() == 'commerce_cart.page';
  }

  protected function buildLinks() {
    $links = [];

    $displayLinks = $this->configuration['display_links'];

    if ($displayLinks['checkout']) {
      $carts = $this->getCarts();

      if (!empty($carts)) {
        /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
        $cart = array_shift($carts);

        $links[] = [
          '#type' => 'link',
          '#title' => $this->t($this->configuration['checkout_link_text']),
          '#url' => Url::fromRoute('commerce_checkout.form', [
            'commerce_order' => $cart->id(),
          ]),
        ];
      }
    }

    if ($displayLinks['cart']) {
      $links[] = [
        '#type' => 'link',
        '#title' => $this->t($this->configuration['cart_link_text']),
        '#url' => Url::fromRoute('commerce_cart.page'),
      ];
    }

    return $links;
  }

  protected function getCountText() {
    return $this->formatPlural($this->getCartCount(), $this->configuration['count_text_singular'], $this->configuration['count_text_plural']);
  }

  protected function getLibraries() {
    return [];
  }

  /**
   * @return \Drupal\Core\Cache\CacheableMetadata
   */
  protected function getCacheabilityMetadata() {
    $carts = $this->getCarts();

    $cachableMetadata = new CacheableMetadata();
    $cachableMetadata->addCacheContexts(['user', 'session']);

    foreach ($carts as $cart) {
      $cachableMetadata->addCacheableDependency($cart);
    }

    return $cachableMetadata;
  }

  /**
   * @return int
   */
  protected function getCartCount() {
    $carts = $this->getCarts();

    $count = 0;

    foreach ($carts as $cart_id => $cart) {
      foreach ($cart->getItems() as $order_item) {
        $count += (int) $order_item->getQuantity();
      }
    }

    return $count;
  }

  /**
   * @return \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected function getCarts() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      // There is a chance the cart may have converted from a draft order, but
      // is still in session. Such as just completing check out. So we verify
      // that the cart is still a cart.
      return $cart->hasItems() && $cart->cart->value;
    });

    return $carts;
  }

  protected function shouldHide() {
    return ($this->configuration['hide_if_empty'] && $this->getCartCount() == 0);
  }

  /**
   * Gets the cart views for each cart.
   *
   * @return array An array of view ids keyed by cart order ID.
   * An array of view ids keyed by cart order ID.
   */
  protected function getCartViews() {
    $carts = $this->getCarts();

    $orderTypeIds = array_map(function ($cart) {
      return $cart->bundle();
    }, $carts);

    $orderTypeStorage = $this->entityTypeManager->getStorage('commerce_order_type');
    $orderTypes = $orderTypeStorage->loadMultiple(array_unique($orderTypeIds));

    $availableViews = [];
    foreach ($orderTypeIds as $cartId => $order_type_id) {
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $orderTypes[$order_type_id];
      $availableViews[$cartId] = $order_type->getThirdPartySetting('commerce_cart', 'cart_block_view', 'commerce_cart_block');
    }

    $cartViews = [];

    foreach ($carts as $cartId => $cart) {
      $cartViews[] = [
        '#prefix' => '<div class="cart cart-block">',
        '#suffix' => '</div>',
        '#type' => 'view',
        '#name' => $availableViews[$cartId],
        '#arguments' => [$cartId],
        '#embed' => TRUE,
      ];
    }

    return $cartViews;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Find proper cache tags to make this cacheable
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
