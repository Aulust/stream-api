<?php

require "StreamChannelMock.php";
require "../StreamService.php";
require "../services/UstreamTv.php";

class UstreamTvTest extends PHPUnit_Framework_TestCase {
    public function testGetInfo() {
        $json_string = file_get_contents(strtr('http://api.ustream.tv/json/channel/live/search/all?key=:channel_name',
            array(':dev_key' => UstreamTv::DEV_KEY)));
        $live_channels = json_decode($json_string);

        $channels = array();
        $channels[] = $live_channels->results[0]->urlTitleName;
        $channels[] = $live_channels->results[1]->urlTitleName;

        $json_string = file_get_contents(strtr(UstreamTv::CHECK_STREAM_STATUS_URL, array(':channel_name' => $channels[0],
            ':dev_key' => UstreamTv::DEV_KEY)));
        $data = json_decode($json_string);

        //Validate json structure for single channel
        $this->assertTrue(is_object($data));
        $this->assertTrue(property_exists($data, 'results'));
        $this->assertTrue(property_exists($data->results, 'status'));
        $this->assertTrue(property_exists($data->results, 'imageUrl'));
        $this->assertTrue(property_exists($data->results, 'title'));
        $this->assertTrue(property_exists($data->results, 'description'));

        $json_string = file_get_contents(strtr(UstreamTv::CHECK_STREAM_STATUS_URL, array(':channel_name' => join(';', $channels),
            ':dev_key' => UstreamTv::DEV_KEY)));
        $data = json_decode($json_string);

        //Validate json structure for multiple channels
        $this->assertTrue(is_object($data));
        $this->assertTrue(property_exists($data, 'results'));
        $this->assertTrue(property_exists($data->results[0], 'result'));
        $this->assertTrue(property_exists($data->results[0]->result, 'status'));
        $this->assertTrue(property_exists($data->results[0]->result, 'imageUrl'));
        $this->assertTrue(property_exists($data->results[0]->result, 'title'));
        $this->assertTrue(property_exists($data->results[0]->result, 'description'));

        $streamService = new UstreamTv();
        $streamChannels = array();
        $streamChannels[] = new StreamChannelMock($channels[0]);
        $streamChannels[] = new StreamChannelMock($channels[1]);

        $info = $streamService->getInfo($streamChannels[0]);
        $this->assertTrue(is_bool($info['live']));
        $this->assertTrue($info['live']);
        $this->assertTrue(filter_var($info['thumbnail'], FILTER_VALIDATE_URL) != false);
        $this->assertTrue(array_key_exists('title', $info));
        $this->assertTrue(array_key_exists('description', $info));

        $info = $streamService->getInfoBatch($streamChannels);
        foreach($info as $value) {
            $this->assertTrue(is_bool($value['live']));
            $this->assertTrue($value['live']);
            $this->assertTrue(filter_var($value['thumbnail'], FILTER_VALIDATE_URL) != false);
            $this->assertTrue(array_key_exists('title', $value));
            $this->assertTrue(array_key_exists('description', $value));
        }
    }
}
