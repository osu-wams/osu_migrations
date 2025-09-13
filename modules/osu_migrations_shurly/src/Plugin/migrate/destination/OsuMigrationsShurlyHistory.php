<?php

namespace Drupal\osu_migrations_shurly\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ShURLy destination plugin.
 */
#[MigrateDestination(
  id: 'shurly_history'
)]
class OsuMigrationsShurlyHistory extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
  }

  /**
   * Creates a new instance of the destination plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\migrate\Plugin\MigrationInterface|null $migration
   *
   * @return \Drupal\osu_migrations_shurly\Plugin\migrate\destination\OsuMigrationsShurlyHistory|static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL): OsuMigrationsShurlyHistory|static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getIds(): array {
    return ['hid' => ['type' => 'integer']];
  }

  /**
   * {@inheritDoc}
   */
  public function import(Row $row, array $old_destination_id_values = []): bool|array {
    $record = [];
    $record['hid'] = $row->getSourceProperty('hid');
    $record['rid'] = $row->getSourceProperty('rid');
    $record['vid'] = $row->getSourceProperty('vid');
    $record['source'] = $row->getSourceProperty('source');
    $record['destination'] = $row->getSourceProperty('destination');
    $record['last_date'] = $row->getSourceProperty('last_date');
    $record['count'] = $row->getSourceProperty('count');
    return [
      $this->database->insert('shurly_history')
        ->fields($record)
        ->execute(),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function fields(): array {
    return [
      'hid' => $this->t('The history ID.'),
      'rid' => $this->t('The redirect ID.'),
      'vid' => $this->t('The version ID.'),
      'source' => $this->t('The source URL.'),
      'destination' => $this->t('The destination URL.'),
      'last_date' => $this->t('The last date.'),
      'count' => $this->t('The count.'),
    ];
  }

}
