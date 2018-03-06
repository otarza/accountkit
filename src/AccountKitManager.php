<?php

namespace Drupal\accountkit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\externalauth\ExternalAuthInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Contains all Account Kit related logic.
 */
class AccountKitManager {

  /**
   * @var ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  private $externalAuth;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $client;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * AccountKitManager constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The config factory to get the account kit config from.
   * @param ExternalAuthInterface $externalAuth
   *   The authentication service for external authentication methods.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client to make requests to facebook with.
   * @param LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger factory to get the logger for our module.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ExternalAuthInterface $externalAuth,
    ClientInterface $client,
    LoggerChannelFactoryInterface $loggerChannelFactory
  ) {
    $this->configFactory = $configFactory;
    $this->externalAuth = $externalAuth;
    $this->client = $client;
    $this->logger = $loggerChannelFactory->get('accountkit');
  }

  /**
   * Log a user in based on the account kit code, create it if necessary.
   *
   * @param string $code
   *   The account kit code.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user or null if there was a failure.
   */
  public function userLoginFromCode($code) {
    try {
      $data = $this->getUserInfo($code);
      $user_name = $data['id'];
      $account_data = [];
      if (!empty($data['email']['address'])) {
        $account_data['mail'] = $data['email']['address'];
      }

      return $this->externalAuth->loginRegister($user_name, 'accountkit', $account_data);
    }
    catch (AccountKitConnectionException $exception) {
      $data = $exception->getData();
      $error = "Accountkit error: " . $data['message']
        . " type: " . $data['type']
        . " code: " . $data['code']
        . " fbtrace_id:" . $data['fbtrace_id'];
      $this->logger->error($error);
    }
    catch (GuzzleException $exception) {
      $this->logger->error('Connection error: ' . $exception->getMessage());
    }
    catch (ExternalAuthRegisterException $exception) {
      $this->logger->error('Registration error: ' . $exception->getMessage());
    }

    return NULL;
  }

  /**
   * Get user information like email or phone.
   *
   * This code is copied from the developer documentation of account kit.
   *
   * @param string $code
   *   The account kit code.
   *
   * @return array
   *   Array containing user info.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   The connection exception.
   */
  protected function getUserInfo($code) {
    // This code is copied from the developer documentation of account kit.
    $access_token = $this->getAccessToken($code);
    $me_endpoint_url = 'https://graph.accountkit.com/' . $this->getConfig('api_version') . '/me?access_token=' . $access_token;
    return $this->curlit($me_endpoint_url);
  }

  /**
   * Get the Access token for a given code.
   *
   * This code is copied from the developer documentation of account kit.
   *
   * @param string $code
   *   The account kit code.
   *
   * @return string
   *   The access token.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   The connection exception.
   */
  protected function getAccessToken($code) {
    $app_id = $this->getConfig('app_id');
    $secret = $this->getConfig('app_secret');
    $version = $this->getConfig('api_version');

    // Exchange authorization code for access token
    $token_exchange_url = 'https://graph.accountkit.com/' . $version . '/access_token?' .
      'grant_type=authorization_code' .
      '&code=' . $code .
      "&access_token=AA|$app_id|$secret";

    $data = $this->curlit($token_exchange_url);
    return $data['access_token'];
  }

  /**
   * Get the account kit config.
   *
   * @param $key
   *   The config key.
   *
   * @return mixed
   *   the config.
   */
  protected function getConfig($key) {
    return $this->configFactory->get('accountkit.settings')->get($key);
  }

  /**
   * Make a curl request.
   *
   * @param string $url
   *   The url to curl
   *
   * @return array
   *   The result
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   The connection exception.
   */
  protected function curlit($url) {
    try {
      $response = $this->client->request('get', $url);
      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (ClientException $exception) {
      if ($exception->hasResponse()) {
        $response = $exception->getResponse();
        $data = json_decode($response->getBody()->getContents(), TRUE);
        throw new AccountKitConnectionException($exception, $data['error']);
      }
      throw $exception;
    }
  }

}
