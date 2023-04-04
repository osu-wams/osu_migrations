<?php

namespace Drupal\osu_migrations\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Process Plugin to transform Drupal 7 embed to Drupal 9
 *
 * @MigrateProcessPlugin(
 *   id = "osu_media_wysiwyg_filter"
 * )
 */
class OsuMediaWysiwygFilter extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Migration Lookup Service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  private MigrateLookupInterface $migrateLookup;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;


  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrateLookup, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrateLookup;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.lookup'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Find our old encoded data and save it a capture group called tag_info
    $pattern = '/\[\[\s*(?<tag_info>\{.+\})\s*\]\]/sU';
    // If we can use associative array use it.
    if (defined(JsonDecode::class . '::ASSOCIATIVE')) {
      $decoder = new JsonDecode([JsonDecode::ASSOCIATIVE => TRUE]);
    }
    else {
      $decoder = new JsonDecode(TRUE);
    }
    // Check to see if the $value is an array or not and if it is an array get
    // the nested value key.
    $value_is_array = is_array($value);
    $text = (string) ($value_is_array ? $value['value'] : $value);
    // Run the anonymous function on the $text using the $pattern.
    $text = preg_replace_callback($pattern, function ($matches) use ($decoder) {
      // Find 2 or more consecutive spaces and replace it with one.
      $matches['tag_info'] = preg_replace('/\s+/', ' ', $matches['tag_info']);

      try {
        $tag_info = $decoder->decode($matches['tag_info'], JsonEncoder::FORMAT);
        if (!is_array($tag_info) || !array_key_exists('fid', $tag_info)) {
          return $matches[0];
        }
        // Get the ID and view mode.
        $embed_metadata = [
          'id' => $tag_info['fid'],
          'view_mode' => $tag_info['view_mode'] ?? 'default',
        ];
        // Check to see if we have attributes and if not create an empty array.
        $source_attributes = !empty($tag_info['attributes']) ? $tag_info['attributes'] : [];
        // Add alt and title overrides.
        foreach (['alt', 'title'] as $attribute_name) {
          if (!empty($source_attributes[$attribute_name])) {
            $embed_metadata[$attribute_name] = $source_attributes[$attribute_name];
          }
        }

        // Get the alignment classes.
        if (!empty($source_attributes['class']) && is_string($source_attributes['class'])) {
          $classes_arr = array_unique(explode(' ', preg_replace('/\s{2,}/', ' ', trim($source_attributes['class']))));
          $old_alignment = [
            'media-wysiwyg-align-center' => 'center',
            'media-wysiwyg-align-left' => 'left',
            'media-wysiwyg-align-right' => 'right',
          ];
          foreach ($old_alignment as $old => $new) {
            if (in_array($old, $classes_arr, TRUE)) {
              $embed_metadata['data-align'] = $new;
            }
          }
        }
        return $this->getEmbedCode($embed_metadata) ?? $matches[0];
      }
      catch (NotEncodableValueException $e) {
        return $matches[0];
      }
      catch (LogicException $e) {
        return $matches[0];
      }
    }, $text);

    if ($value_is_array) {
      $value['value'] = $text;
    }
    else {
      $value = $text;
    }
    return $value;
  }

  /**
   * Get the new drupal media embed code.
   *
   * @param array $embedMetadata
   *   An array of media data.
   *
   * @return string|null
   *   Either return the new media embed code or null.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   */
  private function getEmbedCode(array $embedMetadata) {
    if (empty($embedMetadata['id']) || empty($embedMetadata['view_mode'])) {
      return NULL;
    }
    // Get the New media ID, could be in any one of these migration.
    $newMid = $this->migrateLookup->lookup([
      'upgrade_d7_media_audio',
      'upgrade_d7_media_documents',
      'upgrade_d7_media_images',
      'upgrade_d7_media_kaltura',
      'upgrade_d7_media_local_video',
      'upgrade_d7_media_remote_video',
    ], [$embedMetadata['id']]);
    // Lookup returns a nested array, we only need the id.
    $newMid = reset($newMid)['mid'];
    /** @var \Drupal\media\Entity\Media $mediaEntity */
    $mediaEntity = $this->entityTypeManager->getStorage('media')
      ->load($newMid);
    // Get the UUID of the media object.
    $mediaEntityUuid = $mediaEntity->uuid();

    $attributes = [];
    $attributes['data-entity-type'] = 'media';
    $attributes['data-entity-uuid'] = $mediaEntityUuid;
    $attributes['data-view-mode'] = 'default';
    // Alt, title, caption and align should be handled conditionally.
    $conditionalAttributes = ['alt', 'title', 'data-caption', 'data-align'];
    foreach ($conditionalAttributes as $conditionalAttribute) {
      if (!empty($embedMetadata[$conditionalAttribute])) {
        $attributes[$conditionalAttribute] = $embedMetadata[$conditionalAttribute];
      }
    }
    /** @var \Drupal\Core\Template\Attribute $attribute */
    $attribute = new Attribute($attributes);
    return "<drupal-media {$attribute->__toString()}></drupal-media>";
  }

}
