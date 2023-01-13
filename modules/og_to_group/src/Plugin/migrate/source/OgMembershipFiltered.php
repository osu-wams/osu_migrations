<?php

namespace Drupal\og_to_group\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 OG Membership source from Database, Filtered.
 *
 * @MigrateSource(
 *   id = "d7_og_membership_filtered",
 *   source_module = "og"
 * )
 *
 * @code
 * source:
 *   plugin: d7_og_membership_filtered
 *   roles_name:
 *     - editor
 *     - author
 * @endcode
 */
class OgMembershipFiltered extends DrupalSqlBase {

  /**
   * List of Group Roles used to filter membership on.
   *
   * @var array
   */
  protected $groupRoles = [];

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->groupRoles = $configuration['roles_name'];
  }

  /**
   * {@inheritDoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    $query = $this->select('og_membership', 'ogm');
    $query->fields('ogm', ['id', 'etid', 'gid']);
    $query->innerJoin('og_users_roles', 'ogur', 'ogm.gid = ogur.gid');
    $query->innerJoin('og_role', 'ogr', 'ogur.rid = ogr.rid');
    $query->condition('ogm.entity_type', 'user', '=');
    $query->condition('ogr.name', $this->groupRoles, 'IN');
    $query->distinct(TRUE);
    return $query;
  }

  /**
   * {@inheritDoc}
   */
  public function fields() {
    return [
      'id' => $this->t('The Group Membership ID'),
      'etid' => $this->t('The Target Entity ID'),
      'gid' => $this->t('The Group ID'),
    ];
  }

}
