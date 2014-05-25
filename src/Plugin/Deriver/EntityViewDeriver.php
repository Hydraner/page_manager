<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Derivative\EntityViewDeriver.
 */

namespace Drupal\page_manager\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity view block definitions for each entity type.
 */
class EntityViewDeriver extends DerivativeBase implements ContainerDerivativeInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs new EntityViewDeriver.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['admin_label'] = $this->t('Entity view (@label)', array('@label' => $entity_type->getLabel()));
      $this->derivatives[$entity_type_id]['context'] = array(
        'entity' => array(
          'type' => 'entity:' . $entity_type_id,
          // @todo Remove once https://drupal.org/node/2272161 is in.
          'type' => 'entity',
          'constraints' => array(
            'EntityType' => $entity_type_id,
          )
        ),
      );
    }
    return $this->derivatives;
  }

}