<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use Acms\Plugins\V2\Entities\Master\BlogStatus;

/**
 * ブログ
 */
class Blog
{
    public function getUrl(): string
    {
        return acmsLink([
            'bid' => $this->id,
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
    private string $status = BlogStatus::OPEN;

    /**
     * ソート番号
     */
    private int $sort;

    /**
     * 親ブログID
     */
    private ?int $blogParent = null;

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
     * ドメイン
     */
    private string $domain;

    /**
     * 日付
     */
    private \DateTimeImmutable $generatedDatetime;

    /**
     * インデキシング
     */
    private string $indexing = 'on';

    /**
     * ブログフィールド
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

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getBlogParent(): ?int
    {
        return $this->blogParent;
    }

    public function setBlogParent(?int $blogParent = null): void
    {
        $this->blogParent = $blogParent;
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

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getGeneratedDatetime(): \DateTimeImmutable
    {
        return $this->generatedDatetime;
    }

    public function setGeneratedDatetime(\DateTimeImmutable $generatedDatetime): void
    {
        $this->generatedDatetime = $generatedDatetime;
    }

    public function getIndexing(): string
    {
        return $this->indexing;
    }

    public function setIndexing(string $indexing): void
    {
        $this->indexing = $indexing;
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
