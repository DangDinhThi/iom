<?php

namespace Drupal\iom_book;

use DateTime;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Book Helper service to handle process related to Book content type.
 */
final class BookHelper {

  /**
   * Constructs a BookHelper object.
   */
  public function __construct(
    private readonly RouteMatchInterface $routeMatch
  ) {}

  /**
   * Add 3 apostrophes on book title which content contain 'awesome'.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Book Node entity.
   *
   * @return string
   *   Node title after applying the rule.
   *
   */
  public function getTitle(NodeInterface $node): string {
    if ($node->bundle() == 'book' &&
      $node->hasField('field_content') &&
      !$node->get('field_content')->isEmpty()) {
      $node_content = $node->get('field_content')->getString();
      if (str_contains($node_content, 'awesome')) {
        return $node->getTitle() . '!!!';
      }
    }
    return $node->getTitle();
  }

  /**
   * The logic for checking if the book is more than 1 year old.
   *
   * @throws \Exception
   */
  public function isOver1YearOld(NodeInterface $book): bool {
    if ($book->hasField('field_publication_date') && !$book->get('field_publication_date')->isEmpty()) {
      $publication_date = $book->get('field_publication_date')->getString();
      $publication_date_timestamp = new DateTime($publication_date);
      return $publication_date_timestamp->diff(new DateTime())->i >= 1;
    }
    return FALSE;
  }

  /**
   * Check book's age and user role before viewing the book.
   * @param \Drupal\node\NodeInterface $book
   *   The book node entity to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account viewing the node page.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   Access result
   * @throws \Exception
   */
  public function bookAccessChecking(NodeInterface $book, AccountInterface $account): AccessResultForbidden|AccessResultNeutral|AccessResultAllowed {
    if ($account->isAnonymous() ||
      $account->hasPermission('administration') ||
      $this->routeMatch->getRouteName() !== 'entity.node.canonical' ||
      !$this->isOver1YearOld($book)) {
      return AccessResult::neutral();
    }
    // Allow to view book if user has role full_editor.
    // Even user has other roles.
    if (in_array('full_editor', $account->getRoles())) {
      return AccessResult::allowed();
    }
    // Disallow view book with user has role editor
    // And doesn't have full_editor role.
    if (in_array('editor', $account->getRoles())) {
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();

  }
}
