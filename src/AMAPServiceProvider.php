<?php
/**
 * @copyright Copyright (c) 2018 Larva Information Technology Co., Ltd.
 * @link http://www.larvacent.com/
 * @license http://www.larvacent.com/license/
 */

namespace Larva\AMAP;

use Illuminate\Support\ServiceProvider;

/**
 * Class AMAPServiceProvider
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class AMAPServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('amap', function () {
            return new AMAPManage();
        });
    }
}
