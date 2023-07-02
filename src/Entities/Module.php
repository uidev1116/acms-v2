<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use DateTimeImmutable;
use Acms\Plugins\V2\Entities\Master\Axis;
use Acms\Plugins\V2\Entities\Master\ModuleScope;

/**
 * モジュール
 */
class Module
{
    /**
     * モジュールID
     *
     * @var int
     */
    private $id;

    /**
     * モジュールID名
     *
     * @var string
     */
    private $identifier;

    /**
     * モジュール名
     *
     * @var string
     */
    private $name;

    /**
     * ラベル
     *
     * @var string
     */
    private $label;

    /**
     * 説明文
     *
     * @var string
     */
    private $description;

    /**
     * ステータス
     *
     * @var string
     */
    private $status;

    /**
     * スコープ
     *
     * @var string
     */
    private $scope = ModuleScope::LOCAL;

    /**
     * キャッシュ
     *
     * @var string
     */
    private $cache;

    /**
     * 引数（ブログID）
     *
     * @var int|string|null
     */
    private $bid = null;

    /**
     * 階層（ブログID）
     *
     * @var string
     */
    private $bidAxis = Axis::SELF;

    /**
     * 引数（ユーザーID）
     *
     * @var int|string|null
     */
    private $uid = null;

    /**
     * 引数のスコープ（ユーザーID）
     *
     * @var string
     */
    private $uidScope = ModuleScope::LOCAL;

    /**
     * 引数（カテゴリーID）
     *
     * @var int|string|null
     */
    private $cid = null;

    /**
     * 階層（カテゴリーID）
     *
     * @var string
     */
    private $cidAxis = Axis::SELF;

    /**
     * 引数のスコープ（カテゴリーID）
     *
     * @var string
     */
    private $cidScope = ModuleScope::LOCAL;

    /**
     * 引数（エントリーID）
     *
     * @var int|string|null
     */
    private $eid = null;

    /**
     * 引数のスコープ（エントリーID）
     *
     * @var string
     */
    private $eidScope = ModuleScope::LOCAL;

    /**
     * 引数（キーワード）
     *
     * @var string|null
     */
    private $keyword = null;

    /**
     * 引数のスコープ（キーワード）
     *
     * @var string
     */
    private $keywordScope = ModuleScope::LOCAL;

    /**
     * 引数（タグ）
     *
     * @var string|null
     */
    private $tag = null;

    /**
     * 引数のスコープ（タグ）
     *
     * @var string
     */
    private $tagScope = ModuleScope::LOCAL;

    /**
     * 引数（フィールド）
     *
     * @var string|null
     */
    private $field = null;

    /**
     * 引数のスコープ（フィールド）
     *
     * @var string
     */
    private $fieldScope = ModuleScope::LOCAL;

    /**
     * 引数（開始日時）
     *
     * @var DatetimeImutable|null
     */
    private $start = null;

    /**
     * 引数のスコープ（開始日時）
     *
     * @var string
     */
    private $startScope = ModuleScope::LOCAL;

    /**
     * 引数（終了日時）
     *
     * @var DatetimeImutable|null
     */
    private $end = null;

    /**
     * 引数のスコープ（終了日時）
     *
     * @var string
     */
    private $endScope = ModuleScope::LOCAL;

    /**
     * 引数（ページ）
     *
     * @var int|null
     */
    private $page = null;

    /**
     * 引数のスコープ（ページ）
     *
     * @var string
     */
    private $pageScope = ModuleScope::LOCAL;

    /**
     * 引数（表示順）
     *
     * @var int|null
     */
    private $order = null;

    /**
     * 引数のスコープ（表示順）
     *
     * @var string
     */
    private $orderScope = ModuleScope::LOCAL;

    /**
     * カスタムフィールド
     *
     * @var string
     */
    private $customField = 'off';

    /**
     * レイアウト/ユニット
     *
     * @var string
     */
    private $layoutUse = 'off';

    /**
     * API
     *
     * @var string
     */
    private $apiUse = 'off';

    /**
     * ブログ
     *
     * @var Blog
     */
    private $Blog;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope)
    {
        $this->scope = $scope;
    }

    public function getCache(): int
    {
        return $this->cache;
    }

    public function setCache(int $cache): void
    {
        $this->cache = $cache;
    }

    public function getBid(): int|string|null
    {
        return $this->bid;
    }

    public function setBid(int|string|null $bid): void
    {
        $this->bid = $bid;
    }

    public function getBidAxis(): string
    {
        return $this->bidAxis;
    }

    public function setBidAxis(string $bidAxis): void
    {
        $this->bidAxis = $bidAxis;
    }

    public function getUid(): int|string|null
    {
        return $this->uid;
    }

    public function setUid(int|string|null $uid): void
    {
        $this->uid = $uid;
    }

    public function getUidScope(): string
    {
        return $this->uidScope;
    }

    public function setUidScope(string $uidScope): void
    {
        $this->uidScope = $uidScope;
    }

    public function getCid(): int|string|null
    {
        return $this->cid;
    }

    public function setCid(int|string|null $cid): void
    {
        $this->cid = $cid;
    }

    public function getCidAxis(): string
    {
        return $this->cidAxis;
    }

    public function setCidAxis(string $cidAxis): void
    {
        $this->cidAxis = $cidAxis;
    }

    public function getCidScope(): string
    {
        return $this->cidScope;
    }

    public function setCidScope(string $cidScope): void
    {
        $this->cidScope = $cidScope;
    }

    public function getEid(): int|string|null
    {
        return $this->eid;
    }

    public function setEid(int|string|null $eid): void
    {
        $this->eid = $eid;
    }

    public function getEidScope(): string
    {
        return $this->eidScope;
    }

    public function setEidScope(string $eidScope): void
    {
        $this->eidScope = $eidScope;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getKeywordScope(): string
    {
        return $this->keywordScope;
    }

    public function setKeywordScope(string $keywordScope): void
    {
        $this->keywordScope = $keywordScope;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getTagScope(): string
    {
        return $this->tagScope;
    }

    public function setTagScope(string $tagScope): void
    {
        $this->tagScope = $tagScope;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(?string $field): void
    {
        $this->field = $field;
    }

    public function getFieldScope(): string
    {
        return $this->fieldScope;
    }

    public function setFieldScope(string $fieldScope): void
    {
        $this->fieldScope = $fieldScope;
    }

    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(?DateTimeImmutable $start): void
    {
        $this->start = $start;
    }

    public function getStartScope(): string
    {
        return $this->startScope;
    }

    public function setStartScope(string $startScope): void
    {
        $this->startScope = $startScope;
    }

    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(?DateTimeImmutable $end): void
    {
        $this->end = $end;
    }

    public function getEndScope(): string
    {
        return $this->endScope;
    }

    public function setEndScope(string $endScope): void
    {
        $this->endScope = $endScope;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): void
    {
        $this->page = $page;
    }

    public function getPageScope(): string
    {
        return $this->pageScope;
    }

    public function setPageScope(string $pageScope): void
    {
        $this->pageScope = $pageScope;
    }


    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function setOrder(?string $order): void
    {
        $this->order = $order;
    }

    public function getOrderScope(): string
    {
        return $this->orderScope;
    }

    public function setOrderScope(string $orderScope): void
    {
        $this->orderScope = $orderScope;
    }

    public function getCustomField(): string
    {
        return $this->customField;
    }

    public function setCustomField(string $customField): void
    {
        $this->customField = $customField;
    }

    public function getLayoutUse(): string
    {
        return $this->layoutUse;
    }

    public function setLayoutUse(string $layoutUse): void
    {
        $this->layoutUse = $layoutUse;
    }

    public function getApiUse(): string
    {
        return $this->apiUse;
    }

    public function setApiUse(string $apiUse): void
    {
        $this->apiUse = $apiUse;
    }

    public function getBlog(): Blog
    {
        return $this->Blog;
    }

    public function setBlog(Blog $Blog): void
    {
        $this->Blog = $Blog;
    }
}
