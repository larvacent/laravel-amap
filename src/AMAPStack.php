<?php
/**
 * @copyright Copyright (c) 2018 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larvacent.com/
 * @license http://www.larvacent.com/license/
 */

namespace Larva\AMAP;

use Psr\Http\Message\RequestInterface;

/**
 * Class AMAPStack
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class AMAPStack
{

    /** @var array Configuration settings */
    private $key = '';

    /**
     * AMAPStack constructor.
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Called when the middleware is handled.
     *
     * @param callable $handler
     *
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {
            $request = $this->onBefore($request);
            return $handler($request, $options);
        };
    }

    /**
     * 请求前调用
     * @param RequestInterface $request
     * @return RequestInterface
     */
    private function onBefore(RequestInterface $request)
    {
        if ($request->getMethod() == 'POST') {
            $params = [];
            parse_str($request->getBody()->getContents(), $params);
        } else {
            $params = \GuzzleHttp\Psr7\parse_query($request->getUri()->getQuery());
        }

        $params['key'] = $this->key;
        $params['output'] = 'JSON';

        $body = http_build_query($params, '', '&');
        if ($request->getMethod() == 'POST') {
            $request = \GuzzleHttp\Psr7\modify_request($request, ['body' => $body]);
        } else {
            $request = \GuzzleHttp\Psr7\modify_request($request, ['query' => $body]);
        }
        return $request;
    }
}