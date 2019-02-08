<?php
#app/Http/Controller/Modules/Cms/Content.php
namespace App\Http\Controllers\Modules\Cms;

use App\Models\Config;
use App\Models\Modules\Cms\CmsCategory;
use App\Models\Modules\Cms\CmsCategoryDescription;
use App\Models\Modules\Cms\CmsContent;
use App\Models\Modules\Cms\CmsContentDescription;
use App\Models\Modules\Cms\CmsImage;
use Encore\Admin\Auth\Database\Menu;

class Content extends \App\Http\Controllers\GeneralController
{
    protected $configKey  = 'Content';
    protected $configCode = 'Cms';
    public $title;
    const ON  = 1;
    const OFF = 0;
    public function __construct()
    {
        parent::__construct();
        $this->title = trans('Modules/' . $this->configCode . '/' . $this->configKey . '.title');

    }
    public function getData()
    {
        return $this->processData();
    }

    public function processData()
    {
        $arrData = [
            'title' => $this->title,
            'code'  => $this->configKey,
        ];
        return $arrData;
    }

    public function install()
    {
        $return = ['error' => 0, 'msg' => ''];
        $check  = Config::where('key', $this->configKey)->first();
        if ($check) {
            $return = ['error' => 1, 'msg' => 'Module exist'];
        } else {
            $process = Config::insert(
                [
                    'code'   => $this->configCode,
                    'key'    => $this->configKey,
                    'value'  => self::ON, //1- Enable extension; 0 - Disable
                    'detail' => 'Modules/' . $this->configCode . '/' . $this->configKey . '.title',
                ]
            );
            if (!$process) {
                $return = ['error' => 1, 'msg' => 'Error when install'];
            } else {
                $checkMenu = Menu::find('100');
                if (!$checkMenu) {
                    Menu::insert([
                        'id'        => 100,
                        'order'     => 9,
                        'parent_id' => 0,
                        'title'     => 'CMS Manager',
                        'icon'      => 'fa-coffee',
                    ]);
                }
                (new CmsCategory)->installExtension();
                (new CmsCategoryDescription)->installExtension();
                (new CmsContent)->installExtension();
                (new CmsContentDescription)->installExtension();
                (new CmsImage)->installExtension();
                Menu::insert(
                    [
                        'order'     => 10,
                        'parent_id' => 100,
                        'title'     => 'Cms categories',
                        'icon'      => 'fa-folder-open-o',
                        'uri'       => 'modules/cms/cms_category',
                    ]);
                Menu::insert(
                    [
                        'order'     => 10,
                        'parent_id' => 100,
                        'title'     => 'Cms contents',
                        'icon'      => 'fa-copy',
                        'uri'       => 'modules/cms/cms_content',
                    ]
                );
            }
        }
        return $return;
    }

    public function uninstall()
    {
        $return  = ['error' => 0, 'msg' => ''];
        $process = (new Config)->where('key', $this->configKey)->delete();
        if (!$process) {
            $return = ['error' => 1, 'msg' => 'Error when uninstall'];
        }
        (new CmsCategory)->uninstallExtension();
        (new CmsCategoryDescription)->uninstallExtension();
        (new CmsContent)->uninstallExtension();
        (new CmsContentDescription)->uninstallExtension();
        (new CmsImage)->uninstallExtension();
        //Remove menu
        (new Menu)->where('uri', 'modules/cms/cms_category')->delete();
        (new Menu)->where('uri', 'modules/cms/cms_content')->delete();
        if (!(new Menu)->where('parent_id', 100)->count()) {
            (new Menu)->find(100)->delete();
        }

        return $return;
    }
    public function enable()
    {
        $return  = ['error' => 0, 'msg' => ''];
        $process = (new Config)->where('key', $this->configKey)->update(['value' => self::ON]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => 'Error enable'];
        }
        return $return;
    }
    public function disable()
    {
        $return  = ['error' => 0, 'msg' => ''];
        $process = (new Config)->where('key', $this->configKey)->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => 'Error disable'];
        }
        return $return;
    }

    public function config()
    {
        //Process
    }
    public function processConfig($data)
    {
        //Process
    }

//=======================

/**
 * [news description]
 * @return [type] [description]
 */
    public function category($name, $id)
    {
        $category_currently = CmsCategory::find($id);
        $entries            = (new CmsCategory)->getContentsToCategory($id, $limit = $this->configs['product_new'], $opt = 'paginate');
        return view($this->theme . '.cms_category',
            array(
                'title'       => $category_currently->title,
                'description' => $category_currently['description'],
                'keyword'     => $category_currently['keyword'],
                'entries'     => $entries,
                'og_image'    => $this->logo,
            )
        );
    }

    public function content($name, $id)
    {
        $entry_currently = CmsContent::find($id);
        if ($entry_currently) {
            $title = ($entry_currently) ? $entry_currently->title : trans('language.not_found');
            return view($this->theme . '.cms_entry_detail',
                array(
                    'title'           => $title,
                    'entry_currently' => $entry_currently,
                    'description'     => $entry_currently['description'],
                    'keyword'         => $entry_currently['keyword'],
                    'og_image'        => url($this->path_file . '/' . $entry_currently->image),
                )
            );
        } else {
            return view($this->theme . '.notfound',
                array(
                    'title'       => trans('language.not_found'),
                    'description' => '',
                    'keyword'     => $this->configsGlobal['keyword'],
                )
            );
        }

    }
}
