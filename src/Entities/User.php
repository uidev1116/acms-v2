<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use Acms\Plugins\V2\Entities\Master\UserStatus;
use Acms\Services\Facades\Storage;

/**
 * ユーザー
 */
class User
{
    public function getLargeIcon(): ?string
    {
        $path = ARCHIVES_DIR . $this->icon;
        if (1 &&
            !empty($path) &&
            Storage::isReadable($path) &&
            Storage::isFile($path)
        ) {
            $icon = normalSizeImagePath($this->icon);
            return trim(dirname($icon), '/') . '/square-' . Storage::mbBasename($icon);
        }
        return null;
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
    private string $status = UserStatus::OPEN;

    /**
     * ソート番号
     */
    private int $sort;

    /**
     * ユーザー名
     */
    private string $name;

    /**
     * メールアドレス
     */
    private string $mail;

    /**
     * URL
     */
    private string $url;

    /**
     * アイコン
     */
    private string $icon;

    /**
     * 権限
     */
    private string $auth;

    /**
     * 作成日時
     */
    private \DateTimeImmutable $generatedDatetime;

    /**
     * 更新日時
     */
    private \DateTimeImmutable $updatedDatetime;

    /**
     * インデキシング
     */
    private string $indexing = 'on';

    /**
     *  ブログ
     */
    private Blog $Blog;

    /**
     * ユーザーフィールド
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMail(): string
    {
        return $this->mail;
    }

    public function setMail(string $mail): void
    {
        $this->mail = $mail;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function setAuth(string $auth): void
    {
        $this->auth = $auth;
    }

    public function getGeneratedDatetime(): \DateTimeImmutable
    {
        return $this->generatedDatetime;
    }

    public function setGeneratedDatetime(\DateTimeImmutable $generatedDatetime): void
    {
        $this->generatedDatetime = $generatedDatetime;
    }

    public function getUpdatedDatetime(): \DateTimeImmutable
    {
        return $this->updatedDatetime;
    }

    public function setUpdatedDatetime(\DateTimeImmutable $updatedDatetime): void
    {
        $this->updatedDatetime = $updatedDatetime;
    }

    public function getIcon(): string
    {
        $path = ARCHIVES_DIR . $this->icon;
        if (1 &&
            !empty($path) &&
            Storage::isReadable($path) &&
            Storage::isFile($path)
        ) {
            return $this->icon;
        }
        return '../' . THEMES_DIR . 'system/' . IMAGES_DIR . 'usericon/user' . ($this->id % 10) . '.png';
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
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
