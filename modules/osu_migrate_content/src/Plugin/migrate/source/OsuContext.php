<?php

namespace Drupal\osu_migrate_content\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Context source plugin.
 *
 * @MigrateSource(
 *   id = "osu_d7_context",
 *   source_module = "context"
 * )
 */
class OsuContext extends DrupalSqlBase {

  /**
   * {@inheritDoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'max_length' => 255,
        'is_ascii' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    $query = $this->select('context', 'oc');
    $query->fields('oc', [
      'name',
      'description',
      'tag',
      'conditions',
      'reactions',
      'condition_mode',
    ]);
    return $query;
  }

  /**
   * {@inheritDoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Context name'),
      'description' => $this->t('Context description'),
      'tag' => $this->t('Context tag'),
      'conditions' => $this->t('Context conditions'),
      'reactions' => $this->t('Context reactions'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function prepareRow(Row $row) {
    $context_name = $row->getSourceProperty('name');
    $context_id = preg_replace('/-/', '_', $context_name);
    $row->setSourceProperty('id', $context_id);
    return parent::prepareRow($row);
  }

}
