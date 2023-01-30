<?php

namespace Drupal\og_to_group\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal 7 OG Membership source from Database, Filtered.
 *
 * Migrate Source Plugin to query Drupal 7 and load a filtered set of group
 * memberships filtering based on the group role.
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
class OgMembershipFiltered extends OgMembership {

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
   *
   * Join roles and user roles on OG Memberships to filter relationships to
   * users who have the provided roles.
   */
  public function query() {
    $query = parent::query();
    $query->innerJoin('og_users_roles', 'ogur', 'ogm.gid = ogur.gid');
    $query->innerJoin('og_role', 'ogr', 'ogur.rid = ogr.rid');
    $query->condition('ogr.name', $this->groupRoles, 'IN');
    $query->distinct(TRUE);
    return $query;
  }

}
