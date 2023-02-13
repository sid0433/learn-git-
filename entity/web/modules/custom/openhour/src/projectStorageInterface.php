<?php

namespace Drupal\openhour;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface projectStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Project revision IDs for a specific Project.
   *
   * @param \Drupal\openhour\Entity\projectInterface $entity
   *   The Project entity.
   *
   * @return int[]
   *   Project revision IDs (in ascending order).
   */
  public function revisionIds(projectInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Project author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Project revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
