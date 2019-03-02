<?php
/**
 * @copyright Copyright (c) 2018 Larva Information Technology Co., Ltd.
 * @link http://www.larvacent.com/
 * @license http://www.larvacent.com/license/
 */

namespace Larva\AMAP;

use GuzzleHttp\HandlerStack;
use Larva\Supports\LBSHelper;
use Larva\Supports\Traits\HasHttpRequest;

/**
 * 高德
 */
class AMAPManage
{
    use HasHttpRequest;

    /**
     * @var float
     */
    public $timeout = 5.0;

    /**
     * @return HandlerStack
     */
    public function getHandlerStack()
    {
        $stack = HandlerStack::create();
        $middleware = new AMAPStack(config('services.amap.key'));
        $stack->push($middleware);
        return $stack;
    }

    /**
     * API路径
     * @return string
     */
    public function getBaseUri()
    {
        return 'https://restapi.amap.com';
    }

    /**
     * 坐标转换
     * @param string $locations 经度和纬度用","分割，经度在前，纬度在后，经纬度小数点后不得超过6位。多个坐标对之间用”|”进行分隔最多支持40对坐标。
     * @param string $coordSys 输入坐标系。可选值： gps、mapbar、baidu、autonavi(不进行转换)
     * @return bool|array
     */
    public function coordinateConvert($locations, $coordSys)
    {
        $parameters = ['locations' => $locations, 'coordsys' => $coordSys];
        $response = $this->get('/v3/assistant/coordinate/convert', $parameters);
        if (is_array($response) && $response['status'] == 1 && $response['locations']) {
            return array_shift($response['locations']);
        }
        return false;
    }

    /**
     * 地理编码
     * @param string $address 结构化地址信息
     * @param string $city 指定查询的城市
     * @param string $callback 回调函数
     * @return array|false
     */
    public function geo($address, $city = null, $callback = '')
    {
        $parameters = ['address' => $address, 'batch' => 'false'];
        if ($city) $parameters['city'] = $city;
        if ($callback) $parameters['callback'] = $callback;
        $response = $this->get('/v3/geocode/geo', $parameters);
        if (is_array($response) && $response['status'] == 1 && $response['geocodes']) {
            return array_shift($response['geocodes']);
        }
        return false;
    }

    /**
     * 行政区查询
     * @param string $keywords 规则：只支持单个关键词语搜索关键词支持：行政区名称、citycode、adcode    例如，在subdistrict=2，搜索省份（例如山东），能够显示市（例如济南），区（例如历下区）
     * @param int $subdistrict
     * @param string $extensions
     * @return bool|mixed
     */
    public function district($keywords, $subdistrict = 1, $extensions = 'base')
    {
        $parameters = ['keywords' => $keywords, 'subdistrict' => $subdistrict, 'extensions' => $extensions, 'offset' => 200];
        $response = $this->get('/v3/config/district', $parameters);
        if (is_array($response) && $response['status'] == 1 && $response['districts']) {
            return $response['districts'];
        }
        return false;
    }

    /**
     * IP定位
     * @param string $ip
     * @return array|false 返回的经纬度是 GCJ02
     */
    public function ip($ip)
    {
        $response = $this->get('/v3/ip', ['ip' => $ip]);
        if (is_array($response) && $response['status'] == 1 && $response['rectangle']) {
            $location = LBSHelper::getCenterFromDegrees(LBSHelper::getAMAPRectangle($response['rectangle']));
            return [
                'province' => $response['province'],
                'city' => $response['city'],
                'adcode' => $response['adcode'],
                'rectangle' => $response['rectangle'],//高德坐标系
                'lon' => $location[0],
                'lat' => $location[1]
            ];
        }
        return false;
    }

    /**
     * 逆地理位置编码 接收 GCJ02 坐标
     * @param float $longitude
     * @param float $latitude
     * @param string $extensions
     * @return array|false
     */
    public function regeo($longitude, $latitude, $extensions = 'base')
    {
        $response = $this->get('/v3/geocode/regeo', ['location' => $longitude . ',' . $latitude, 'extensions' => $extensions]);
        if (is_array($response) && $response['status'] == 1 && is_array($response['regeocode']['addressComponent'])) {
            if ($response['regeocode']['addressComponent']) {
                return $response['regeocode']['addressComponent'];
            }
        }
        return false;
    }
}
