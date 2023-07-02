<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

/**
 * タグ
 */
class Tag
{
    public function getUrl(): string
    {
        return acmsLink([
            'bid' => $this->Entry->getBlog()->getId(),
            'tag' => $this->name
        ]);
    }

    /**
     * 名前
     */
    private string $name;

    /**
     * ソート
     */
    private int $sort;

    /**
     * エントリー
     */
    private Entry $Entry;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getEntry(): Entry
    {
        return $this->Entry;
    }

    public function setEntry(Entry $Entry): void
    {
        $this->Entry = $Entry;
    }
}
