<?php

namespace Drupal\accountkit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\accountkit\AccountKitManager;

/**
 * Class AccountKitLoginForm
 *
 * @package Drupal\accountkit\Form
 */
class AccountKitLoginForm extends FormBase {

  /**
   * Accountkit manager to perform the login.
   *
   * @var \Drupal\accountkit\AccountKitManager
   */
  protected $accountkitManager;

  /**
   * The path validator to redirect the user.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * AccountKitLoginForm constructor.
   *
   * @param \Drupal\accountkit\AccountKitManager $accountkitManager
   *   The accountkit manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator to get the redirect url from.
   */
  public function __construct(
    AccountKitManager $accountkitManager,
    PathValidatorInterface $pathValidator
  ) {
    $this->accountkitManager = $accountkitManager;
    $this->pathValidator = $pathValidator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('accountkit.accountkit_manager'),
      $container->get('path.validator')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'accountkit_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('accountkit.settings');

    $enable_email = $config->get('enable_email_auth');
    $enable_phone = $config->get('enable_phone_auth');
    if (!$enable_email && ! $enable_phone) {
      // Both options are disabled, the form needs to be empty.
      return $form;
    }


    if ($enable_email) {
      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#description' => $this->t('Please input your email address.'),
      ];
      $form['email_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Email Login'),
        '#attributes' => ['id' => 'email-login-submit'],
      ];
    }

    if ($enable_phone) {
      $form['country_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Country code'),
        '#description' => $this->t('Please input your country code.'),
        '#maxlength' => 64,
        '#size' => 5,
      ];
      $form['phone_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone number'),
        '#description' => $this->t('Please input your phone number.'),
        '#maxlength' => 64,
        '#size' => 64,
      ];
      $form['sms_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('SMS Login'),
        '#attributes' => ['id' => 'sms-login-submit'],
      ];
    }

    $form['code'] = [
      '#type' => 'hidden',
      '#title' => t('Code'),
      '#description' => t('Hidden code field.'),
      '#attributes' => ['id' => 'code'],
    ];

    $form['#attached'] = [
      'library' => [
        'accountkit/sdk',
        'accountkit/client',
      ],
      'drupalSettings' => [
        'accountkit' => [
          'client' => [
            'app_id' => $config->get('app_id'),
            'api_version' => $config->get('api_version'),
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->accountkitManager->userLoginFromCode($form_state->getValue('code'))){
      $url = $this->pathValidator->getUrlIfValid($this->config('accountkit.settings')->get('redirect_url'));
      if ($url) {
        $form_state->setRedirectUrl($url);
      }
      else {
        $form_state->setRedirectUrl(Url::fromRoute('user.page'));
      }
    }
    else {
      $this->logger('accountkit')->error('Accountkit form submission but no login.');
      $this->messenger()->addError($this->t('Something went wrong with the accountkit login.'));
    }
  }

}
