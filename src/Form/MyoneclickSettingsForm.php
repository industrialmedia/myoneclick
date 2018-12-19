<?php

namespace Drupal\myoneclick\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

class MyoneclickSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myoneclick_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'myoneclick.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings_config = $this->config('myoneclick.settings');
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Settings'),
    ];

    // Tab - load_popup
    $form['load_popup'] = [
      '#type' => 'details',
      '#title' => 'Вызов попап-формы',
      '#group' => 'settings',
      '#tree' => TRUE,
    ];
    $form['load_popup']['addtocart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add button "Buy one click" to add to cart form'),
      '#default_value' => $settings_config->get('load_popup.addtocart'),
      '#description' => $this->t('If the item is in stock and the price is not zero, add button "Buy one click" to add to cart form.'),
    ];

    /* @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $view_modes = $entity_display_repository->getViewModes('commerce_product');
    $options = [];
    if ($view_modes) {
      foreach ($view_modes as $key => $view_mode) {
        $options[$key] = $view_mode['label'];
      }
    }
    $options['full'] = t('Full');


    $form['load_popup']['addtocart_view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Add to cart form for view modes'),
      '#default_value' => !empty($settings_config->get('load_popup.addtocart_view_modes')) ? $settings_config->get('load_popup.addtocart_view_modes') : [],
      '#options' => $options,
      '#states' => [
        'visible' => [
          ':input[name="load_popup[addtocart]"]' => ['checked' => TRUE],
        ],
      ],
    ];


    // Tab - form
    $form['form'] = [
      '#type' => 'details',
      '#title' => 'Форма',
      '#group' => 'settings',
      '#tree' => TRUE,
    ];
    $form['form']['name_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name label'),
      '#default_value' => $settings_config->get('form.name_label'),
    ];
    $form['form']['phone_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone label'),
      '#default_value' => $settings_config->get('form.phone_label'),
    ];
    $form['form']['mail_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mail label'),
      '#default_value' => $settings_config->get('form.mail_label'),
    ];
    $form['form']['submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit label'),
      '#default_value' => $settings_config->get('form.submit_label'),
    ];
    // Tab - popup
    $form['popup'] = [
      '#type' => 'details',
      '#title' => 'Попап',
      '#group' => 'settings',
      '#tree' => TRUE,
    ];
    $form['popup']['dialog_options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog options'),
      '#default_value' => $settings_config->get('popup.dialog_options'),
      '#size' => 120,
    ];
    $form['popup']['button_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button name'),
      '#default_value' => $settings_config->get('popup.button_name'),
    ];
    $form['popup']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $settings_config->get('popup.title'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => ['commerce_product'],
    ];
    // Add the token tree UI.
    $form['popup']['token_tree'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('commerce_product'),
      '#show_restricted' => TRUE,
      '#show_nested' => FALSE,
      '#weight' => 90,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $popup = $form_state->getValue('popup');
    $dialog_options = $popup['dialog_options'];
    $dialog_options = Json::decode($dialog_options);
    if (!is_array($dialog_options)) {
      $form_state->setErrorByName('popup][dialog_options', 'Dialog options: Не верный Json формат');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings_config = $this->config('myoneclick.settings');
    $settings_config->set('load_popup.addtocart', $form_state->getValue('load_popup')['addtocart'])
      ->set('load_popup.addtocart_view_modes', array_filter($form_state->getValue('load_popup')['addtocart_view_modes']));
    $settings_config->set('popup.dialog_options', $form_state->getValue('popup')['dialog_options'])
      ->set('popup.button_name', $form_state->getValue('popup')['button_name'])
      ->set('popup.title', $form_state->getValue('popup')['title']);
    $settings_config->set('form.name_label', $form_state->getValue('form')['name_label'])
      ->set('form.phone_label', $form_state->getValue('form')['phone_label'])
      ->set('form.mail_label', $form_state->getValue('form')['mail_label'])
      ->set('form.submit_label', $form_state->getValue('form')['submit_label'])
      ->save();
    parent::submitForm($form, $form_state);
  }


}
