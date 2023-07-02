<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use Acms\Plugins\V2\Entities\Master\CategoryScope;
use Acms\Plugins\V2\Entities\Master\CategoryStatus;

/**
 * カテゴリー
 */
class Category
{
    public function getUrl(): string
    {
        return acmsLink([
            'bid' => $this->Blog->getId(),
            'cid' => $this->id
        ]);
    }

    /**
     * ID
     */
    private int $id;

    /**
     * コード
     */
    private string $code;

    /**
     * ステータス
     */
    private string $status = CategoryStatus::OPEN;

    /**
     * 親カテゴリーID
     */
    private ?int $categoryParent = null;

    /**
     * ソート番号
     */
    private int $sort;

    /**
     * 名前
     */
    private string $name;

    /**
     * left
     */
    private int $left;

    /**
     * right
     */
    private int $right;

    /**
     * スコープ
     */
    private string $scope = CategoryScope::LOCAL;

    /**
     * インデキシング
     */
    private string $indexing = 'on';

    /**
     *  ブログ
     */
    private Blog $Blog;

    /**
     * カテゴリーフィールド
     */
    private ?\Field $Field = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCategoryParent(): ?int
    {
        return $this->categoryParent;
    }

    public function setCategoryParent(?int $categoryParent = null): void
    {
        $this->categoryParent = $categoryParent;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): void
    {
        $this->left = $left;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function setRight(int $right): void
    {
        $this->right = $right;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope = CategoryScope::LOCAL): void
    {
        $this->scope = $scope;
    }

    public function getIndexing(): string
    {
        return $this->indexing;
    }

    public function setIndexing(string $indexing): void
    {
        $this->indexing = $indexing;
    }

    public function getBlog(): Blog
    {
        return $this->Blog;
    }

    public function setBlog(Blog $Blog): void
    {
        $this->Blog = $Blog;
    }

    public function getField(): \Field
    {
        if (is_null($this->Field)) {
            return new \Field();
        }
        return $this->Field;
    }

    public function setField(?\Field $Field = null): void
    {
        $this->Field = $Field;
    }
}
