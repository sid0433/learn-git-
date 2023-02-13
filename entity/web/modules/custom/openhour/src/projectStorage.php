<?php

namespace Drupal\openhour;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\openhour\Entity\projectInterface;

/**
 * Defines the storage handler class for Project entities.
 *
 * This extends the base storage class, adding required special handling for
 * Project entities.
 *
 * @ingroup openhour
 */
class projectStorage extends SqlContentEntityStorage implements projectStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(projectInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {project_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {project_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
