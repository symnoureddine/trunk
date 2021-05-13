<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Timeline;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Class Timeline
 */
class Timeline implements IShortNameAutoloadable {
  /** @var */
  private $timeline = [];

  /** @var int[] */
  private $badges = [];

  /** @var CMediusers[] */
  private $involved_users = [];

  /** @var ITimelineMenuItem[] */
  private $menu_items = [];

  /**
   * CTimelineCabinet constructor.
   *
   * @param ITimelineMenuItem[] $menu_items
   */
  public function __construct(array $menu_items = []) {
    $this->menu_items = $menu_items;
  }

  /**
   * @return CStoredObject[]
   */
  public function getTimeline() {
    return $this->timeline;
  }

  /**
   * @return int[]
   */
  public function getBadges() {
    return $this->badges;
  }

  /**
   * @return CMediusers[]
   */
  public function getInvolvedUsers() {
    return $this->involved_users;
  }

  /**
   * @return ITimelineMenuItem[]
   */
  public function getMenuItems() {
    return $this->menu_items;
  }

  /**
   * @return void
   * @throws Exception
   */
  public function buildTimeline() {
    foreach ($this->menu_items as $_menu_item) {
      $_events = $_menu_item->getEventsByDate();

      if ($_events) {
        $this->mergeTimeline($_events);
      }

      $this->mergeBadges([$_menu_item->getCanonicalName() => $_menu_item->getAmountEvents()]);
      $this->mergeInvolvedUsers($_menu_item->getInvolvedUsers());

      foreach ($_menu_item->getChildren() as $_child) {
        $_events = $_child->getEventsByDate();

        if ($_events) {
          $this->mergeTimeline($_events);
        }

        $_amount_events = $_child->getAmountEvents();
        $this->mergeBadges([$_menu_item->getCanonicalName() => $_amount_events]);
        $this->mergeBadges([$_child->getCanonicalName() => $_amount_events]);

        $this->mergeInvolvedUsers($_child->getInvolvedUsers());
      }
    }

    $this->sortTimeline();
  }

  /**
   * Find attached menus
   *
   * @param ITimelineMenuItem $menu_item - the menu to search for
   *
   * @return ITimelineMenuItem[]
   */
  private function findAttachedMenus(ITimelineMenuItem $menu_item) {
    $attached = [];

    foreach ($this->menu_items as $_item) {
      if ($_item instanceof ITimelineAttachableMenuItem) {
        if ($_item->attachedTo() === $menu_item) {
          $attached[] = $_item;
        }
      }
    }

    return $attached;
  }

  /**
   * @param array $timeline
   *
   * @return void
   */
  private function mergeTimeline(array $timeline) {
    foreach (array_unique(array_keys($timeline) + array_keys($this->timeline)) as $_year) {
      if (isset($timeline[$_year])) {
        if (!isset($this->timeline[$_year])) {
          $this->timeline[$_year] = [];
        }
        $this->timeline[$_year] = array_merge_recursive($this->timeline[$_year], $timeline[$_year]);
      }
    }
  }

  /**
   * @param array $amount_events
   *
   * @return void
   */
  private function mergeBadges(array $amount_events) {
    foreach ($amount_events as $_event => $_amount) {
      if (!isset($this->badges[$_event])) {
        $this->badges += $amount_events;
      }
      else {
        $this->badges[$_event] += $_amount;
      }
    }
  }

  /**
   * @param array $getInvolvedUsers
   *
   * @return void
   * @throws Exception
   */
  private function mergeInvolvedUsers(array $getInvolvedUsers) {
    foreach (array_unique($getInvolvedUsers) as $_user) {
      if (!$_user instanceof CMediusers) {
        throw new Exception("This is not a mediuser ! (CTimelineCabinet::mergeInvolvedUsers)");
      }

      if (!isset($this->involved_users[$_user->_id])) {
        $_user->loadRefFunction();

        $this->involved_users[$_user->_id] = $_user;
      }
    }
  }

  /**
   * Sorts the timeline by Year/Month/Date
   *
   * @return void
   */
  private function sortTimeline() {
    krsort($this->timeline);
    foreach ($this->timeline as &$year) {
      krsort($year);
      foreach ($year as &$month) {
        krsort($month);
      }
    }
  }
}

