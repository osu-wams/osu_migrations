<?php

namespace Drupal\paragraphs_to_layout_builder;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for layout process plugins.
 *
 * @package Drupal\paragraphs_to_layout_builder
 */
class LayoutBase extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;
  /**
   * The migration database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $migrateDb;

  /**
   * The immutable config factory service provided by Drupal core.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Block content Entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration,
    $pluginId,
    $pluginDefinition,
    UuidInterface $uuid,
    Connection $db,
    EntityTypeManagerInterface $entityTypeManager,
    configFactoryInterface $configFactory,
    MigrateLookupInterface $migrateLookup
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->db = $db;
    $this->migrateDb = Database::getConnection('default', 'migrate');
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->migrateLookup = $migrateLookup;
    $this->blockContentStorage = $entityTypeManager->getStorage('block_content');
    $this->uuid = $uuid;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition,
    MigrationInterface $migration = NULL
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('uuid'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('migrate.lookup')
    );
  }

  /**
   * Creates a Layout Builder section.
   *
   * @param string $layout
   *   The layout template id to use for this section.
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   An array of section components to add to the section.
   * @param array $settings
   *   An array of settings for the layout.
   *
   * @return \Drupal\layout_builder\Section
   *   The created section.
   */
  public function createSection($layout, array $components = [], array $settings = []) {
    // get default section settings and merge with passed in settings
    $settings = $settings + $this->getDefaultSectionSettings($layout);
    return new Section($layout, $settings, $components);
  }

  /**
   * Gets default section settings for the given $layout
   *
   * @param string $layout
   *   The layout template id to use for this section.
   *
   * @return array
   *   default section settings for type $layout
   */
  private static function getDefaultSectionSettings($layout) {
    switch ($layout) {
      case 'bootstrap_layout_builder:blb_col_1':
        return ['container' => 'container'];

      case 'bootstrap_layout_builder:blb_col_2':
        return [
          'breakpoints' => [
            'extra_wide_desktop' => 'blb_col_6_6',
            'desktop' => 'blb_col_6_6',
            'tablet' => 'blb_col_6_6',
            'mobile' => 'blb_col_6_6',
          ],
          'layout_regions_classes' => [
            'blb_region_col_1' => [
              'col-xxl-6',
              'col-lg-6',
              'col-md-6',
              'col-6'
            ],
            'blb_region_col_2' => [
              'col-xxl-6',
              'col-lg-6',
              'col-md-6',
              'col-6'
            ]
          ],
          'container' => 'container',
          'remove_gutters' => '1'
        ];

      case 'bootstrap_layout_builder:blb_col_3':
        return [
          'breakpoints' => [
            'extra_wide_desktop' => 'blb_col_4_4_4',
            'desktop' => 'blb_col_4_4_4',
            'tablet' => 'blb_col_4_4_4',
            'mobile' => 'blb_col_4_4_4',
          ],
          'layout_regions_classes' => [
            'blb_region_col_1' => [
              'col-xxl-4',
              'col-lg-4',
              'col-md-4',
              'col-4'
            ],
            'blb_region_col_2' => [
              'col-xxl-4',
              'col-lg-4',
              'col-md-4',
              'col-4'
            ],
            'blb_region_col_3' => [
              'col-xxl-4',
              'col-lg-4',
              'col-md-4',
              'col-4'
            ]
          ],
          'container' => 'container',
          'remove_gutters' => '1'
        ];

      default:
        return [];
    }
  }

  /**
   * Creates a component from a paragraph.
   *
   * @param \Drupal\paragraphs_to_layout_builder\LayoutMigrationItem $item
   *   A migration item instance.
   * @param \Drupal\layout_builder\Section $section
   *   The layout builder section this block will be applied to
   * @param string $row
   *   The region the component belongs within.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   A Layout Builder SectionComponent.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\paragraphs_to_layout_builder\LayoutMigrationMissingBlockException
   */
  public function createComponent(LayoutMigrationItem $item, $section, $row = 'blb_region_col_1') {
    $block_id = $this->lookupBlock($item->getMigrationId(), $item->getId());
    
    $query = $this->db->select('block_content_field_data', 'b')
      ->fields('b', ['type'])
      ->condition('b.id', $block_id, '=');
    $block_type = $query->execute()->fetchField();
    if (!$block_type) {
      throw new MigrateException(sprintf('An unknown error occurred trying to find the block type from migration item type %s with id %s.', $item->getType(), $item->getId()));
    }

    // get block and set any additional settings on component or section as needed
    $block_revision_id = $this->blockContentStorage->getLatestRevisionId($block_id);
    $block = \Drupal\block_content\Entity\BlockContent::load($block_id);

    if ($item->getMigrationId() == 'paragraph_menu__to__layout_builder') {
      return $this->handleMenuItems($block);
    }

    $additional = $this->getAdditionalBlockSettings($block, $row, $item);
    $this->setAdditionalSectionSettings($section, $block, $item);

    return [$this->createSectionComponent($block_type, $block_revision_id, $item->getDelta(), $row, $additional)];
  }

  /**
   * Additional blocks need to be queried for menu items
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block containing IDs of the menu item blocks
   * @param \Drupal\paragraphs_to_layout_builder\LayoutMigrationItem $item
   *   A migration item instance.
   *
   * @return array
   *   layout builder block settings array
   */
  protected function handleMenuItems($block) {
    $block_ids = explode(',', $block->body->value);
    $components = [];
    foreach ($block_ids as $index => $block_id) {
      $query = $this->db->select('block_content_field_data', 'b')
      ->fields('b', ['type'])
      ->condition('b.id', $block_id, '=');
      $block_type = $query->execute()->fetchField();
      $block_revision_id = $this->blockContentStorage->getLatestRevisionId($block_id);
      $block = \Drupal\block_content\Entity\BlockContent::load($block_id);
      $row = 'blb_region_col_' . ($index + 1);
      $components[] = $this->createSectionComponent($block_type, $block_revision_id, 0, $row, []);
    }
    return $components;
  }

  /**
   * Gets additional settings applied to the block based on field_styles
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block we need settings for
   * @param string $row
   *   The region the component belongs within.
   * @param \Drupal\paragraphs_to_layout_builder\LayoutMigrationItem $item
   *   A migration item instance.
   *
   * @return array
   *   layout builder block settings array
   */
  protected function getAdditionalBlockSettings($block, $row, $item) {
    $additional = [];
    if ($block->field_styles && (
         ($row == 'blb_region_col_1' && str_contains($block->field_styles->value, 'black-bg-left'))
      || ($row == 'blb_region_col_2' && str_contains($block->field_styles->value, 'black-bg-right')))) {
      // 2 column additional settings
      $additional = [
        'bootstrap_styles' => [
          'block_style' => [
            'background_color' => [
              'class' => 'osu-bg-page-alt-2'
            ],
            'text_color' => [
              'class' => 'osu-text-bucktoothwhite'
            ]
          ]
        ]
      ];
    } else if ($item->getType() == 'paragraph_1_col') {
      // 1 column text alignments
      $alignment = 'bs-text-center';
      if ($block->field_styles->value != NULL) {
        if (str_contains($block->field_styles->value, 'left')) {
          $alignment = 'bs-text-left';
        } else if (str_contains($block->field_styles->value, 'right')) {
          $alignment = 'bs-text-right';
        }
      }
      $additional = [
        'bootstrap_styles' => [
          'block_style' => [
            'text_alignment' => [
              'class' => $alignment
            ]
          ]
        ]
      ];
    }

    return $additional;
  }

  /**
   * Some blocks need to set additional settings on the section
   *
   * @param \Drupal\layout_builder\Section $section
   *   The layout builder section this block will be applied to
   * @param \Drupal\block\Entity\Block $block
   *   The block we get settings from
   * @param \Drupal\paragraphs_to_layout_builder\LayoutMigrationItem $item
   *   A migration item instance.
   */
  protected function setAdditionalSectionSettings($section, $block, $item) {
    $settings = $section->getLayoutSettings();
    if ($item->getType() == 'paragraph_1_col_clean') {
      // 1 column margin settings
      switch ($block->field_styles->value) {
        case '0':
          $settings['container'] = 'w-100';
          break;

        case '67':
          $settings['container'] = 'container-fluid';
          break;

        case '10':
        case '20':
          $settings['container'] = 'container';
          break;
      }
      $section->setLayoutSettings($settings);
    } else if ($item->getType() == 'paragraph_divider') {
      // default settings for dividers
      $settings['container_wrapper']['bootstrap_styles']['background_color'] = ['class' => 'osu-bg-page-alt-2'];
      $settings['container_wrapper']['bootstrap_styles']['min_height'] = ['class' => 'osu-min-h-100'];

      /* TODO: Other divider colors
      if (str_contains($block->field_styles->value, 'black')) {
        $settings['container_wrapper']['bootstrap_styles']['background_color'] = ['class' => 'osu-bg-page-alt-2'];
      }
      */

      // divider thickness
      if (str_contains($block->field_styles->value, 'medium')) {
        $settings['container_wrapper']['bootstrap_styles']['min_height'] = ['class' => 'osu-min-h-200'];
      } else if (str_contains($block->field_styles->value, 'large')) {
        $settings['container_wrapper']['bootstrap_styles']['min_height'] = ['class' => 'osu-min-h-300'];
      }

      $section->setLayoutSettings($settings);
    }
  }

  /**
   * Looks up a block from a given migration.
   *
   * @param string $migration_id
   *   The migration id to search.
   * @param string $id
   *   The source id from the migration.
   *
   * @return int
   *   The block id of the located block.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\paragraphs_to_layout_builder\LayoutMigrationMissingBlockException
   */
  public function lookupBlock($migration_id, $id) {
    $source = [$id];
    $block_ids = $this->migrateLookup->lookup($migration_id, $source);

    if (empty($block_ids)) {
      throw new LayoutMigrationMissingBlockException(
        sprintf('Unable to find related migrated block for source id %s in migration %s', $id, $migration_id),
        MigrationInterface::MESSAGE_WARNING
      );
    }

    return reset($block_ids)['id'];
  }

  /**
   * Creates a layout builder section component.
   *
   * @param string $block_type
   *   The block type machine name to embed as an inline block for.
   * @param int|string $block_latest_revision_id
   *   The numeric block content revision id.
   * @param int $weight
   *   The weight of the component.
   * @param string $row
   *   The region of the layout the component will reside in.
   * @param array $additional
   *   additional section settings
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   Returns the layout builder section component that gets added.
   */
  public function createSectionComponent($block_type, $block_latest_revision_id, $weight = 0, $row, $additional) {
    return SectionComponent::fromArray([
      'uuid' => $this->uuid->generate(),
      'region' => $row,
      'configuration' => [
        'id' => "inline_block:{$block_type}",
        'label' => 'Layout Builder Inline Block',
        'provider' => 'layout_builder',
        'label_display' => '0',
        'view_mode' => 'full',
        'block_revision_id' => $block_latest_revision_id,
        'block_serialized' => NULL,
        'context_mapping' => [],
      ],
      'additional' => $additional,
      'weight' => $weight,
    ]);
  }

  /**
   * Maps paragraph bundle type to bootstrap layout builder section type
   * 
   * @param string $paragraphType
   *   Name of the paragraph bundle
   * 
   * @return string
   *   Name of the layout builder section
   */
  public static function getSectionType($paragraphType) {
    $types = array (
      "paragraph_1_col_clean" => "bootstrap_layout_builder:blb_col_1",
      "paragraph_1_col" => "bootstrap_layout_builder:blb_col_1",
      "paragraph_divider" => "bootstrap_layout_builder:blb_col_1",
      "paragraph_menu" => "bootstrap_layout_builder:blb_col_3",
      "paragraph_2_col" => "bootstrap_layout_builder:blb_col_2",
      "paragraph_3_col" => "bootstrap_layout_builder:blb_col_3"
    );

    return $types[$paragraphType];
  }

  /**
   * Loads default layout builder sections for a content type.
   *
   * @param string $bundle
   *   The content type to load defaults from.
   *
   * @return \Drupal\layout_builder\Section[]
   *   An array of the default layout builder section objects loaded from
   *   config.
   */
  protected function loadDefaultSections($bundle) {
    $config = $this->configFactory->get("core.entity_view_display.node.{$bundle}.default");
    $sections_array = $config->get('third_party_settings.layout_builder.sections');
    $sections = [];
    
    if (!empty($sections_array)) {
      foreach($sections_array as $section) {
        $sections[] = Section::fromArray($section);
      }
    }

    return $sections;
  }

  /**
   * Handles exceptions for missing blocks.
   *
   * Writes a message to the migrate map table and displays the message.
   *
   * @param \Drupal\migrate\MigrateExecutableInterface $migrateExecutable
   *   The current migration executable.
   * @param \Drupal\paragraphs_to_blocks\LayoutMigrationMissingBlockException $e
   *   The exception thrown when unable to find a block.
   */
  protected function handleMissingBlockException(MigrateExecutableInterface $migrateExecutable, LayoutMigrationMissingBlockException $e) {
    $migrateExecutable->saveMessage($e->getMessage(), $e->getCode());
    if ($migrateExecutable instanceof MigrateExecutable) {
      $migrateExecutable->message->display($e->getMessage());
    }
  }
}
