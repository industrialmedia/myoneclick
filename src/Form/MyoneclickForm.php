<?php

namespace Drupal\myoneclick\Form;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class MyoneclickForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;


  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * Constructs a new MyoneclickForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MessengerInterface $messenger, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ConfigFactoryInterface $config_factory) {
    $this->messenger = $messenger;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
    $this->configFactory = $config_factory;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    /* @var \Drupal\commerce_cart\CartManagerInterface $cart_manager */
    $cart_manager = $container->get('commerce_cart.cart_manager');
    /* @var \Drupal\commerce_cart\CartProviderInterface $cart_provider */
    $cart_provider = $container->get('commerce_cart.cart_provider');
    /* @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver */
    $order_type_resolver = $container->get('commerce_order.chain_order_type_resolver');
    /* @var \Drupal\commerce_store\CurrentStoreInterface $current_store */
    $current_store = $container->get('commerce_store.current_store');
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');

    return new static(
      $messenger,
      $cart_manager,
      $cart_provider,
      $order_type_resolver,
      $current_store,
      $config_factory
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myoneclick_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProductInterface $commerce_product = NULL) {
    $myoneclick_config = $this->configFactory->get('myoneclick.settings');
    $form['product_id'] = array(
      '#type' => 'value',
      '#value' => $commerce_product->id(),
    );
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $myoneclick_config->get('form.name_label'),
      '#default_value' => $this->currentUser()->getAccountName(),
      '#required' => TRUE,
    ];
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $myoneclick_config->get('form.phone_label'),
      '#required' => TRUE,
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $myoneclick_config->get('form.mail_label'),
      '#default_value' => $this->currentUser()->getEmail(),
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $myoneclick_config->get('form.submit_label'),
    ];

    $noindex_nofollow = [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'robots',
        'content' => 'noindex,nofollow',
      ],
    ];
    $form['#attached']['html_head'][] = [$noindex_nofollow, 'noindex_nofollow'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $product_id = $form_state->getValue('product_id');
    $name = $form_state->getValue('name');
    $phone = $form_state->getValue('phone');
    $mail = $form_state->getValue('mail');
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = Product::load($product_id);
    /** @var \Drupal\commerce_product\Entity\ProductVariation[] $product_variations */
    $product_variations = $product->getVariations();
    $product_variation = $product_variations[0];
    $order_item = $this->cartManager->createOrderItem($product_variation);
    $order_type_id = $this->orderTypeResolver->resolve($order_item);
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $this->currentStore->getStore();
    $cart = $this->cartProvider->getCart($order_type_id, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type_id, $store);
    }
    $this->cartManager->addOrderItem($cart, $order_item);

    $cart->set('field_order_fio', $name);
    $cart->set('field_order_phone', $phone);
    $cart->setEmail($mail);

    $step_id = 'complete';
    $cart->set('checkout_step', $step_id);
    $this->onStepChange($cart, $step_id);
    $cart->save();

    //$checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($cart);
    //$checkout_flow_plugin = $checkout_flow->getPlugin(); // Так нельзя потому что $checkout_flow_plugin->order берет из урла
    //$checkout_flow_plugin->redirectToStep($step_id); // изменить тоже нельзя, потому что значение protected и нет функции сетера

    $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS); // Очищаем системные сообщения
    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $cart->id(),
      'step' => $step_id
    ]);

  }


  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param $step_id
   */
  protected function onStepChange(OrderInterface &$order, $step_id) {
    if ($step_id == 'payment') {
      $order->lock();
    }
    elseif ($step_id != 'payment') {
      $order->unlock();
    }
    if ($step_id == 'complete') {
      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
    }
  }


}
