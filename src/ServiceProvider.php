<?php

namespace Acms\Plugins\V2;

use ACMS_App;
use Acms\Services\Facades\Application as Container;
use Acms\Services\Common\InjectTemplate;
use Acms\Plugins\V2\Services\Unit\UnitFactory;

class ServiceProvider extends ACMS_App
{
    /**
     * @var string
     */
    public $version = '0.0.2';

    /**
     * @var string
     */
    public $name = 'V2';

    /**
     * @var string
     */
    public $author = 'uidev1116';

    /**
     * @var bool
     */
    public $module = false;

    /**
     * @var bool|string
     */
    public $menu = 'v2_index';

    /**
     * @var string
     */
    public $desc = 'ビルトインモジュール Ver.2.0を提供する拡張アプリです。';

    /**
     * サービスの初期処理
     */
    public function init()
    {
        $Container = Container::getInstance();

        /**
         * Register Repository
         */
        Container::singleton(
            'v2.repositry.media',
            Repositories\MediaRepository::class,
            [$Container]
        );
        Container::singleton(
            'v2.repositry.module',
            Repositories\ModuleRepository::class,
            [$Container]
        );
        Container::singleton(
            'v2.repositry.entry',
            Repositories\EntryRepository::class,
            [$Container]
        );


        /**
         * Register Service
         */
        Container::singleton('template', Services\Template\Helper::class);

        /**
         * Register Unit
         */
        $unit = UnitFactory::singleton();
        $unit->attach('text', Services\Unit\Types\TextUnit::class);
        $unit->attach('table', Services\Unit\Types\TableUnit::class);
        $unit->attach('image', Services\Unit\Types\ImageUnit::class);
        $unit->attach('file', Services\Unit\Types\FileUnit::class);
        $unit->attach('map', Services\Unit\Types\GoogleMapUnit::class);
        $unit->attach('osmap', Services\Unit\Types\OpenStreetMapUnit::class);
        $unit->attach('video', Services\Unit\Types\VideoUnit::class);
        $unit->attach('eximage', Services\Unit\Types\ExImageUnit::class);
        $unit->attach('quote', Services\Unit\Types\QuoteUnit::class);
        $unit->attach('media', Services\Unit\Types\MediaUnit::class);
        $unit->attach('rich-editor', Services\Unit\Types\RichEditorUnit::class);
        $unit->attach('break', Services\Unit\Types\BreakUnit::class);
        $unit->attach('module', Services\Unit\Types\ModuleUnit::class);
        $unit->attach('custom', Services\Unit\Types\CustomUnit::class);

        /**
         * Inject Template
         */
        $inject = InjectTemplate::singleton();
        $inject->add(
            'admin-module-config-V2_Entry_Body',
            PLUGIN_DIR . $this->name . '/template/admin/config/v2/entry/body_body.html'
        );
        $inject->add(
            'admin-module-config-V2_Entry_Summary',
            PLUGIN_DIR . $this->name . '/template/admin/config/v2/entry/summary_body.html'
        );
        $inject->add(
            'admin-module-select',
            PLUGIN_DIR . $this->name . '/template/admin/module/select.html'
        );
        if (ADMIN === 'app_' . $this->menu) {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'V2/template/admin/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'V2/template/admin/main.html');
        }
    }

    /**
     * インストールする前の環境チェック処理
     *
     * @return bool
     */
    public function checkRequirements()
    {
        return true;
    }

    /**
     * インストールするときの処理
     * データベーステーブルの初期化など
     *
     * @return void
     */
    public function install()
    {
    }

    /**
     * アンインストールするときの処理
     * データベーステーブルの始末など
     *
     * @return void
     */
    public function uninstall()
    {
    }

    /**
     * アップデートするときの処理
     *
     * @return bool
     */
    public function update()
    {
        return true;
    }

    /**
     * 有効化するときの処理
     *
     * @return bool
     */
    public function activate()
    {
        return true;
    }

    /**
     * 無効化するときの処理
     *
     * @return bool
     */
    public function deactivate()
    {
        return true;
    }
}
