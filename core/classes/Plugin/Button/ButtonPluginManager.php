<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Plugin\Button;

use Exception;
use Ox\Core\CClassMap;
use Ox\Core\Module\ModuleManagerTrait;
use SplPriorityQueue;

/**
 * Button Plugin Manager
 */
class ButtonPluginManager {
  use ModuleManagerTrait;

  /** @var self */
  private static $instance;

  /** @var array */
  private $locations = [];

  /** @var array */
  private $register_bag = [];

  /**
   * ButtonPluginManager constructor.
   */
  private function __construct() {
  }

  /**
   * @return static
   * @throws Exception
   */
  public static function get(): self {
    if (self::$instance instanceof self) {
      return self::$instance;
    }

    $instance = new self();
    $instance->registerAll();

    return self::$instance = $instance;
  }

  /**
   * Register all buttons declared in modules
   *
   * @return void
   * @throws Exception
   */
  private function registerAll(): void {
    $module_button_plugin = $this->getRegisteredButtons();

    /** @var AbstractButtonPlugin $_module_button_plugin */
    foreach ($module_button_plugin as $_module_button_plugin) {
      $_module_name = $this->getModuleForClass($_module_button_plugin);

      if (!$this->isModuleActive($_module_name)) {
        continue;
      }

      $_module_button_plugin::registerButtons($this);
      $this->applyForModule($_module_name);
    }
  }

  /**
   * Get the Button classes
   *
   * @return array
   * @throws Exception
   */
  protected function getRegisteredButtons(): array {
    return CClassMap::getInstance()->getClassChildren(
      AbstractButtonPlugin::class, false, false
    );
  }

  /**
   * Create buttons from the register bag for each location, according to priority
   *
   * @param string $module_name
   *
   * @return void
   */
  private function applyForModule(string $module_name): void {
    foreach ($this->register_bag as $_button_to_register) {
      $_button = new ButtonPlugin(
        $_button_to_register['label'],
        $_button_to_register['class_names'],
        $_button_to_register['disabled'],
        $module_name,
        $_button_to_register['onclick'],
        $_button_to_register['script_name']
      );

      $_priority = $_button_to_register['priority'];

      foreach ($_button_to_register['locations'] as $_location) {
        $this->registerButtonForLocation($_button, $_location, $_priority);
      }
    }

    // Resetting the bag
    $this->register_bag = [];
  }

  /**
   * Get the buttons for a given location
   *
   * @param string $location
   * @param mixed  ...$args
   *
   * @return array
   */
  public function getButtonsForLocation(string $location, ...$args): array {
    if (!$this->isLocationRegistered($location)) {
      return [];
    }

    // Because of loop is dequeuing elements
    $queue = clone $this->locations[$location];

    $buttons = [];

    /** @var ButtonPlugin $_item */
    foreach ($queue as $_item) {
      $_item->setParameters($args);

      $buttons[] = $_item;
    }

    return $buttons;
  }

  /**
   * @param ButtonPlugin $button
   * @param string       $location
   * @param int          $priority
   *
   * @return void
   */
  private function registerButtonForLocation(ButtonPlugin $button, string $location, int $priority): void {
    $this->registerLocation($location)->insert($button, $priority);
  }

  /**
   * @param string $location
   *
   * @return bool
   */
  private function isLocationRegistered(string $location): bool {
    return array_key_exists($location, $this->locations);
  }

  /**
   * @param string $location
   *
   * @return SplPriorityQueue
   */
  private function registerLocation(string $location): SplPriorityQueue {
    if ($this->isLocationRegistered($location)) {
      return $this->locations[$location];
    }

    return $this->locations[$location] = new SplPriorityQueue();
  }

  /**
   * @param string $label
   * @param string $class_names
   * @param bool   $disabled
   * @param array  $locations
   * @param int    $priority
   * @param string $onclick
   * @param string $script_name
   *
   * @return void
   */
  public function register(
    string $label,
    string $class_names,
    bool $disabled,
    array $locations,
    int $priority,
    string $onclick,
    string $script_name
  ): void {
    $this->register_bag[] = [
      'label'       => $label,
      'class_names' => $class_names,
      'disabled'    => $disabled,
      'locations'   => $locations,
      'priority'    => $priority,
      'onclick'     => $onclick,
      'script_name' => $script_name,
    ];
  }
}
