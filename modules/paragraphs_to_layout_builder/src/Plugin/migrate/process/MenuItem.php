<?php

namespace Drupal\paragraphs_to_layout_builder\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\block_content\Entity\BlockContent;
use Drupal\paragraphs_to_layout_builder\LayoutBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Custom plugin for handling paragraph menu items from d7
 * 
 * @MigrateProcessPlugin(
 *   id = "menu_item",
 *   handle_multiples = TRUE
 * )
 */
class MenuItem extends LayoutBase {

   /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // get entity ids for link data
    $entity_ids = [];
    foreach ($value as $entity_id) {
      $entity_ids[] = $entity_id['value'];
    }

    // query migrateDb for link and icon data
    $query = $this->migrateDb->select('field_data_field_p_menu_link', 'p');
    $query->fields('p', ['field_p_menu_link_title', 'field_p_menu_link_url']);
    $query->leftJoin('field_data_field_p_menu_icon', 'i', 'p.entity_id = i.entity_id');
    $query->fields('i', ['field_p_menu_icon_value']);
    $query->condition('p.entity_id', $entity_ids, 'IN');
    $query->orderBy('p.entity_id');
    $results = $query->execute();

    // use query results to build menu bar item blocks, save block ids for later use
    $block_ids = [];
    foreach ($results as $result) {
      // check for valid urls. Make changes as necessary
      $url = $result->field_p_menu_link_url;
      if (UrlHelper::isValid($url)) {
        if (!str_starts_with($url, 'http')) {
          if (str_starts_with($url, '/')) {
            $url = 'internal:' . $url;
          } else {
            $url = 'internal:/' . $url;
          }
        }
      } else {
        // clean out invalid URLs
        $url = '';
      }
      $block = BlockContent::create([
        'info' => 'Migrated d7 paragraph paragraph_menu',
        'type' => 'osu_menu_bar_item',
        'langcode' => 'en',
        'field_osu_menu_bar_item_link' => [
          'uri' => $url,
          'title' => $result->field_p_menu_link_title,
        ],
        'field_osu_menu_bar_item_icon' => $result->field_p_menu_icon_value,
        'reusable' => 0,
      ]);
      $block->save();
      $block_ids[] = $block->id();
    }

    return implode(',', $block_ids);
  }
}
