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
     * IP定位
     * @param string $ip
     * @return array|false 返回的经纬度是 WGS84
     */
    public function ip($ip)
    {
        $response = $this->get('/v3/ip', ['ip' => $ip]);
        if (is_array($response) && $response['status'] == 1 && $response['rectangle']) {
            $rectangle = array_map([LBSHelper::class,'GCJ02ToWGS84'], LBSHelper::getAMAPRectangle($response['rectangle']));
            $location = LBSHelper::getCenterFromDegrees($rectangle);
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
     * 逆地理位置编码 接收 WGS54坐标
     * @param float $longitude
     * @param float $latitude
     * @param string $extensions
     * @return array|false
     */
    public function regeo($longitude, $latitude, $extensions = 'base')
    {
        //WGS84 -> gcj02
        list($longitude, $latitude) = LBSHelper::WGS84ToGCJ02($longitude, $latitude);
        $response = $this->get('/v3/geocode/regeo', ['location' => $longitude . ',' . $latitude, 'extensions' => $extensions]);
        if (is_array($response) && $response['status'] == 1 && is_array($response['regeocode']['addressComponent'])) {
            if ($response['regeocode']['addressComponent']) {
                return $response['regeocode']['addressComponent'];
            }
        }
        return false;
    }
}
