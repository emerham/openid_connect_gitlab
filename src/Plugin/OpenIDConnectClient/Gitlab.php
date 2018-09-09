<?php

namespace Drupal\openid_connect_gitlab\Plugin\OpenIdConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Exception;

/**
 * Gitlab OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for Gitlab.
 *
 * @OpenIDConnectClient(
 *   id = "gitlab",
 *   label = @Translation("Gitlab")
 * )
 */
class Gitlab extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['baseUrl'] = [
      '#title' => 'Your Gitlab Base URL',
      '#description' => $this->t('The Base URL of your Gitlab Installation'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['baseUrl'],
    ];
    $form['apiVersion'] = [
      '#title' => 'Gitlab User API Version',
      '#description' => $this->t('If you do not know what version your Gitlab use v4. More information can be found here https://docs.gitlab.com/ce/api/v3_to_v4.html'),
      '#type' => 'select',
      '#options' => [
        'v3' => 'v3',
        'v4' => 'v4',
      ],
      '#default_value' => $this->configuration['apiVersion'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function decodeIdToken($id_token) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize($scope = 'openid email') {
    return parent::authorize('openid read_user');
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo($access_token) {
    $request_options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
        'Accept' => 'application/json',
      ],
    ];
    $endpoints = $this->getEndpoints();

    $client = $this->httpClient;

    try {
      $response = $client->get($endpoints['userinfo'], $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);
      $response_data['sub'] = $response_data['id'];
      $response_data['picture'] = $response_data['avatar_url'];
      return $response_data;

    }
    catch (Exception $e) {
      $variables = [
        '@message' => 'Could not retrieve user profile information',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    $user_info_url = $this->configuration['baseUrl'] . '/api/' . $this->configuration['apiVersion'] . '/user';
    return [
      'authorization' => $this->configuration['baseUrl'] . '/oauth/authorize',
      'token' => $this->configuration['baseUrl'] . '/oauth/token',
      'userinfo' => $user_info_url,
    ];
  }

}
