<?php

/**
 * @file
 * Contains \Drupal\page_manager\Controller\PageManagerController.
 */

namespace Drupal\page_manager\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\page_manager\PageInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route controllers for Page Manager.
 */
class PageManagerController extends ControllerBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * The variant manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $variantManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Constructs a new DisplayVariantEditForm.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $variant_manager
   *   The variant manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   */
  public function __construct(BlockManagerInterface $block_manager, PluginManagerInterface $condition_manager, PluginManagerInterface $variant_manager, ContextHandlerInterface $context_handler) {
    $this->blockManager = $block_manager;
    $this->conditionManager = $condition_manager;
    $this->variantManager = $variant_manager;
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('plugin.manager.display_variant'),
      $container->get('context.handler')
    );
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return string
   *   The title for the page edit form.
   */
  public function editPageTitle(PageInterface $page) {
    return $this->t('Edit %label page', array('%label' => $page->label()));
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   *
   * @return string
   *   The title for the display variant edit form.
   */
  public function editDisplayVariantTitle(PageInterface $page, $display_variant_id) {
    $display_variant = $page->getVariant($display_variant_id);
    return $this->t('Edit %label display variant', array('%label' => $display_variant->label()));
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $condition_id
   *   The access condition ID.
   *
   * @return string
   *   The title for the access condition edit form.
   */
  public function editAccessConditionTitle(PageInterface $page, $condition_id) {
    $access_condition = $page->getAccessCondition($condition_id);
    return $this->t('Edit %label access condition', array('%label' => $access_condition->getPluginDefinition()['label']));
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   * @param string $condition_id
   *   The selection condition ID.
   *
   * @return string
   *   The title for the selection condition edit form.
   */
  public function editSelectionConditionTitle(PageInterface $page, $display_variant_id, $condition_id) {
    $display_variant = $page->getVariant($display_variant_id);
    $selection_condition = $display_variant->getSelectionCondition($condition_id);
    return $this->t('Edit %label selection condition', array('%label' => $selection_condition->getPluginDefinition()['label']));
  }

  /**
   * Enables or disables a Page.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the pages list page.
   */
  public function performPageOperation(PageInterface $page, $op) {
    $page->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label page has been enabled.', array('%label' => $page->label())));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label page has been disabled.', array('%label' => $page->label())));
    }

    return $this->redirect('page_manager.page_list');
  }

  /**
   * Presents a list of display variants to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return array
   *   The display variant selection page.
   */
  public function selectDisplayVariant(PageInterface $page) {
    $build = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    foreach ($this->variantManager->getDefinitions() as $display_variant_id => $display_variant) {
      $build['#links'][$display_variant_id] = array(
        'title' => $display_variant['admin_label'],
        'route_name' => 'page_manager.display_variant_add',
        'route_parameters' => array(
          'page' => $page->id(),
          'display_variant_id' => $display_variant_id,
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
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return array
   *   The access condition selection page.
   */
  public function selectAccessCondition(PageInterface $page) {
    $build = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    $available_plugins = $this->conditionManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $access_id => $access_condition) {
      $build['#links'][$access_id] = array(
        'title' => $access_condition['label'],
        'route_name' => 'page_manager.access_condition_add',
        'route_parameters' => array(
          'page' => $page->id(),
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
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   *
   * @return array
   *   The selection condition selection page.
   */
  public function selectSelectionCondition(PageInterface $page, $display_variant_id) {
    $build = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    $available_plugins = $this->conditionManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $selection_id => $selection_condition) {
      $build['#links'][$selection_id] = array(
        'title' => $selection_condition['label'],
        'route_name' => 'page_manager.selection_condition_add',
        'route_parameters' => array(
          'page' => $page->id(),
          'display_variant_id' => $display_variant_id,
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
   * Presents a list of blocks to add to the display variant.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   *
   * @return array
   *   The block selection page.
   */
  public function selectBlock(Request $request, PageInterface $page, $display_variant_id) {
    // Add a section containing the available blocks to be added to the variant.
    $build = array(
      '#type' => 'container',
      '#attached' => array(
        'library' => array(
          'core/drupal.ajax',
        ),
      ),
    );
    $available_plugins = $this->blockManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $plugin_id => $plugin_definition) {
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
        'route_name' => 'page_manager.display_variant_add_block',
        'route_parameters' => array(
          'page' => $page->id(),
          'display_variant_id' => $display_variant_id,
          'block_id' => $plugin_id,
          'region' => $request->query->get('region'),
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
