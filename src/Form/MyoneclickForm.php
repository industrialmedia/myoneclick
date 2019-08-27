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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_shipping\PackerManagerInterface;
use Drupal\Core\Utility\Token;

class MyoneclickForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The packer manager.
   *
   * @var \Drupal\commerce_shipping\PackerManagerInterface
   */
  protected $packerManager;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;


  /**
   * Constructs a new MyoneclickForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
   * @param \Drupal\commerce_shipping\PackerManagerInterface $packer_manager
   *   The packer manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   */
  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ConfigFactoryInterface $config_factory, PackerManagerInterface $packer_manager, Token $token) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
    $this->configFactory = $config_factory;
    $this->packerManager = $packer_manager;
    $this->token = $token;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
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
    /* @var \Drupal\commerce_shipping\PackerManagerInterface $packer_manager */
    $packer_manager = $container->get('commerce_shipping.packer_manager');
    /* @var \Drupal\Core\Utility\Token $token */
    $token = $container->get('token');

    return new static(
      $messenger,
      $entity_type_manager,
      $cart_manager,
      $cart_provider,
      $order_type_resolver,
      $current_store,
      $config_factory,
      $packer_manager,
      $token
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

    if (empty($form['#prefix'])) {
      $form['#prefix'] = '';
    }
    $text_top = $myoneclick_config->get('form.text_top');
    if (!empty($text_top)) {
      $text_top = $this->token->replace($text_top, ['commerce_product' => $commerce_product]);
      $form['#prefix'] .= '<div class="text-top-wrapper">' . $text_top . '</div>';
    }


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
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $myoneclick_config->get('form.city_label'),
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
    $city = $form_state->getValue('city');

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


    $cart->setEmail($mail);
    $cart->set('field_order_oneclick', TRUE);

    $myoneclick_config = $this->configFactory->get('myoneclick.settings');
    $save_fields = [
      'save.fields.name',
      'save.fields.phone',
      'save.fields.city'
    ];
    foreach ($save_fields as $save_field) {
      $value = NULL;
      switch ($save_field) {
        case 'save.fields.name':
          $value = $name;
          break;
        case 'save.fields.phone':
          $value = $phone;
          break;
        case 'save.fields.city':
          $value = $city;
          break;
      }
      if (!empty($value)) {
        $save_field_value = $myoneclick_config->get($save_field);
        if ($save_field_value) {
          $save_field_value = explode('|', $save_field_value);
          $field_name = $save_field_value[1];
          if ($save_field_value[0] == 'commerce_order') {
            $cart->set($field_name, $value);
          }
          elseif ($save_field_value[0] == 'profile') {
            $shipping_profile = $this->getShippingProfile($cart);
            $shipping_profile->set($field_name, $value);
            /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
            $shipments = $cart->get('shipments')->referencedEntities();
            list($shipments, $removed_shipments) = $this->packerManager->packToShipments($cart, $shipping_profile, $shipments);
            $shipments[0]->setShippingProfile($shipping_profile);
            $shipping_method = $this->getShippingMethod($cart);
            $shipments[0]->setShippingMethod($shipping_method);
            /** @var \Drupal\commerce_shipping\ShippingService $shipping_service */
            $shipping_services = $shipping_method->getPlugin()->getServices();
            $shipping_service = reset($shipping_services);
            $shipments[0]->setShippingService($shipping_service->getId());
            $cart->set('shipments', $shipments);
          }
        }
      }
    }


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


  /**
   * Gets the shipping profile.
   *
   * The shipping profile is assumed to be the same for all shipments.
   * Therefore, it is taken from the first found shipment, or created from
   * scratch if no shipments were found.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The shipping profile.
   */
  protected function getShippingProfile(\Drupal\commerce_order\Entity\OrderInterface $order) {
    $shipping_profile = NULL;
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    foreach ($order->shipments->referencedEntities() as $shipment) {
      $shipping_profile = $shipment->getShippingProfile();
      break;
    }
    if (!$shipping_profile) {
      $shipping_profile = $this->entityTypeManager->getStorage('profile')
        ->create([
          'type' => 'customer',
          'uid' => $order->getCustomerId(),
        ]);
    }
    return $shipping_profile;
  }


  protected function getShippingMethod(\Drupal\commerce_order\Entity\OrderInterface $order) {
    $shipping_method = NULL;
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    foreach ($order->shipments->referencedEntities() as $shipment) {
      $shipping_method = $shipment->getShippingMethod();
      break;
    }
    /** @var \Drupal\commerce_shipping\ShippingMethodStorageInterface $shipping_method_storage */
    $shipping_method_storage = $this->entityTypeManager->getStorage('commerce_shipping_method');
    if (!$shipping_method) {
      $myoneclick_config = $this->configFactory->get('myoneclick.settings');
      $shipping_method_id = $myoneclick_config->get('save.shipping_method');
      $shipping_method = $shipping_method_storage->load($shipping_method_id);
    }
    if (!$shipping_method) {
      $shipping_methods = $shipping_method_storage->loadMultiple();
      /** @var \Drupal\commerce_shipping\Entity\ShippingMethod $method */
      foreach ($shipping_methods as $method) {
        if ($method->isEnabled()) {
          $shipping_method = $method;
          break;
        }
      }
    }
    return $shipping_method;
  }


}
