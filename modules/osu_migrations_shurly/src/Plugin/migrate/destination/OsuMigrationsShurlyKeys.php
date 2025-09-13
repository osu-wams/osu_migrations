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
  id: 'shurly_keys'
)]
class OsuMigrationsShurlyKeys extends DestinationBase implements ContainerFactoryPluginInterface {

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
    return ['uid' => ['type' => 'integer']];
  }

  /**
   * {@inheritDoc}
   */
  public function import(Row $row, array $old_destination_id_values = []): bool|array {
    $record = [];
    $record['uid'] = $row->getSourceProperty('uid');
    $record['apikey'] = $row->getSourceProperty('apikey');
    return [
      $this->database->insert('shurly_keys')
        ->fields($record)
        ->execute(),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function fields(): array {
    return [
      'uid' => $this->t('The User ID.'),
      'apikey' => $this->t('The API Key.'),
    ];
  }

}
