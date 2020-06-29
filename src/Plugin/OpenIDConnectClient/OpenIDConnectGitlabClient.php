<?php

namespace Drupal\openid_connect_gitlab\Plugin\OpenIdConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Exception;

/**
 * OpenIDConnectGitlabClient OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for OpenIDConnectGitlabClient.
 *
 * @OpenIDConnectClient(
 *   id = "gitlab",
 *   label = @Translation("GitLab")
 * )
 */
class OpenIDConnectGitlabClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['baseUrl'] = [
      '#title' => 'Your GitLab Base URL',
      '#description' => $this->t('The Base URL of your GitLab Installation'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['baseUrl'],
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
    return [
      'authorization' => $this->configuration['baseUrl'] . '/oauth/authorize',
      'token' => $this->configuration['baseUrl'] . '/oauth/token',
      'userinfo' => $this->configuration['baseUrl'] . '/api/v4/user',
    ];
  }

}
