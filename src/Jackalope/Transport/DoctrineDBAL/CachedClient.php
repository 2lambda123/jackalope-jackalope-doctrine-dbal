<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Doctrine\DBAL\Connection;
use Jackalope\FactoryInterface;
use Jackalope\Node;
use Jackalope\Query\Query;
use PHPCR\ItemNotFoundException;
use PHPCR\RepositoryException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class to add caching to the Doctrine DBAL client.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class CachedClient extends Client
{
    /**
     * @var CacheInterface[]
     */
    private array $caches;

    /**
     * @var \Closure that accepts a cache key as argument and returns a cleaned up version of the key
     */
    private \Closure $keySanitizer;

    /**
     * @param CacheInterface[] $caches
     */
    public function __construct(FactoryInterface $factory, Connection $conn, array $caches)
    {
        parent::__construct($factory, $conn);

        if (!array_key_exists('meta', $caches)) {
            throw new \InvalidArgumentException('The meta cache is required when setting up the CachedClient');
        }
        foreach ($caches as $type => $cache) {
            if (!$cache instanceof CacheInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Cache provided for type "%s" is %s which does not implement %s.',
                    $type,
                    is_object($cache) ? get_class($cache) : gettype($cache),
                    CacheInterface::class
                ));
            }
        }
        $this->caches = $caches;
        $this->keySanitizer = static function ($cacheKey) {
            return str_replace(
                ['%', '.'],
                ['_', '|'],
                \urlencode($cacheKey)
            );
        };
    }

    public function setKeySanitizer(\Closure $sanitizer): void
    {
        $this->keySanitizer = $sanitizer;
    }

    /**
     * @param array|null $caches which caches to invalidate, null means all except meta
     */
    private function clearCaches(?array $caches = null): void
    {
        $caches = $caches ?: ['nodes', 'query'];
        foreach ($caches as $cache) {
            if (array_key_exists($cache, $this->caches)) {
                $this->caches[$cache]->clear();
            }
        }
    }

    /**
     * Sanitizes the key using $this->keySanitizer.
     */
    private function sanitizeKey(string $cacheKey): string
    {
        if ($sanitizer = $this->keySanitizer) {
            return $sanitizer($cacheKey);
        }

        return $cacheKey;
    }

    /**
     * @throws RepositoryException
     */
    private function clearNodeCache(Node $node): void
    {
        $cacheKey = "nodes: {$node->getPath()}, ".$this->workspaceName;
        $cacheKey = $this->sanitizeKey($cacheKey);

        $this->caches['nodes']->delete($cacheKey);

        // Actually in the DBAL all nodes have a uuid ..
        if ($node->isNodeType('mix:referenceable')) {
            $uuid = $node->getIdentifier();
            $cacheKey = "nodes by uuid: $uuid, ".$this->workspaceName;
            $cacheKey = $this->sanitizeKey($cacheKey);
            $this->caches['nodes']->delete($cacheKey);
        }
    }

    public function createWorkspace($name, $srcWorkspace = null): void
    {
        parent::createWorkspace($name, $srcWorkspace);

        $this->caches['meta']->delete('workspaces');
        $this->caches['meta']->set($this->sanitizeKey("workspace: $name"), 1);
    }

    public function deleteWorkspace($name): void
    {
        parent::deleteWorkspace($name);

        $this->caches['meta']->delete('workspaces');
        $this->caches['meta']->delete($this->sanitizeKey("workspace: $name"));
        $this->clearCaches();
    }

    protected function workspaceExists($workspaceName): bool
    {
        $cacheKey = "workspace: $workspaceName";
        $cacheKey = $this->sanitizeKey($cacheKey);

        $result = null !== $this->caches['meta']->get($cacheKey);
        if (!$result && parent::workspaceExists($workspaceName)) {
            $result = true;
            $this->caches['meta']->set($cacheKey, $result);
        }

        return $result;
    }

    protected function fetchUserNodeTypes(): array
    {
        $cacheKey = 'node_types';
        $cacheKey = $this->sanitizeKey($cacheKey);

        if (!$this->inTransaction && $result = $this->caches['meta']->get($cacheKey)) {
            return $result;
        }

        $result = parent::fetchUserNodeTypes();

        if (!$this->inTransaction) {
            $this->caches['meta']->set($cacheKey, $result);
        }

        return $result;
    }

    public function getNodeTypes($nodeTypes = []): array
    {
        $cacheKey = 'nodetypes: '.serialize($nodeTypes);
        $cacheKey = $this->sanitizeKey($cacheKey);

        $result = $this->caches['meta']->get($cacheKey);
        if (!$result) {
            $result = parent::getNodeTypes($nodeTypes);
            $this->caches['meta']->set($cacheKey, $result);
        }

        return $result;
    }

    public function getNamespaces(): array
    {
        if ($this->namespaces instanceof \ArrayObject) {
            return parent::getNamespaces();
        }

        $cacheKey = 'namespaces';
        $cacheKey = $this->sanitizeKey($cacheKey);

        $result = $this->caches['meta']->get($cacheKey);
        if ($result) {
            $this->setNamespaces($result);
        } else {
            $result = parent::getNamespaces();

            $this->caches['meta']->set($cacheKey, $result);
        }

        return (array) $result;
    }

    public function copyNode($srcAbsPath, $destAbsPath, $srcWorkspace = null): void
    {
        parent::copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);

        $this->clearCaches();
    }

    public function getAccessibleWorkspaceNames(): array
    {
        $cacheKey = 'workspaces';
        $cacheKey = $this->sanitizeKey($cacheKey);

        $workspaces = $this->caches['meta']->get($cacheKey);
        if (!$workspaces) {
            $workspaces = parent::getAccessibleWorkspaceNames();
            $this->caches['meta']->set($cacheKey, $workspaces);
        }

        return $workspaces;
    }

    public function getNode(string $path): \stdClass
    {
        if (!array_key_exists('nodes', $this->caches)) {
            return parent::getNode($path);
        }

        $this->assertLoggedIn();

        $cacheKey = "nodes: $path, ".$this->workspaceName;
        $cacheKey = $this->sanitizeKey($cacheKey);

        if (null !== ($result = $this->caches['nodes']->get($cacheKey))) {
            if ('ItemNotFoundException' === $result) {
                throw new ItemNotFoundException("Item '$path' not found in workspace '$this->workspaceName'");
            }

            return $result;
        }

        try {
            $node = parent::getNode($path);
        } catch (ItemNotFoundException $e) {
            if (array_key_exists('nodes', $this->caches)) {
                $this->caches['nodes']->set($cacheKey, 'ItemNotFoundException');
            }

            throw $e;
        }

        $this->caches['nodes']->set($cacheKey, $node);

        return $node;
    }

    public function getNodes(array $paths): array
    {
        if (!array_key_exists('nodes', $this->caches)) {
            return parent::getNodes($paths);
        }

        $nodes = [];
        foreach ($paths as $key => $path) {
            try {
                $nodes[$key] = $this->getNode($path);
            } catch (ItemNotFoundException $e) {
                // ignore
            }
        }

        return $nodes;
    }

    public function getNodeByIdentifier(string $uuid): \stdClass
    {
        $path = $this->getNodePathForIdentifier($uuid);
        $data = $this->getNode($path);
        $data->{':jcr:path'} = $path;

        return $data;
    }

    public function getNodesByIdentifier(array $identifiers): array
    {
        $data = [];
        foreach ($identifiers as $uuid) {
            try {
                $path = $this->getNodePathForIdentifier($uuid);
                $data[$path] = $this->getNode($path);
            } catch (ItemNotFoundException $e) {
                // skip
            }
        }

        return $data;
    }

    public function deleteNodes(array $operations): void
    {
        parent::deleteNodes($operations);

        $this->clearCaches();
    }

    public function deleteProperties(array $operations): void
    {
        parent::deleteProperties($operations);

        // we do not have the node here, otherwise we could use clearNodeCache() and then just invalidate all queries
        $this->clearCaches();
    }

    public function deleteNodeImmediately(string $path): void
    {
        parent::deleteNodeImmediately($path);

        $this->clearCaches();
    }

    public function deletePropertyImmediately($path): void
    {
        parent::deletePropertyImmediately($path);

        // we do not have the node here, otherwise we could use clearNodeCache() and then just invalidate all queries
        $this->clearCaches();
    }

    public function moveNodes(array $operations): void
    {
        parent::moveNodes($operations);

        $this->clearCaches();
    }

    public function moveNodeImmediately(string $srcAbsPath, string $destAbsPath): void
    {
        parent::moveNodeImmediately($srcAbsPath, $destAbsPath);

        $this->clearCaches();
    }

    public function reorderChildren(Node $node): void
    {
        parent::reorderChildren($node);

        $this->clearNodeCache($node);
    }

    public function storeNodes(array $operations): void
    {
        parent::storeNodes($operations);

        // we do not have the node here, otherwise we could just use clearNodeCache() on pre-existing parents and then just invalidate all queries
        $this->clearCaches();
    }

    public function getNodePathForIdentifier($uuid, $workspace = null): string
    {
        if (!array_key_exists('nodes', $this->caches) || null !== $workspace) {
            return parent::getNodePathForIdentifier($uuid);
        }

        $this->assertLoggedIn();

        $cacheKey = "nodes by uuid: $uuid, $this->workspaceName";
        $cacheKey = $this->sanitizeKey($cacheKey);

        if (null !== ($result = $this->caches['nodes']->get($cacheKey))) {
            if ('ItemNotFoundException' === $result) {
                throw new ItemNotFoundException("no item found with uuid $uuid");
            }

            return $result;
        }

        try {
            $path = parent::getNodePathForIdentifier($uuid);
        } catch (ItemNotFoundException $e) {
            if (array_key_exists('nodes', $this->caches)) {
                $this->caches['nodes']->set($cacheKey, 'ItemNotFoundException');
            }

            throw $e;
        }

        $this->caches['nodes']->set($cacheKey, $path);

        return $path;
    }

    public function registerNodeTypes(array $types, bool $allowUpdate): void
    {
        parent::registerNodeTypes($types, $allowUpdate);

        if (!$this->inTransaction) {
            $this->caches['meta']->delete('node_types');
        }
    }

    public function registerNamespace(string $prefix, string $uri): void
    {
        parent::registerNamespace($prefix, $uri);
        $this->caches['meta']->set('namespaces', $this->namespaces);
    }

    public function unregisterNamespace(string $prefix): void
    {
        parent::unregisterNamespace($prefix);
        $this->caches['meta']->set('namespaces', $this->namespaces);
    }

    public function getReferences($path, $name = null): array
    {
        if (!array_key_exists('nodes', $this->caches)) {
            return parent::getReferences($path, $name);
        }

        $cacheKey = "nodes references: $path, $name, ".$this->workspaceName;
        $cacheKey = $this->sanitizeKey($cacheKey);

        if (null !== ($result = $this->caches['nodes']->get($cacheKey))) {
            return $result;
        }

        $references = parent::getReferences($path, $name);

        $this->caches['nodes']->set($cacheKey, $references);

        return $references;
    }

    public function getWeakReferences($path, $name = null): array
    {
        if (!array_key_exists('nodes', $this->caches)) {
            return parent::getWeakReferences($path, $name);
        }

        $cacheKey = "nodes weak references: $path, $name, ".$this->workspaceName;
        $cacheKey = $this->sanitizeKey($cacheKey);

        if ($result = $this->caches['nodes']->get($cacheKey)) {
            return $result;
        }

        $references = parent::getWeakReferences($path, $name);

        $this->caches['nodes']->set($cacheKey, $references);

        return $references;
    }

    public function query(Query $query): array
    {
        if (!array_key_exists('query', $this->caches)) {
            return parent::query($query);
        }

        $this->assertLoggedIn();

        $cacheKey = "query: {$query->getStatement()}, {$query->getLimit()}, {$query->getOffset()}, {$query->getLanguage()}, ".$this->workspaceName;
        $cacheKey = $this->sanitizeKey($cacheKey);

        if (null !== ($result = $this->caches['query']->get($cacheKey))) {
            return $result;
        }

        $result = parent::query($query);

        $this->caches['query']->set($cacheKey, $result);

        return $result;
    }

    public function commitTransaction(): void
    {
        parent::commitTransaction();

        $this->clearCaches(array_keys($this->caches));
    }

    public function rollbackTransaction(): void
    {
        parent::rollbackTransaction();

        $this->clearCaches(array_keys($this->caches));
    }
}
