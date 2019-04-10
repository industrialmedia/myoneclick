<?php

namespace Drupal\myoneclick\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;

class MyoneclickSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $config_factory,
      $entity_type_manager
    );
  }


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
    $form['form']['city_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City label'),
      '#default_value' => $settings_config->get('form.city_label'),
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


    // Tab - save
    $form['save'] = [
      '#type' => 'details',
      '#title' => 'Сохранение заказа',
      '#group' => 'settings',
      '#tree' => TRUE,
    ];
    $options = [];
    /** @var \Drupal\commerce_shipping\ShippingMethodStorageInterface $shipping_method_storage */
    $shipping_method_storage = $this->entityTypeManager->getStorage('commerce_shipping_method');
    $shipping_methods = $shipping_method_storage->loadMultiple();
    /** @var \Drupal\commerce_shipping\Entity\ShippingMethod $shipping_method */
    foreach ($shipping_methods as $shipping_method) {
      if ($shipping_method->isEnabled()) {
        $options[$shipping_method->id()] = $shipping_method->getName();
      }
    }
    $form['save']['shipping_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Shipping method'),
      '#options' => $options,
      '#default_value' => $settings_config->get('save.shipping_method'),
      '#required' => TRUE,
      '#description' => 'Для модуля commerce_shipping способ доставки есть обязательным, значение по умолчанию',
    ];
    $form['save']['fields'] = [
      '#type' => 'fieldset',
      '#description' => '
        В формате <strong>commerce_order|FIELDNAME</strong> или <strong>profile|FIELDNAME</strong> <br />
        Примеры commerce_order|field_order_fio, profile|field_customer_city
      ',
    ];
    $form['save']['fields']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Save field: Name'),
      '#default_value' => $settings_config->get('save.fields.name'),
      '#element_validate' => [[get_class($this), 'saveFieldValidate']],
    ];
    $form['save']['fields']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Save field: Phone'),
      '#default_value' => $settings_config->get('save.fields.phone'),
      '#element_validate' => [[get_class($this), 'saveFieldValidate']],
    ];
    $form['save']['fields']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Save field: City'),
      '#default_value' => $settings_config->get('save.fields.city'),
      '#element_validate' => [[get_class($this), 'saveFieldValidate']],
    ];
    
    return parent::buildForm($form, $form_state);
  }


  public static function saveFieldValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#value'])) {
      $message = 'Не верный формат поля "' . $element['#title'] . '"';
      $save_field = $element['#value'];
      $save_field = explode('|', $save_field);
      if (count($save_field) != 2) {
        $form_state->setError($element, $message);
        return;
      }
      if (!in_array($save_field[0], ['commerce_order', 'profile'])) {
        $form_state->setError($element, $message);
        return;
      }
      $save_field_1 = trim($save_field[1]);
      if (empty($save_field_1)) {
        $form_state->setError($element, $message);
        return;
      }
      $field_config = FieldStorageConfig::loadByName($save_field[0], $save_field[1]);
      if (empty($field_config)) {
        $form_state->setError($element, $message);
        return;
      }
    }
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
    $settings_config
      ->set('load_popup.addtocart', $form_state->getValue('load_popup')['addtocart'])
      ->set('load_popup.addtocart_view_modes', array_filter($form_state->getValue('load_popup')['addtocart_view_modes']));
    $settings_config
      ->set('popup.dialog_options', $form_state->getValue('popup')['dialog_options'])
      ->set('popup.button_name', $form_state->getValue('popup')['button_name'])
      ->set('popup.title', $form_state->getValue('popup')['title']);
    $settings_config
      ->set('form.name_label', $form_state->getValue('form')['name_label'])
      ->set('form.phone_label', $form_state->getValue('form')['phone_label'])
      ->set('form.mail_label', $form_state->getValue('form')['mail_label'])
      ->set('form.city_label', $form_state->getValue('form')['city_label'])
      ->set('form.submit_label', $form_state->getValue('form')['submit_label'])
      ->save();
    $settings_config
      ->set('save.shipping_method', $form_state->getValue('save')['shipping_method'])
      ->set('save.fields.name', $form_state->getValue('save')['fields']['name'])
      ->set('save.fields.phone', $form_state->getValue('save')['fields']['phone'])
      ->set('save.fields.city', $form_state->getValue('save')['fields']['city'])
      ->save();
    parent::submitForm($form, $form_state);
  }


}
