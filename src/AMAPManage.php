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
     * @return array|false
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
                'rectangle' => $response['rectangle'],
                'lon' => $location[0],
                'lat' => $location[1]
            ];
        }
        return false;
    }

    /**
     * 逆地理位置编码
     * @param float $lat 维度
     * @param float $lon 精度
     * @param string $extensions
     * @return array|false
     */
    public function regeo($lat, $lon, $extensions = 'base')
    {
        $response = $this->get('/v3/geocode/regeo', ['location' => $lon . ',' . $lat, 'extensions' => $extensions]);
        if (is_array($response) && $response['status'] == 1 && is_array($response['regeocode']['addressComponent'])) {
            if ($response['regeocode']['addressComponent']) {
                return $response['regeocode']['addressComponent'];
            }
        }
        return false;
    }
}
