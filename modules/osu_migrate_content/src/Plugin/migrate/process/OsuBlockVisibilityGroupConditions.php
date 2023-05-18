<?php

namespace Drupal\osu_migrate_content\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process Plugin to Work on Context to Block Visibility Group Conditions.
 *
 * @MigrateProcessPlugin (
 *    id = "osu_block_visibility_group_conditions",
 *   source_module = "menu"
 * )
 */
class OsuBlockVisibilityGroupConditions extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Entity Type Bundle.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $nodeType;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $nodeType) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeType = $nodeType;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("entity_type.manager")->getStorage("node_type")
    );
  }

  /**
   * {@inheritDoc}
   *
   * Convert the context conditions into block visibility group conditions.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $context_condition = unserialize($value, ['allowed_classes' => FALSE]);
    $node_bundles = $this->nodeType->loadMultiple();
    foreach ($node_bundles as $node_name => $node_type) {
      $node_bundles[$node_name] = $node_name;
    }
    $bvg_conditions = [];
    foreach ($context_condition as $context_type => $condition) {
      switch ($context_type) {
        case "path":
          $negatedPaths = preg_grep("/^~/", $condition["values"]);
          $positivePaths = preg_grep("/^~/", $condition["values"], PREG_GREP_INVERT);
          if (count($positivePaths) > 0) {
            $bvg_conditions[] = [
              "id" => "request_path",
              "pages" => implode("\r\n", $positivePaths),
              "negate" => FALSE,
            ];
          }
          if (count($negatedPaths) > 0) {
            $bvg_conditions[] = [
              "id" => "request_path",
              "pages" => implode("\r\n", $negatedPaths),
              "negate" => TRUE,
            ];
          }
          break;

        case "node":
          $old_node_bundle = $condition["values"];
          if (array_key_exists("book", $old_node_bundle)) {
            unset($old_node_bundle["book"]);
            $old_node_bundle["page"] = "page";
          }
          $new_node_bundles = array_intersect($node_bundles, $old_node_bundle);
          if (count($new_node_bundles) > 0) {
            $bvg_conditions[] = [
              "id" => "entity_bundle:node",
              "context_mapping" => [
                "node" => "@node.node_route_context:node",
              ],
              "negate" => FALSE,
              "bundles" => $new_node_bundles,
            ];
          }
          break;

        case "user":
          $bvg_conditions[] = [
            "id" => "user_role",
            "negate" => FALSE,
            "context_mapping" => [
              "user" => "@user.current_user_context:current_user",
            ],
            "roles" => [
              "authenticated" => "authenticated",
            ],
          ];
          break;

        case "sitewide":
          $bvg_conditions[] = [
            "id" => "request_path",
            "pages" => "*",
            "negate" => FALSE,
          ];
          break;
      }
    }
    return $bvg_conditions;
  }

}
