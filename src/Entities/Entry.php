<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use Acms\Services\Facades\Common;
use Acms\Services\Facades\RichEditor;
use Acms\Plugins\V2\Entities\Master\EntryApproval;
use Acms\Plugins\V2\Entities\Master\EntryStatus;
use Acms\Plugins\V2\Entities\Master\UnitType;
use Acms\Plugins\V2\Entities\Master\UnitAlign;

/**
 * エントリー
 */
class Entry
{
    public function getPrefixedTitle(): string
    {
        return (!IS_LICENSED ? '[test]' : '') . addPrefixEntryTitle(
            $this->title,
            $this->status,
            $this->startDatetime->format('Y-m-d H:i:s'),
            $this->endDatetime->format('Y-m-d H:i:s'),
            $this->approval
        );
    }

    public function getUrl(): string
    {
        if (!empty($this->link)) {
            return $this->link;
        }

        return acmsLink([
            'bid' => $this->Blog->getId(),
            'cid' => !is_null($this->Category) ? $this->Category->getId() : null,
            'eid' => $this->id
        ]);
    }

    public function getParmalink(): string
    {
        return acmsLink([
            'bid' => $this->Blog->getId(),
            'cid' => !is_null($this->Category) ? $this->Category->getId() : null,
            'eid' => $this->id
        ], false);
    }

    public function getSummary(): string
    {
        return array_reduce(
            $this->Units,
            function (string $summary, Unit $Unit) {
                if ($Unit->isHidden()) {
                    return $summary;
                }
                if (!in_array($Unit->getType(), [UnitType::TEXT, UnitType::RICH_EDITOR], true)) {
                    return $summary;
                }

                $text = '';
                if ($Unit->getType() === UnitType::TEXT) {
                    if ($Unit->getField2() === 'markdown') {
                        $text = Common::parseMarkdown($Unit->getField1());
                    } elseif ($Unit->getField2() === 'table') {
                        (new \ACMS_Corrector())->table($Unit->getField1());
                    } else {
                        $text = $Unit->getField1();
                    }

                    $text = preg_replace('@\s+@u', ' ', strip_tags($text));
                }
                if ($Unit->getType() === UnitType::RICH_EDITOR) {
                    $text = strip_tags(RichEditor::render($Unit->getField1()));
                }

                return $summary . $text . ' ';
            },
            ''
        );
    }

    public function hasContinue(): bool
    {
        return !is_null($this->summaryRange) && $this->summaryRange < count($this->Units);
    }

    public function getMicroPageAmount(): int
    {
        return count($this->getUnitsSplitedByPage());
    }

    public function getMicroPageUrl(int $page)
    {
        return acmsLink([
            'bid' => $this->Blog->getId(),
            'cid' => !is_null($this->Category) ? $this->Category->getId() : null,
            'eid' => $this->id,
            'page' => $page
        ]);
    }

    /**
     * ページ分割されたユニットを取得する
     *
     * @return Unit[]
     */
    public function getUnitsSplitedByPage(): array
    {
        return array_reduce(
            $this->Units,
            function (array $data, Unit $Unit) {
                $last = array_key_last($data);
                return [
                    ...array_map(
                        function (int $i, array $Units) use ($last, $Unit) {
                            return $i === $last ? [...$Units, $Unit] : $Units;
                        },
                        array_keys($data),
                        array_values($data)
                    ),
                    ...($Unit->getType() === UnitType::BREAK ? [[]] : [])
                ];
            },
            [[]]
        );
    }

    public function isFormEnable(): bool
    {
        return $this->formId > 0 && $this->formStatus === 'open';
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
    private string $status = EntryStatus::OPEN;

    /**
     * 承認ステータス
     */
    private string $approval = EntryApproval::NONE;

    /**
     * フォームステータス
     */
    private string $formStatus;

    /**
     * ソート番号
     */
    private int $sort;

    /**
     * ユーザーのソート番号
     */
    private int $userSort;

    /**
     * カテゴリーのソート番号
     */
    private int $categorySort;

    /**
     * タイトル
     */
    private string $title;

    /**
     * リンク
     */
    private string $link;

    /**
     * 日付
     */
    private \DateTimeImmutable $datetime;

    /**
     * 公開日時
     */
    private \DateTimeImmutable $startDatetime;

    /**
     * 掲載終了日時
     */
    private \DateTimeImmutable $endDatetime;

    /**
     * 作成日時
     */
    private \DateTimeImmutable $postedDatetime;

    /**
     * 更新日時
     */
    private \DateTimeImmutable $updatedDatetime;

    /**
     * 概要
     */
    private ?int $summaryRange = null;

    /**
     * インデキシング
     */
    private string $indexing = 'on';

    /**
     *  メインイメージ
     */
    private ?Unit $primaryImage = null;

    /**
     * リビジョンID
     */
    private int $currentRevId;

    /**
     * 最後に更新したユーザーID
     */
    private User $LastUpdateUser;

    /**
     *  ハッシュ
     */
    private string $hash;

    /**
     *  カテゴリー
     */
    private ?Category $Category = null;

    /**
     *  ユーザー
     */
    private User $User;

    /**
     *  フォームID
     */
    private int $formId;

    /**
     *  ブログ
     */
    private Blog $Blog;

    /**
     *  削除したユーザー
     */
    private ?User $DeleteUser = null;

    /**
     * エントリーフィールド
     */
    private ?\Field $Field = null;

    /**
     * 位置情報
     */
    private ?Geo $Geo = null;

    /**
     * ユニット
     *
     * @var Unit[]
     */
    private array $Units = [];

    /**
     * サブカテゴリー
     *
     * @var Category[]
     */
    private array $SubCategories = [];

    /**
     * タグ
     *
     * @var Tag[]
     */
    private array $Tags = [];

    /**
     * 関連エントリーグループ
     *
     * @var RelatedEntryGroup[]
     */
    private array $RelatedEntryGroups = [];


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

    public function getApproval(): string
    {
        return $this->approval;
    }

    public function setApproval(string $approval): void
    {
        $this->approval = $approval;
    }

    public function getFormStatus(): string
    {
        return $this->formStatus;
    }

    public function setFormStatus(string $formStatus): void
    {
        $this->formStatus = $formStatus;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getUserSort(): int
    {
        return $this->userSort;
    }

    public function setUserSort(int $userSort): void
    {
        $this->userSort = $userSort;
    }

    public function getCategorySort(): int
    {
        return $this->categorySort;
    }

    public function setCategorySort(int $categorySort): void
    {
        $this->categorySort = $categorySort;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getDatetime(): \DateTimeImmutable
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeImmutable $datetime): void
    {
        $this->datetime = $datetime;
    }

    public function getStartDatetime(): \DateTimeImmutable
    {
        return $this->startDatetime;
    }

    public function setStartDatetime(\DateTimeImmutable $startDatetime): void
    {
        $this->startDatetime = $startDatetime;
    }

    public function getEndDatetime(): \DateTimeImmutable
    {
        return $this->endDatetime;
    }

    public function setEndDatetime(\DateTimeImmutable $endDatetime): void
    {
        $this->endDatetime = $endDatetime;
    }

    public function getPostedDatetime(): \DateTimeImmutable
    {
        return $this->postedDatetime;
    }

    public function setPostedDatetime(\DateTimeImmutable $postedDatetime): void
    {
        $this->postedDatetime = $postedDatetime;
    }

    public function getUpdatedDatetime(): \DateTimeImmutable
    {
        return $this->updatedDatetime;
    }

    public function setUpdatedDatetime(\DateTimeImmutable $updatedDatetime): void
    {
        $this->updatedDatetime = $updatedDatetime;
    }

    public function getSummaryRange(): ?int
    {
        return $this->summaryRange;
    }

    public function setSummaryRange(?int $summaryRange = null): void
    {
        $this->summaryRange = $summaryRange;
    }

    public function getIndexing(): string
    {
        return $this->indexing;
    }

    public function setIndexing(string $indexing): void
    {
        $this->indexing = $indexing;
    }

    public function getPrimaryImage(): ?Unit
    {
        return $this->primaryImage;
    }

    public function setPrimaryImage(?Unit $primaryImage = null): void
    {
        $this->primaryImage = $primaryImage;
    }

    public function getCurrentRevId(): int
    {
        return $this->currentRevId;
    }

    public function setCurrentRevId(int $currentRevId): void
    {
        $this->currentRevId = $currentRevId;
    }

    public function getLastUpdateUser(): User
    {
        return $this->LastUpdateUser;
    }

    public function setLastUpdateUser(User $LastUpdateUser): void
    {
        $this->LastUpdateUser = $LastUpdateUser;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getCategory(): ?Category
    {
        return $this->Category;
    }

    public function setCategory(?Category $Category = null): void
    {
        $this->Category = $Category;
    }

    public function getUser(): User
    {
        return $this->User;
    }

    public function setUser(User $User): void
    {
        $this->User = $User;
    }

    public function getFormId(): int
    {
        return $this->formId;
    }

    public function setFormId(int $formId): void
    {
        $this->formId = $formId;
    }

    public function getBlog(): Blog
    {
        return $this->Blog;
    }

    public function setBlog(Blog $Blog): void
    {
        $this->Blog = $Blog;
    }

    public function getDeleteUser(): ?User
    {
        return $this->DeleteUser;
    }

    public function setDeleteUser(?User $DeleteUser = null): void
    {
        $this->DeleteUser = $DeleteUser;
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

    public function getGeo(): ?Geo
    {
        return $this->Geo;
    }

    public function setGeo(?Geo $Geo = null): void
    {
        $this->Geo = $Geo;
    }

    /**
     * @return Unit[]
     */
    public function getUnits(): array
    {
        return $this->Units;
    }

    /**
     * @param Unit[]
     *
     * @return void
     */
    public function setUnits(array $Units): void
    {
        $this->Units = $Units;
    }

    /**
     * @return Category[]
     */
    public function getSubCategories(): array
    {
        return $this->SubCategories;
    }

    /**
     * @param Category[]
     *
     * @return void
     */
    public function setSubCategories(array $SubCategories): void
    {
        $this->SubCategories = $SubCategories;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->Tags;
    }

    /**
     * @param Tag[]
     *
     * @return void
     */
    public function setTags(array $Tags): void
    {
        $this->Tags = $Tags;
    }

    /**
     * @return RelatedEntryGroup[]
     */
    public function getRelatedEntryGroups(): array
    {
        return $this->RelatedEntryGroups;
    }

    /**
     * @param RelatedEntryGroup[]
     *
     * @return void
     */
    public function setRelatedEntryGroups(array $RelatedEntryGroups): void
    {
        $this->RelatedEntryGroups = $RelatedEntryGroups;
    }
}
