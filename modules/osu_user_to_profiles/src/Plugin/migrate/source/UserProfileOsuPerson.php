<?php

namespace Drupal\osu_user_to_profiles\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 Migrate First Name field.
 *
 * @MigrateSource(
 *   id = "d7_user_profile_osu_person",
 *   source_module = "osu_profiles"
 * )
 */
class UserProfileOsuPerson extends DrupalSqlBase {

  /**
   * @inheritDoc
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function query() {
    $query = $this->select('profile', 'p');
    $query->fields('p', ['pid', 'uid']);
    $query->innerJoin('field_data_first_name', 'fdfn', 'fdfn.entity_id = p.pid');
    $query->leftJoin('field_data_middle_name', 'fdmn', 'fdmn.entity_id = p.pid');
    $query->innerJoin('field_data_last_name', 'fdln', 'fdln.entity_id = p.pid');
    $query->leftJoin('field_data_biography', 'bio', 'bio.entity_id = p.pid');
    $query->leftJoin('field_data_image', 'fdimage', 'fdimage.entity_id = p.pid AND fdimage.entity_type = :profile_type', [':profile_type' => 'profile2']);
    $query->addField('fdfn', 'first_name_value');
    $query->addField('fdmn', 'middle_name_value');
    $query->addField('fdln', 'last_name_value');
    $query->addField('bio', 'biography_value');
    $query->addField('fdimage', 'image_fid');
    $query->addField('fdimage', 'image_alt');
    $query->condition('p.type', 'osu_person');
    $query->distinct();
    return $query;
  }

  /**
   * @inheritDoc
   */
  public function fields() {
    return [
      'pid' => $this->t('The Profile ID'),
      'uid' => $this->t('The User ID'),
      'first_name_value' => $this->t('The First Name'),
      'middle_name_value' => $this->t('The Middle Name'),
      'last_name_value' => $this->t('The Last Name'),
      'biography_value' => $this->t('The Profile Biography'),
      'image_fid' => $this->t('The Image ID'),
      'image_alt' => $this->t('The Image Alt Text'),
    ];
  }

}
