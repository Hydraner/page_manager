<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\SelectionConditionFormBase.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Provides a base form for editing and adding an selection condition.
 */
abstract class SelectionConditionFormBase extends ConditionFormBase {

  /**
   * The page variant.
   *
   * @var \Drupal\block_page\Plugin\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $page_variant_id = NULL, $selection_condition_id = NULL) {
    $this->pageVariant = $block_page->getPageVariant($page_variant_id);
    return parent::buildForm($form, $form_state, $block_page, $selection_condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $form_state['redirect_route'] = new Url('block_page.page_variant_edit', array(
      'block_page' => $this->blockPage->id(),
      'page_variant_id' => $this->pageVariant->id(),
    ));
  }

}
