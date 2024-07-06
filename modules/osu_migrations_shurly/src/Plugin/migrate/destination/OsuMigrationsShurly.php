<?php

namespace Drupal\osu_migrations_shurly\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;

/**
 * Provides a ShURLy destination plugin.
 *
 * @MigrateDestination(
 *   id = "shurly"
 * )
 */
class OsuMigrationsShurly extends DestinationBase {

  /**
   * {@inheritDoc}
   */
  public function getIds() {
    return ['rid' => ['type' => 'integer']];
  }

  /**
   * {@inheritDoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Implement your custom import logic here.
    // This method should return an array of destination IDs if successful,
    // false on failure.
    $record = [];
    $record['destination'] = $row->getSourceProperty('destination');
    $record['hash'] = $row->getSourceProperty('hash');
    $record['custom'] = $row->getSourceProperty('custom');
    $record['created'] = $row->getSourceProperty('created');
    $record['source'] = $row->getSourceProperty('source');
    $record['uid'] = $row->getSourceProperty('uid');
    $record['count'] = $row->getSourceProperty('count');
    $record['last_used'] = $row->getSourceProperty('last_used');
    $record['active'] = $row->getSourceProperty('active');

    return [\Drupal::database()->insert('shurly')->fields($record)->execute()];
  }

  /**
   * {@inheritDoc}
   */
  public function fields() {
    // Optional. Provide a list of available fields to map to.
    // This could be resourced from provided CSV file, external API, etc.
    // It should return an associative array where keys are the field/column
    // names and values are descriptions.
    // If this method is not provided, it's assumed all source fields can be
    // mapped to the destination.
  }

}
