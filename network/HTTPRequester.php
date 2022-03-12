<?php
class HTTPRequester
{
    /**
     * @description Make HTTP-GET call
     * @param $url
     * @param array $params
     * @return HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPGet($url, array $params)
    {
        $query = http_build_query($params);
        $ch = curl_init($url . '?' . $query);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        return [
            $mh,
            $ch
        ];
    }
    /**
     * @description Make HTTP-POST call
     * @param $url
     * @param array $params
     * @return HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPPost($url, array $params)
    {
        $query = http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        return [
            $mh,
            $ch
        ];
    }
    /**
     * @description Make HTTP-PUT call
     * @param $url
     * @param array $params
     * @return HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPPut($url, array $params)
    {
        $query = \http_build_query($params);
        $ch = \curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_HEADER, false);
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, 'PUT');
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        return [
            $mh,
            $ch
        ];
    }
    /**
     * @category Make HTTP-DELETE call
     * @param $url
     * @param array $params
     * @return HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPDelete($url, array $params)
    {
        $query = \http_build_query($params);
        $ch = \curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_HEADER, false);
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, 'DELETE');
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        return [
            $mh,
            $ch
        ];
    }
}
