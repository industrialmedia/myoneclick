<?php

namespace Drupal\myoneclick\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_product\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\ConfigFactoryInterface;


class MyoneclickController extends ControllerBase {


  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;


  /**
   * Constructs
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Token $token, ConfigFactoryInterface $config_factory) {
    $this->token = $token;
    $this->configFactory = $config_factory;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Utility\Token $token */
    $token = $container->get('token');
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');

    return new static(
      $token,
      $config_factory
    );
  }


  public function formTitleCallback(Product $commerce_product) {
    $settings_config = $this->configFactory->get('myoneclick.settings');
    return $this->token->replace($settings_config->get('popup.title'), ['commerce_product' => $commerce_product]);
  }


}