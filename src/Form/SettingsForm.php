<?php

namespace Drupal\authenticated_frontpage\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom Frontpage for Authenticated users settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Create function for depdendency injection.
   */
  public static function create(ContainerInterface $container) {
    /* @var $entity_type_manager \Drupal\Core\Entity\EntityTypeManagerInterface */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'authenticated_frontpage';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['authenticated_frontpage.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $saved_page = $this->config('authenticated_frontpage.settings')->get('authenticated_frontpage.field_loggedin_frontpage');
    $form['field_loggedin_frontpage'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Authenticated User frontpage'),
      '#default_value' => $saved_page ? $node_storage->load($saved_page) : NULL,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('authenticated_frontpage.settings')
      ->set('authenticated_frontpage.field_loggedin_frontpage', $form_state->getValue('field_loggedin_frontpage'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
