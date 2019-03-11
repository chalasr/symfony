<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Loader\Configurator\Traits\LocalizedRouteTrait;
use Symfony\Component\Routing\Loader\Configurator\Traits\PrefixTrait;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads Yaml routing files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class YamlFileLoader extends FileLoader
{
    use LocalizedRouteTrait;
    use PrefixTrait;

    private static $availableKeys = [
        'resource', 'type', 'prefix', 'path', 'host', 'schemes', 'methods', 'defaults', 'requirements', 'options', 'condition', 'controller', 'name_prefix', 'trailing_slash_on_root',
    ];
    private $yamlParser;

    /**
     * Loads a Yaml file.
     *
     * @param string      $file A Yaml file path
     * @param string|null $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When a route can't be parsed because YAML is invalid
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!stream_is_local($path)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $path));
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" not found.', $path));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        try {
            $parsedConfig = $this->yamlParser->parseFile($path, Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $path), 0, $e);
        }

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($path));

        // empty file
        if (null === $parsedConfig) {
            return $collection;
        }

        // not an array
        if (!\is_array($parsedConfig)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a YAML array.', $path));
        }

        foreach ($parsedConfig as $name => $config) {
            $this->validate($config, $name, $path);

            if (isset($config['resource'])) {
                $this->parseImport($collection, $config, $path, $file);
            } else {
                $this->parseRoute($collection, $name, $config, $path);
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return \is_string($resource) && \in_array(pathinfo($resource, PATHINFO_EXTENSION), ['yml', 'yaml'], true) && (!$type || 'yaml' === $type);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param string          $name       Route name
     * @param array           $config     Route definition
     * @param string          $path       Full path of the YAML file being processed
     */
    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        $requirements = $config['requirements'] ?? [];

        foreach ($requirements as $placeholder => $requirement) {
            if (\is_int($placeholder)) {
                @trigger_error(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s" in "%s"?', $placeholder, $requirement, $name, $path), E_USER_DEPRECATED);
            }
        }

        $defaults = isset($config['defaults']) ? $config['defaults'] : [];

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }

        $routes = $this->createRoutes($collection, $name, $config['path']);
        $routes->addDefaults($defaults);
        $routes->addRequirements($requirements);
        $routes->addOptions($config['options'] ?? []);
        $routes->setHost($config['host'] ?? '');
        $routes->setSchemes($config['schemes'] ?? []);
        $routes->setMethods($config['methods'] ?? []);
        $routes->setCondition($config['condition'] ?? null);
    }

    /**
     * Parses an import and adds the routes in the resource to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param array           $config     Route definition
     * @param string          $path       Full path of the YAML file being processed
     * @param string          $file       Loaded file name
     */
    protected function parseImport(RouteCollection $collection, array $config, $path, $file)
    {
        $defaults = $config['defaults'] ?? [];

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }

        $this->setCurrentDir(\dirname($path));

        $type = $config['type'] ?? null;

        $imported = $this->import($config['resource'], $type, false, $file);

        if (!\is_array($imported)) {
            $imported = [$imported];
        }

        $requirements = $config['requirements'] ?? [];
        $options = $config['options'] ?? [];
        $host = $config['host'] ?? null;
        $schemes = $config['schemes'] ?? null;
        $methods = $config['methods'] ?? null;
        $condition = $config['condition'] ?? null;
        $namePrefix = $config['name_prefix'] ?? '';
        $prefix = $config['prefix'] ?? '';
        $trailingSlashOnRoot = $config['trailing_slash_on_root'] ?? true;

        foreach ($imported as $subCollection) {
            $this->addPrefix($subCollection, $prefix, $trailingSlashOnRoot);

            $subCollection->addDefaults($defaults);
            $subCollection->addRequirements($requirements);
            $subCollection->addOptions($options);
            $subCollection->setHost($host);
            $subCollection->setSchemes($schemes);
            $subCollection->setMethods($methods);
            $subCollection->setCondition($condition);
            $subCollection->addNamePrefix($namePrefix);

            $collection->addCollection($subCollection);
        }
    }

    /**
     * Validates the route configuration.
     *
     * @param array  $config A resource config
     * @param string $name   The config key
     * @param string $path   The loaded file path
     *
     * @throws \InvalidArgumentException If one of the provided config keys is not supported,
     *                                   something is missing or the combination is nonsense
     */
    protected function validate($config, $name, $path)
    {
        if (!\is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The definition of "%s" in "%s" must be a YAML array.', $name, $path));
        }
        if ($extraKeys = array_diff(array_keys($config), self::$availableKeys)) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" contains unsupported keys for "%s": "%s". Expected one of: "%s".', $path, $name, implode('", "', $extraKeys), implode('", "', self::$availableKeys)));
        }
        if (isset($config['resource']) && isset($config['path'])) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "resource" key and the "path" key for "%s". Choose between an import and a route definition.', $path, $name));
        }
        if (!isset($config['resource']) && isset($config['type'])) {
            throw new \InvalidArgumentException(sprintf('The "type" key for the route definition "%s" in "%s" is unsupported. It is only available for imports in combination with the "resource" key.', $name, $path));
        }
        if (!isset($config['resource']) && !isset($config['path'])) {
            throw new \InvalidArgumentException(sprintf('You must define a "path" for the route "%s" in file "%s".', $name, $path));
        }
        if (isset($config['controller']) && isset($config['defaults']['_controller'])) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "controller" key and the defaults key "_controller" for "%s".', $path, $name));
        }
    }
}
