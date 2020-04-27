<?php

namespace Drupal\myoneclick\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\Order;


class MyoneclickFormCheckoutPage extends MyoneclickFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myoneclick_checkout_page_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $commerce_order = NULL) {
    $myoneclick_config = $this->configFactory->get('myoneclick.settings');

    $form['order_id'] = array(
      '#type' => 'value',
      '#value' => $commerce_order->id(),
    );

    $form['text_top'] = [];
    $text_top = $myoneclick_config->get('form.text_top');
    if (!empty($text_top)) {
      $text_top = $this->token->replace($text_top, ['commerce_order' => $commerce_order]);
      $form['text_top'] = [
        '#type' => 'markup',
        '#markup' =>'<div class="text-top-wrapper">' . $text_top . '</div>',
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
      $text_bottom = $this->token->replace($text_bottom, ['commerce_order' => $commerce_order]);
      $form['text_bottom'] = [
        '#type' => 'markup',
        '#markup' =>'<div class="text-bottom-wrapper">' . $text_bottom . '</div>',
      ];
    }

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $order_id = $form_state->getValue('order_id');
    $name = $form_state->getValue('name');
    $phone = $form_state->getValue('phone');
    $mail = $form_state->getValue('mail');
    $city = $form_state->getValue('city');
    $cart = Order::load($order_id);
    $this->saveOrderFields($cart, $mail, $name, $phone, $city);
    $this->redirectToComplete($order_id, $form_state);
  }



}
