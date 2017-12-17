<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller
{
    /**
     * @var string 用户的openID
     */
    private  $clientID="";
    /**
     * @var string 开发者的微信号
     */
    private  $serverID="";

    /**
     * @FUNCTION token 验证的函数
     * @DATE     2017-12-16
     */
    public function token()
    {
        $nonce = $_GET['nonce'];
        $token = 'weixin';
        $timestamp = $_GET['timestamp'];
        $echostr = $_GET['echostr'];
        $signature = $_GET['signature'];
        //形成数组，然后按字典序排序
        $array = array();
        $array = array($nonce, $timestamp, $token);
        sort($array);
        //拼接成字符串,sha1加密 ，然后与signature进行校验
        $str = sha1(implode($array));
        if ($str == $signature && $echostr) {
            //第一次接入weixin api接口的时候
            echo $echostr;
            exit;
        }
    }

    /**
     * @FUNCTION 主要的请求地址，以及进行对应命令的操作
     * @DATE     2017-12-16
     */
    public function index()
    {
        if(!isset($GLOBALS["HTTP_RAW_POST_DATA"])){
            //echo "It works";
            $this->send_image_textMsg($this->get_toutiao());
            exit;
        }else{
            $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
            $keyword = $this->parse_message($postStr);
            switch($keyword){
                case "糗事百科":
                    $this->send_textMsg($this->get_qiubai());
                    break;
                case "今日头条":
                    $this->send_image_textMsg($this->get_toutiao());
                    break;
                case "醉月老哥的微信助手":
                    $this->send_image_textMsg(array(array(
                        'Title'=>'醉月老哥的微信助手',
                        'Description'=>'立志做一个对接全网内容的助手,其内容包括但不限于:糗事百科，今日头条，简书，MSDN，php.net，百度翻译，搜索引擎。争取做到微信在手，天下我有！有好的想法或者建议欢迎联系我！',
                        'picUrl'=>'https://www.github.com/thinkmoon/pic/raw/master/%E5%B0%8F%E4%B9%A6%E5%8C%A0/%E9%86%89%E6%9C%88%E8%80%81%E5%93%A5%E7%9A%84%E5%BE%AE%E4%BF%A1%E5%8A%A9%E6%89%8B%20900%C3%97500px.jpg',
                        'Url'=>'https://github.com/thinkmoon/blog/blob/master/%E9%86%89%E6%9C%88%E8%80%81%E5%93%A5%E7%9A%84%E5%BE%AE%E4%BF%A1%E5%8A%A9%E6%89%8B.md')));
                    break;
                default:
                    //只是为了好看
                    $this->send_textMsg(
                        "亲,我不会别的啦,
                        回复'糗事百科'返回一条糗事
                        回复'今日头条'查看今日头条
                        ");
            }
        }
    }

    /**
     * @param string $content 待发送的内容
     *
     * @FUNCTION 发送一个文本消息
     * @DATE     2017-12-16
     */
    public function send_textMsg($content="")
    {
        $textMsg_Tpl="<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime><![CDATA[%s]]></CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content>%s</Content>
                   </xml>";
        $textMsg=sprintf($textMsg_Tpl, $this->clientID, $this->serverID, time(), $content);
        echo $textMsg;
        runLog("发送文本消息",$textMsg);
    }

    /**
     * @param array $items 待装入的图文数据
     *
     * @FUNCTION 发送图文消息
     * @DATE     2017-12-17
     */
    public function send_image_textMsg($items=array(array('Title'=>'Title','Description'=>'Description','picUrl'=>'picUrl','Url'=>'mp.thinkmoon.cn')))
    {
        $image_textMsg_Tpl="<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>%s</ArticleCount>
                        <Articles>%s</Articles>
                        </xml>";
        $Articles_Tpl="<item>
                          <Title><![CDATA[%s]]></Title>
                          <Description><![CDATA[%s]]></Description>
                          <PicUrl><![CDATA[%s]]></PicUrl>
                          <Url><![CDATA[%s]]></Url>
                       </item>";
        $Articles="";
        foreach($items as $item){
            $Article=sprintf($Articles_Tpl, $item['Title'], $item['Description'], $item['picUrl'], $item['Url']);
            $Articles=$Articles.$Article;
        }
        $image_textMsg=sprintf($image_textMsg_Tpl, $this->clientID, $this->serverID, time(), count($items),$Articles);
        echo $image_textMsg;
        runLog("发送图文消息",$image_textMsg);
    }
    /**
     * @param $postStr 用户POST的源数据字符串
     *
     * @return string 用户触发的关键字
     * @FUNCTION 解析一条消息，将对应的数据进行装载和返回
     * @DATE     2017-12-16
     */
    public function parse_message($postStr)
    {
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->clientID = $postObj->FromUserName;
        $this->serverID = $postObj->ToUserName;
        $content = trim(($postObj->Content));
        return $content;
    }

    /**
     * @return string 该条糗事百科的内容
     * @FUNCTION 获取一条糗事百科
     * @DATE     2017-12-16
     */
    public function get_qiubai()
    {
        $contentStr = file_get_contents("http://m2.qiushibaike.com/article/list/suggest?page=" . rand(1, 100) . "&type=refresh&count=30");
        $data = json_decode($contentStr);
        $items = $data->items;
        $site = rand(1, 29);
        $str = "第" . $site . "条糗百\r\n" . $items[$site]->content;
        return $str;
    }

    /**
     * @return array 待封装的图文数据
     * @FUNCTION 获取今日头条热门内容
     * @DATE     2017-12-17
     */
    public function get_toutiao(){
        $contentStr = file_get_contents('https://www.toutiao.com/api/pc/hot_gallery/?widen=1');
      	$data = json_decode($contentStr);
        $items = $data->data;
      $result=array();$i=0;
      foreach($items as $item){
        $result[$i]['Title']=$item->title;
        $result[$i]['Description']=$item->title;
        $result[$i]['picUrl']='http:'.$item->cover_image_url;
        $result[$i]['Url']='https://www.toutiao.com/'.$item->article_url;
        $i++;
      }
      return $result;
    }

}
