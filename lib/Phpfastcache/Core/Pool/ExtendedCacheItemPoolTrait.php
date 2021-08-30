<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache\Core\Pool;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\{PhpfastcacheCoreException, PhpfastcacheDriverException, PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;


/**
 * Trait ExtendedCacheItemPoolTrait
 * @package Phpfastcache\Core\Pool
 */
trait ExtendedCacheItemPoolTrait
{
    use CacheItemPoolTrait;

    /**
     * @param array $keys
     * @param int $option
     * @param int $depth
     * @return string
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getItemsAsJsonString(array $keys = [], int $option = \JSON_THROW_ON_ERROR, int $depth = 512): string
    {
        return \json_encode(
            \array_map(
                static fn(CacheItemInterface $item) => $item->get(),
                \array_values($this->getItems($keys))
            ),
            $option,
            $depth
        );
    }

    public function detachAllItems(): static
    {
        foreach ($this->itemInstances as $item) {
            $this->detachItem($item);
        }

        return $this;
    }

    public function detachItem(CacheItemInterface $item): static
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            $this->deregisterItem($item->getKey());
        }

        return $this;
    }

    /**
     * @param string $item
     * @return ExtendedCacheItemPoolTrait
     * @internal This method de-register an item from $this->itemInstances
     */
    protected function deregisterItem(string $item): static
    {
        unset($this->itemInstances[$item]);

        if (\gc_enabled()) {
            \gc_collect_cycles();
        }

        return $this;
    }

    /**
     * @param CacheItemInterface $item
     * @return ExtendedCacheItemPoolTrait
     * @throws PhpfastcacheLogicException
     */
    public function attachItem(CacheItemInterface $item): static
    {
        if (isset($this->itemInstances[$item->getKey()]) && \spl_object_hash($item) !== \spl_object_hash($this->itemInstances[$item->getKey()])) {
            throw new PhpfastcacheLogicException(
                'The item already exists and cannot be overwritten because the Spl object hash mismatches ! You probably tried to re-attach a detached item which has been already retrieved from cache.'
            );
        }

        if(!$this->getConfig()->isUseStaticItemCaching()){
            throw new PhpfastcacheLogicException(
                'The static item caching option (useStaticItemCaching) is disabled so you cannot attach an item.'
            );
        }

        $this->itemInstances[$item->getKey()] = $item;

        return $this;
    }

    public function isAttached(CacheItemInterface $item): bool
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            return \spl_object_hash($item) === \spl_object_hash($this->itemInstances[$item->getKey()]);
        }
        return false;
    }

    /**
     * @param ExtendedCacheItemInterface ...$items
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function saveMultiple(ExtendedCacheItemInterface...$items): bool
    {
        if (\count($items)) {
            foreach ($items as $item) {
                $this->save($item);
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return '';
    }
}
