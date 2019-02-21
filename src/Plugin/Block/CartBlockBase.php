<?php

namespace Drupal\commerce_cart_blocks\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CartBlockBase class.
 */
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
        'cart' => $this->t('Cart'),
        'checkout' => $this->t('Checkout'),
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

  /**
   * {@inheritdoc}
   */
  protected function buildCache() {
    $cacheableMetadata = $this->getCacheabilityMetadata();

    return [
      'contexts' => $cacheableMetadata->getCacheContexts(),
      'tags' => $cacheableMetadata->getCacheTags(),
      'max-age' => $cacheableMetadata->getCacheMaxAge(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function isInCart() {
    return \Drupal::routeMatch()->getRouteName() === 'commerce_cart.page';
  }

  /**
   * {@inheritdoc}
   */
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
          '#title' => $this->configuration['checkout_link_text'],
          '#url' => Url::fromRoute('commerce_checkout.form', [
            'commerce_order' => $cart->id(),
          ]),
        ];
      }
    }

    if ($displayLinks['cart']) {
      $links[] = [
        '#type' => 'link',
        '#title' => $this->configuration['cart_link_text'],
        '#url' => Url::fromRoute('commerce_cart.page'),
      ];
    }

    return $links;
  }

  /**
   * Gets the text representation of the count of items
   */
  protected function getCountText() {
    return $this->formatPlural($this->getCartCount(), $this->configuration['count_text_singular'], $this->configuration['count_text_plural']);
  }

  /**
   * Gets the total price of the carts
   */
  protected function getTotal() {
    $carts = $this->getCarts();
    /** @var OrderInterface $firstCart */
    $firstCart = array_shift($carts);

    if (!empty($firstCart)) {
      $price = $firstCart->getTotalPrice();

      foreach ($carts as $cart_id => $cart) {
        $price->add($cart->getTotalPrice());
      }
    } else {
      $price = $this->createPrice(0);
    }

    return $price;
  }

  protected function createPrice($amount) {
    /** @var \Drupal\commerce_store\StoreStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_store');
    $defaultStore = $storage->loadDefault();
    $currencyCode = $defaultStore ? $defaultStore->getDefaultCurrencyCode() : 'USD';

    return new Price($amount, $currencyCode);
  }

  /**
   * Gets the total price as a formatted string.
   *
   * @return mixed|null
   */
  protected function getTotalText() {
    $element = [];
    $element['price'] = [
      '#type' => 'inline_template',
      '#template' => '{{ price|commerce_price_format }}',
      '#context' => [
        'price' => $this->getTotal(),
      ],
    ];

    return render($element);
  }

  /**
   * {@inheritdoc}
   */
  protected function getLibraries() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheabilityMetadata() {
    $carts = $this->getCarts();

    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheContexts(['user', 'session']);

    foreach ($carts as $cart) {
      $cacheableMetadata->addCacheableDependency($cart);
    }

    return $cacheableMetadata;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  protected function getCarts() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    return array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasItems() && $cart->cart->value;
    });
  }

  /**
   * {@inheritdoc}
   */
  protected function shouldHide() {
    return ($this->configuration['hide_if_empty'] && !$this->getCartCount());
  }

  /**
   * Gets the cart views for each cart.
   *
   * @return array
   *   An array of view ids keyed by cart order ID.
   */
  protected function getCartViews() {
    $carts = $this->getCarts();
    $availableViews = $this->getAvailableViews($carts);

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
   */
  private function getOrderTypeIds(array $carts) {
    return array_map(function ($cart) {
      return $cart->bundle();
    }, $carts);
  }

  /**
   * {@inheritdoc}
   */
  private function getAvailableViews(array $carts) {
    try {
      $orderTypeIds = $this->getOrderTypeIds($carts);
      $orderTypeStorage = $this->entityTypeManager->getStorage('commerce_order_type');
      $orderTypes = $orderTypeStorage->loadMultiple(array_unique($orderTypeIds));

      $availableViews = [];
      foreach ($orderTypeIds as $cartId => $order_type_id) {
        /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
        $order_type = $orderTypes[$order_type_id];
        $availableViews[$cartId] = $order_type->getThirdPartySetting('commerce_cart', 'cart_block_view', 'commerce_cart_block');
      }

      return $availableViews;
    }
    catch (InvalidPluginDefinitionException $e) {
      return [];
    }
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

