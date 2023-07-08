<?php

namespace Acms\Plugins\V2\Repositories;

use DateTimeImmutable;
use Acms\Plugins\V2\Entities\Media;
use Acms\Plugins\V2\Entities\Blog;
use Acms\Plugins\V2\Entities\User;
use SQL;
use DB;

/**
 * メディアのリポジトリ
 */
class MediaRepository
{
    /**
     * 初期化処理
     *
     * @param \Acms\Services\Container
     */
    public function __construct(\Acms\Services\Container $container)
    {
    }

    /**
     * 指定したメディアIDのメディアを取得する
     * @param int[] $mids
     *
     * @return Media[]
     */
    public function findByIds(array $mids): array
    {
        return array_map(
            function (array $row) {
                return $this->createMedia($row);
            },
            DB::query($this->buildQuery($mids), 'all')
        );
    }

    /**
     * sqlの組み立て
     *
     * @param int[] $mids
     *
     * @return string
     */
    private function buildQuery(array $mids): string
    {
        $sql = SQL::newSelect('media');
        $sql->addLeftJoin('blog', 'blog_id', 'media_blog_id');
        $sql->addLeftJoin('user', 'user_id', 'media_user_id');

        $sql->addWhereIn('media_id', $mids);

        return $sql->get(dsn());
    }

    /**
     * メディアオブジェクトの組み立て
     *
     * @param array $row
     *
     * @return Media
     */
    private function createMedia(array $row): Media
    {
        $Media = new Media();
        $Media->setId(intval($row['media_id']));
        $Media->setStatus($row['media_status']);
        $Media->setPath($row['media_path']);
        $Media->setThumbnail($row['media_thumbnail']);
        $Media->setFileName($row['media_file_name']);
        $Media->setImageSize($row['media_image_size']);
        $Media->setFileSize(intval($row['media_file_size']));
        $Media->setType($row['media_type']);
        $Media->setExtension($row['media_extension']);
        $Media->setOriginal($row['media_original']);
        $Media->setUpdateDate(new DateTimeImmutable($row['media_update_date']));
        $Media->setUploadDate(new DateTimeImmutable($row['media_upload_date']));
        $Media->setCaption($row['media_field_1']);
        $Media->setLink($row['media_field_2']);
        $Media->setAlt($row['media_field_3']);
        $Media->setText($row['media_field_4']);
        $Media->setFocalPoint($row['media_field_5']);
        $Media->setPage(
            !empty($row['media_field_6']) ? intval($row['media_field_6']) : null
        );

        $Blog = new Blog();
        $Blog->setId(intval($row['blog_id']));
        $Blog->setCode($row['blog_code']);
        $Blog->setStatus($row['blog_status']);
        $Blog->setSort(intval($row['blog_sort']));
        $Blog->setBlogParent(intval($row['blog_parent']));
        $Blog->setName($row['blog_name']);
        $Blog->setLeft(intval($row['blog_left']));
        $Blog->setRight(intval($row['blog_right']));
        $Blog->setDomain($row['blog_domain']);
        $Blog->setGeneratedDatetime(new DateTimeImmutable($row['blog_generated_datetime']));
        $Blog->setIndexing($row['blog_indexing']);

        $Media->setBlog($Blog);

        $User = new User();
        $User->setId(intval($row['user_id']));
        $User->setCode($row['user_code']);
        $User->setStatus($row['user_status']);
        $User->setSort(intval($row['user_sort']));
        $User->setName($row['user_name']);
        $User->setMail($row['user_mail']);
        $User->setUrl($row['user_url']);
        $User->setIcon($row['user_icon']);
        $User->setAuth($row['user_auth']);
        $User->setGeneratedDatetime(new DateTimeImmutable($row['user_generated_datetime']));
        $User->setUpdatedDatetime(new DateTimeImmutable($row['user_updated_datetime']));
        $User->setIndexing($row['user_indexing']);

        $Media->setUser($User);

        return $Media;
    }
}
