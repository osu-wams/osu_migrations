<?php

namespace Drupal\og_to_group\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 OG Membership source from Database.
 *
 * @MigrateSource(
 *   id = "d7_og_membership",
 *   source_module = "og"
 * )
 *
 * @code
 * source:
 *   plugin: d7_og_membership
 * @endcode
 */
class OgMembership extends DrupalSqlBase {

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
    $query->condition('ogm.entity_type', 'user');
    $query->distinct();
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
