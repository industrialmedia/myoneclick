<?php

namespace Drupal\myoneclick\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;


/**
 * Provides the checkout page form one-click.
 */
class MyoneclickCheckoutPageController extends ControllerBase {


  /**
   * The config factory.
   *
   * @var \Drupal\Core\config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;



  /**
   * Constructs
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder) {
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('form_builder')
    );
  }


  /**
   * Builds and processes the page form one-click.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The render.
   */
  public function checkoutPage(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $form = $this->formBuilder->getForm('Drupal\myoneclick\Form\MyoneclickFormCheckoutPage', $order);
    // dump($form);
    return $form;
  }




}
