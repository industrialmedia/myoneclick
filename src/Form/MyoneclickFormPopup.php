<?php

namespace Drupal\myoneclick\Form;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

/**
 * Class MyoneclickFormPopup
 * @package Drupal\myoneclick\Form
 */
class MyoneclickFormPopup extends MyoneclickFormBase {


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
    $form['quantity'] = array(
      '#type' => 'hidden',
      '#value' => 1,
    );

    $form['text_top'] = [];
    $text_top = $myoneclick_config->get('form.text_top');
    if (!empty($text_top)) {
      $text_top = $this->token->replace($text_top, ['commerce_product' => $commerce_product]);
      $form['text_top'] = [
        '#type' => 'markup',
        '#markup' => '<div class="text-top-wrapper">' . $text_top . '</div>',
      ];
    }

    $show_fields = !empty($myoneclick_config->get('form.show_fields')) ? $myoneclick_config->get('form.show_fields') : [];
    $show_fields = array_filter($show_fields);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $myoneclick_config->get('form.name_label'),
      '#default_value' => $this->currentUser()->getAccountName(),
      '#required' => TRUE,
      '#access' => !empty($show_fields['name']),
    ];
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $myoneclick_config->get('form.phone_label'),
      '#required' => TRUE,
      '#access' => !empty($show_fields['phone']),
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $myoneclick_config->get('form.mail_label'),
      '#default_value' => $this->currentUser()->getEmail(),
      '#required' => TRUE,
      '#access' => !empty($show_fields['mail']),
    ];
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $myoneclick_config->get('form.city_label'),
      '#required' => TRUE,
      '#access' => !empty($show_fields['city']),
    ];

    $view_id = $myoneclick_config->get('form.view_id');
    $view_display_id = $myoneclick_config->get('form.view_display_id');
    if ($view_id && $view_display_id) {
      $form['view'] = views_embed_view($view_id, $view_display_id);
    }

    $form['text_bottom'] = [];
    $text_bottom = $myoneclick_config->get('form.text_bottom');
    if (!empty($text_bottom)) {
      $text_bottom = $this->token->replace($text_bottom, ['commerce_product' => $commerce_product]);
      $form['text_bottom'] = [
        '#type' => 'markup',
        '#markup' => '<div class="text-bottom-wrapper">' . $text_bottom . '</div>',
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $myoneclick_config->get('form.submit_label'),
    ];

    // ajaxSubmit
    $form['#id'] = Html::getId($this->getFormId());
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmitCallback',
      'wrapper' => $form['#id'],
      'event' => 'click',
      'progress' => [
        'type' => 'throbber',
      ],
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
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      return $form;
    }

    $response = new AjaxResponse();
    $url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $form_state->getValue('cart_id'),
      'step' => 'complete'
    ]);
    $command = new RedirectCommand($url->toString());
    $response->addCommand($command);
    return $response;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $product_id = $form_state->getValue('product_id');
    $quantity = $form_state->getValue('quantity');
    $name = $form_state->getValue('name');
    $phone = $form_state->getValue('phone');
    $mail = $form_state->getValue('mail');
    $city = $form_state->getValue('city');

    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = Product::load($product_id);
    /** @var \Drupal\commerce_product\Entity\ProductVariation[] $product_variations */
    $product_variations = $product->getVariations();
    $product_variation = $product_variations[0];
    $order_item = $this->cartManager->createOrderItem($product_variation, $quantity);
    $order_type_id = $this->orderTypeResolver->resolve($order_item);
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $this->currentStore->getStore();
    $cart = $this->cartProvider->getCart($order_type_id, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type_id, $store);
    }
    $form_state->setValue('cart_id', $cart->id());
    $this->cartManager->addOrderItem($cart, $order_item);

    $this->saveOrderFields($cart, $mail, $name, $phone, $city);
    $this->redirectToComplete($cart->id(), $form_state);
    $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS); // ?????????????? ?????????????????? ??????????????????
  }


}
