<?php

namespace Drupal\osu_migrations_volcano\Plugin\migrate\process;

use Drupal\paragraphs_to_layout_builder\Plugin\migrate\process\ParagraphsLayout;

/**
 * Paragraphs Layout process plugin for the Volcano site.
 *
 * @code
 * layout_builder__layout:
 *   plugin: volcano_paragraphs_layout
 *   source_field: field_paragraphs
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "volcano_paragraphs_layout"
 * )
 */
class VolcanoParagraphsLayout extends ParagraphsLayout {

}
