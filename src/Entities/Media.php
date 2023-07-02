<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use Acms\Services\Facades\Media as MediaService;

use Acms\Plugins\V2\Entities\Master\MediaType;

/**
 * メディア
 */
class Media
{
    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        if (empty($this->getImageSize())) {
            return null;
        }

        $width = explode('x', $this->getImageSize())[0];
        return intval(trim($width), 10);
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        if (empty($this->getImageSize())) {
            return null;
        }

        $height = explode('x', $this->getImageSize())[1];
        return intval(trim($height), 10);
    }

    /**
     * @return int|null
     */
    public function getRatio(): ?float
    {
        if (empty($this->getImageSize())) {
            return null;
        }

        $width = $this->getWidth();
        $height = $this->getHeight();

        if ($width === 0 || $height === 0) {
            return null;
        }

        return $width / $height;
    }

    /**
     * @return int|null
     */
    public function getFocalX(): ?int
    {
        if (!strpos($this->getFocalPoint(), ',')) {
            return null;
        }

        $focalX = explode(',', $this->getFocalPoint())[0];

        if (empty($focalX)) {
            return null;
        }

        return ($focalX / 50) - 1;
    }

    /**
     * @return int|null
     */
    public function getFocalY(): ?int
    {
        if (!strpos($this->getFocalPoint(), ',')) {
            return null;
        }

        $focalY = explode(',', $this->getFocalPoint())[1];

        if (empty($focalY)) {
            return null;
        }

        return (($focalY / 50) - 1) * -1;
    }

    /**
     * メディアID
     *
     * @var int
     */
    private $id;

    /**
     * ブログ
     *
     * @var Blog
     */
    private $Blog;

    /**
     * ユーザー
     *
     * @var User
     */
    private $User;

    /**
     * ステータス
     *
     * @var string
     */
    private $status;

    /**
     * 画像のパス
     *
     * @var string
     */
    private $path;

    /**
     * サムネイル画像のパス
     *
     * @var string
     */
    private $thumbnail;

    /**
     * ファイル名
     *
     * @var string
     */
    private $fileName;

    /**
     * サイズ
     *
     * @var string
     */
    private $imageSize;

    /**
     * ファイルサイズ
     *
     * @var int
     */
    private $fileSize;

    /**
     * ファイルタイプ
     *
     * @var string
     */
    private $type;

    /**
     * 拡張子
     *
     * @var string
     */
    private $extension;

    /**
     * 元画像のパス
     *
     * @var string
     */
    private $original;

    /**
     * 更新日時
     *
     * @var \DateTimeImmutable
     */
    private $updateDate;

    /**
     * アップロード日時
     *
     * @var \DateTimeImmutable
     */
    private $uploadDate;

    /**
     * キャプション
     *
     * @var string
     */
    private $caption;

    /**
     * リンク
     *
     * @var string
     */
    private $link;

    /**
     * altテキスト
     *
     * @var string
     */
    private $alt;

    /**
     * テキスト
     *
     * @var string
     */
    private $text;

    /**
     * PDFページ
     *
     * @var int
     */
    private $page;

    /**
     * 座標
     *
     * @var string
     */
    private $focalPoint;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getBlog(): Blog
    {
        return $this->Blog;
    }

    public function setBlog(Blog $Blog): void
    {
        $this->Blog = $Blog;
    }

    public function getUser(): User
    {
        return $this->User;
    }

    public function setUser(User $User): void
    {
        $this->User = $User;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function getThumbnail(): string
    {
        switch ($this->type) {
            case MediaType::IMAGE:
                return MediaService::getImageThumbnail($this->path);
            case MediaType::SVG:
                return MediaService::getSvgThumbnail($this->path);
            case MediaType::FILE:
                if (strtolower($this->extension) === 'pdf' && !empty($this->thumbnail)) {
                    return MediaService::getPdfThumbnail($this->thumbnail);
                }
                return MediaService::getFileThumbnail($this->extension);
            default:
                return $this->thumbnail;
        }
    }

    public function setThumbnail(string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getImageSize(): string
    {
        return $this->imageSize;
    }

    public function setImageSize(string $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getOriginal(): string
    {
        return $this->original;
    }

    public function setOriginal(string $original): void
    {
        $this->original = $original;
    }

    public function getUpdateDate(): ?\DateTimeImmutable
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTimeImmutable $updateDate): void
    {
        $this->updateDate = $updateDate;
    }

    public function getUploadDate(): \DateTimeImmutable
    {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTimeImmutable $uploadDate): void
    {
        $this->uploadDate = $uploadDate;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): void
    {
        $this->alt = $alt;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): void
    {
        $this->page = $page;
    }

    public function getFocalPoint(): ?string
    {
        return $this->focalPoint;
    }

    public function setFocalPoint(?string $focalPoint): void
    {
        $this->focalPoint = $focalPoint;
    }
}
