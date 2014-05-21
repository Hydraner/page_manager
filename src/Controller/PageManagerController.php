<?php

/**
 * @file
 * Contains \Drupal\page_manager\Controller\PageManagerController.
 */

namespace Drupal\page_manager\Controller;

use Drupal\block\BlockManagerInterface;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\ContextHandler;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route controllers for Page Manager.
 */
class PageManagerController extends ControllerBase {

  /**
   * The block manager.
   *
   * @var \Drupal\block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * The page variant manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pageVariantManager;

  /**
   * The context handler.
   *
   * @var \Drupal\page_manager\ContextHandler
   */
  protected $contextHandler;

  /**
   * Constructs a new PageVariantEditForm.
   *
   * @param \Drupal\block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $page_variant_manager
   *   The page variant manager.
   * @param \Drupal\page_manager\ContextHandler $context_handler
   *   The context handler.
   */
  public function __construct(BlockManagerInterface $block_manager, PluginManagerInterface $condition_manager, PluginManagerInterface $page_variant_manager, ContextHandler $context_handler) {
    $this->blockManager = $block_manager;
    $this->conditionManager = $condition_manager;
    $this->pageVariantManager = $page_variant_manager;
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('plugin.manager.page_variant'),
      $container->get('context.handler')
    );
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   *
   * @return string
   *   The title for the page edit form.
   */
  public function editPageTitle(PageInterface $page_manager) {
    return $this->t('Edit %label page', array('%label' => $page_manager->label()));
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   * @param string $page_variant_id
   *   The page variant ID.
   *
   * @return string
   *   The title for the page variant edit form.
   */
  public function editPageVariantTitle(PageInterface $page_manager, $page_variant_id) {
    $page_variant = $page_manager->getPageVariant($page_variant_id);
    return $this->t('Edit %label page variant', array('%label' => $page_variant->label()));
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   * @param string $condition_id
   *   The access condition ID.
   *
   * @return string
   *   The title for the access condition edit form.
   */
  public function editAccessConditionTitle(PageInterface $page_manager, $condition_id) {
    $access_condition = $page_manager->getAccessCondition($condition_id);
    return $this->t('Edit %label access condition', array('%label' => $access_condition->getPluginDefinition()['label']));
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   * @param string $page_variant_id
   *   The page variant ID.
   * @param string $condition_id
   *   The selection condition ID.
   *
   * @return string
   *   The title for the selection condition edit form.
   */
  public function editSelectionConditionTitle(PageInterface $page_manager, $page_variant_id, $condition_id) {
    $page_variant = $page_manager->getPageVariant($page_variant_id);
    $selection_condition = $page_variant->getSelectionCondition($condition_id);
    return $this->t('Edit %label selection condition', array('%label' => $selection_condition->getPluginDefinition()['label']));
  }

  /**
   * Presents a list of page variants to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   *
   * @return array
   *   The page variant selection page.
   */
  public function selectPageVariant(PageInterface $page_manager) {
    $build = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    foreach ($this->pageVariantManager->getDefinitions() as $page_variant_id => $page_variant) {
      $build['#links'][$page_variant_id] = array(
        'title' => $page_variant['admin_label'],
        'route_name' => 'page_manager.page_variant_add',
        'route_parameters' => array(
          'page' => $page_manager->id(),
          'page_variant_id' => $page_variant_id,
        ),
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(array(
            'width' => 'auto',
          )),
        ),
      );
    }
    return $build;
  }

  /**
   * Presents a list of access conditions to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   *
   * @return array
   *   The access condition selection page.
   */
  public function selectAccessCondition(PageInterface $page_manager) {
    $build = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    $available_plugins = $this->contextHandler->getAvailablePlugins($page_manager->getContexts(), $this->conditionManager);
    foreach ($available_plugins as $access_id => $access_condition) {
      $build['#links'][$access_id] = array(
        'title' => $access_condition['label'],
        'route_name' => 'page_manager.access_condition_add',
        'route_parameters' => array(
          'page' => $page_manager->id(),
          'condition_id' => $access_id,
        ),
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(array(
            'width' => 'auto',
          )),
        ),
      );
    }
    return $build;
  }

  /**
   * Presents a list of selection conditions to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   * @param string $page_variant_id
   *   The page variant ID.
   *
   * @return array
   *   The selection condition selection page.
   */
  public function selectSelectionCondition(PageInterface $page_manager, $page_variant_id) {
    $build = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    $available_plugins = $this->contextHandler->getAvailablePlugins($page_manager->getContexts(), $this->conditionManager);
    foreach ($available_plugins as $selection_id => $selection_condition) {
      $build['#links'][$selection_id] = array(
        'title' => $selection_condition['label'],
        'route_name' => 'page_manager.selection_condition_add',
        'route_parameters' => array(
          'page' => $page_manager->id(),
          'page_variant_id' => $page_variant_id,
          'condition_id' => $selection_id,
        ),
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(array(
            'width' => 'auto',
          )),
        ),
      );
    }
    return $build;
  }

  /**
   * Presents a list of blocks to add to the page variant.
   *
   * @param \Drupal\page_manager\PageInterface $page_manager
   *   The page entity.
   * @param string $page_variant_id
   *   The page variant ID.
   *
   * @return array
   *   The block selection page.
   */
  public function selectBlock(PageInterface $page_manager, $page_variant_id) {
    // Add a section containing the available blocks to be added to the variant.
    $build = array(
      '#type' => 'container',
      '#attached' => array(
        'library' => array(
          'core/drupal.ajax',
        ),
      ),
    );
    foreach ($this->blockManager->getSortedDefinitions() as $plugin_id => $plugin_definition) {
      // Make a section for each region.
      $category = String::checkPlain($plugin_definition['category']);
      $category_key = 'category-' . $category;
      if (!isset($build[$category_key])) {
        $build[$category_key] = array(
          '#type' => 'fieldgroup',
          '#title' => $category,
          'content' => array(
            '#theme' => 'links',
          ),
        );
      }
      // Add a link for each available block within each region.
      $build[$category_key]['content']['#links'][$plugin_id] = array(
        'title' => $plugin_definition['admin_label'],
        'route_name' => 'page_manager.page_variant_add_block',
        'route_parameters' => array(
          'page' => $page_manager->id(),
          'page_variant_id' => $page_variant_id,
          'block_id' => $plugin_id,
        ),
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(array(
            'width' => 'auto',
          )),
        ),
      );
    }
    return $build;
  }

}