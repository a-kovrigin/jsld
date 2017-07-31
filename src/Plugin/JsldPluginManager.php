<?php

namespace Drupal\jsld\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin type manager for all jsld plugins.
 *
 * @ingroup jsld_plugins
 */
class JsldPluginManager extends DefaultPluginManager {

  /**
   * Constructs a JsldPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example entity.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = 'Drupal\jsld\Annotation\Jsld' . Container::camelize($type);
    parent::__construct("Plugin/jsld/$type", $namespaces, $module_handler, 'Drupal\jsld\Plugin\jsld\JsldPluginInterface', $plugin_definition_annotation_name);

    $this->defaults += [
      'plugin_type' => $type,
    ];

    $this->alterInfo('jsld_plugins_' . $type);
    $this->setCacheBackend($cache_backend, "jsld:$type");
  }

}
