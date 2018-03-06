<?php

namespace Drupal\accountkit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Account Kit settings.
 */
class AccountKitSettingsForm extends ConfigFormBase {

  /**
   * The path validator to redirect the user.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * AccountKitSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory for config forms.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator to check the redirect path.
   */
  public function __construct(ConfigFactoryInterface $configFactory, PathValidatorInterface $pathValidator) {
    parent::__construct($configFactory);
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'accountkit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'accountkit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $accountkit_config = $this->config('accountkit.settings');

    $form['fb_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Facebook App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Facebook App at <a href="@facebook-dev">@facebook-dev</a>', array('@facebook-dev' => 'https://developers.facebook.com/apps')),
    );

    $form['fb_settings']['app_id'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $accountkit_config->get('app_id'),
      '#description' => $this->t('Copy the App ID of your Facebook App here. This value can be found from your App Dashboard.'),
    );

    $form['fb_settings']['app_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Account Kit App Secret'),
      '#default_value' => $accountkit_config->get('app_secret'),
      '#description' => $this->t('Copy the Account Kit App Secret of your Facebook App here. This value can be found under Products > Account Kit > Dashboard.'),
    );

    $form['fb_settings']['api_version'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Account Kit API Version'),
      '#default_value' => $accountkit_config->get('api_version'),
      '#description' => $this->t('Copy the Account Kit API Version of your Facebook App here. It may be different than the facebook graph api version.'),
    );

    $form['module_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Account Kit configurations'),
      '#open' => TRUE,
      '#description' => $this->t('These settings allow you to configure how Account Kit module behaves on your Drupal site'),
    );

    $form['module_settings']['redirect_url'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Redirect Path'),
      '#description' => $this->t('Drupal path where the user should be redirected after successful login. Use <em>&lt;front&gt;</em> to redirect user to your front page.'),
      '#default_value' => $accountkit_config->get('redirect_url'),
    );

    $form['module_settings']['enable_email_auth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email authentication'),
      '#description' => $this->t('Enable authentication via Email on Account Kit.'),
      '#default_value' => $accountkit_config->get('enable_email_auth'),
    );

    $form['module_settings']['enable_phone_auth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable phone authentication'),
      '#description' => $this->t('Enable authentication via Phone on Account Kit.'),
      '#default_value' => $accountkit_config->get('enable_phone_auth'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^v[1-9]\.[0-9]{1,2}$/', $form_state->getValue('api_version'))) {
      $form_state->setErrorByName('api_version', $this->t('Invalid API version. The syntax for API version is for example <em>v2.8</em>'));
    }
    if (!$this->pathValidator->isValid($form_state->getValue('redirect_url'))) {
      $form_state->setErrorByName('redirect_url', $this->t('The redirect url must be valid on the site.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('accountkit.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->set('api_version', $values['api_version'])
      ->set('redirect_url', $values['redirect_url'])
      ->set('enable_email_auth', $values['enable_email_auth'])
      ->set('enable_phone_auth', $values['enable_phone_auth'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
