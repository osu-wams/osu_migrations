<?php

namespace Drupal\paragraphs_to_layout_builder\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\paragraphs_to_layout_builder\LayoutBase;
use Drupal\paragraphs_to_layout_builder\LayoutMigrationItem;
use Drupal\paragraphs_to_layout_builder\LayoutMigrationMissingBlockException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Paragraphs Layout process plugin.
 *
 * @code
 * layout_builder__layout:
 *   plugin: layout_builder_layout
 *   source_field: field_paragraphs
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_layout"
 * )
 */
class ParagraphsLayout extends LayoutBase {

  /**
   * Transform paragraph source values into a Layout Builder sections.
   *
   * @param mixed $value
   *   The value to be transformed.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process. Normally, just transforming the value
   *   is adequate but very rarely you might need to change two columns at the
   *   same time or something like that.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return \Drupal\layout_builder\Section
   *   A Layout Builder Section object populated with Section Components.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   */
  public function transform($value,
    MigrateExecutableInterface $migrate_executable,
    Row $row,
    $destination_property
  ) {
    $sourceField = $this->configuration['source_field'];
    if (!isset($sourceField)) {
      throw new MigrateException('Missing source_field for paragraph layout process plugin.');
    }

    $values = $row->getSourceProperty($sourceField);
    $map = $row->getSource()['constants']['map'];
    $sections = [];

    if (is_array($values)) {
      foreach ($values as $delta => $item) {
        try {
          $type = $this->getParagraphType($item['value']);
          $sectionType = $this->getSectionType($type);
          $section = $this->createSection($sectionType, []);

          // map migration IDs to their layout builder region
          $migration_ids = [];
          if ($type == "paragraph_2_col") {
            $migration_ids[$map['paragraph_2_col_left']] = "blb_region_col_1";
            $migration_ids[$map['paragraph_2_col_right']] = "blb_region_col_2";
          }else if ($type == "paragraph_3_col") {
            $migration_ids[$map['paragraph_3_col_left']] = "blb_region_col_1";
            $migration_ids[$map['paragraph_3_col_center']] = "blb_region_col_2";
            $migration_ids[$map['paragraph_3_col_right']] = "blb_region_col_3";
          } else {
            $migration_ids[$map[$type]] = "blb_region_col_1";
          }
          // iterate through migration_ids creating components for each block and attaching to section
          foreach ($migration_ids as $migration_id => $row) {
            $migrationItem = new LayoutMigrationItem($type, $item['value'], $delta, $migration_id);
            $components = $this->createComponent($migrationItem, $section, $row);

            // limitations on menu migrations means we don't know what section type to use until now
            if ($components[0]->get('configuration')['id'] == 'inline_block:osu_menu_bar_item') {
              $section = $this->createSection('bootstrap_layout_builder:blb_col_' . sizeof($components), []);
            }

            foreach ($components as $component) {
              $section->appendComponent($component);
            }
          }

          $sections[] = $section;
        } catch (LayoutMigrationMissingBlockException $e) {
          $this->handleMissingBlockException($migrate_executable, $e);
          continue;
        }
      }
    }

    return $sections;
  }

  /**
   * Gets the type of paragraph given a paragraph id.
   *
   * Uses basic static caching since this may be called multiple times for the
   * same paragraphs.
   *
   * @param string $id
   *   The paragraph id.
   *
   * @return string
   *   The paragraph bundle.
   */
  public function getParagraphType($id) {
    $types = &drupal_static(__FUNCTION__);
    if (!isset($types[$id])) {
      $query = $this->migrateDb->select('paragraphs_item', 'p');
      $query->fields('p', ['bundle']);
      $query->condition('p.item_id', $id, '=');
      $types[$id] = $query->execute()->fetchField();
    }
    return $types[$id];
  }
}
