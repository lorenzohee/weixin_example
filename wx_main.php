<?php
require("wx_video_mysql_test.php");
//change to your token
define("TOKEN", "mytoken");
$wechatObj = new wechatCallbackapiTest();

if (isset($_GET['echostr'])) {
	$wechatObj->valid();
}else{
	$wechatObj->responseMsg();
}

class wechatCallbackapiTest
{

	public function valid() {
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			header('content-type:text');
			echo $echoStr;
			exit;
		}
	}
	private function checkSignature() {
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		
		if($tmpStr == $signature) {
			return true;
		}else {
			return false;
		}
	}
	public function responseMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

		if (!empty($postStr)){
			$this->logger("R \r\n".$postStr);
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			$RX_TYPE = trim($postObj->MsgType);

			switch ($RX_TYPE)
			{
				case "event":
					$result = $this->receiveEvent($postObj);
					break;
				case "text":
					$result = $this->receiveText($postObj);
					break;
				case "image":
					$result = $this->receiveImage($postObj);
					break;
				case "location":
					$result = $this->receiveLocation($postObj);
					break;
				case "voice":
					$result = $this->receiveVoice($postObj);
					break;
				case "video":
					$result = $this->receiveVideo($postObj);
					break;
				case "shortvideo":
					$result = $this->receiveVideo($postObj);
					break;
				case "link":
					$result = $this->receiveLink($postObj);
					break;
				default:
					$result = "位置消息类型: ".$RX_TYPE;
					break;
			}
			$this->logger("T \r\n".$result);
			echo $result;
			exit;
		}else{
				echo "";
				exit;
		}
	}


//receive event message
	private function receiveEvent($object)
	{
		$content = "";
		switch ($object->Event)
		{
			case "subscribe":
				$content = "欢迎订阅本公众号";
				break;
			case "unsubscribe":
				$content = "您已成功取消本公众号的订阅";
				break;
			case "CLICK":
				switch ($object->EventKey)
				{
					case "V1001_GOOD":
						$content = "you have click the button : "."awesome";
						break;
					default:
						$content = "click the button".$object->EventKey;
						break;
				}
				break;
			case "VIEW":
				$content = "跳转链接 ".$object->EventKey;
				break;
			case "SCAN":
				$content = "扫描场景 ".$object->EventKey;
				break;
			case "LOCATION":
				break;
			case "scancode_waitmsg":
				if ($object->ScanCodeInfo->ScanType == "qrcode"){
					$content = "扫码带提示：类型 二维码 结果：".$object->ScanCodeInfo->ScanResult;
				}else if ($object->ScanCodeInfo->ScanType == "barcode"){
					$codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
					$codeValue = $codeinfo[1];
					$content = "扫码带提示：类型 条形码 结果：".$codeValue;
				}else{
					$content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
				}
				break;
			case "scancode_push":
				$content = "扫码推事件";
				break;
			case "pic_sysphoto":
				$content = "系统拍照";
				break;
			case "pic_weixin":
				$content = "相册发图：数量 ".$object->SendPicsInfo->Count;
				break;
			case "pic_photo_or_album":
				$content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
				break;
			case "location_select":
				$content = "发送位置：标签 ".$object->SendLocationInfo->Label;
				break;
			default:
				$content = "receive a new event: ".$object->Event;
				break;
		}

		if(is_array($content)){
			if (isset($content[0]['PicUrl'])){
				$result = $this->transmitNews($object, $content);
			}else if (isset($content['MusicUrl'])){
				$result = $this->transmitMusic($object, $content);
			}
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}

	//接收文本消息
	private function receiveText($object)
	{
		$keyword = trim($object->Content);
		//多客服人工回复模式
		if (strstr($keyword, "在吗") || strstr($keyword, "在线客服")){
			$result = $this->transmitService($object);
			return $result;
		}
		if($keyword == '1'){
			$content = '冰箱';
			$flag = true;
		}else if($keyword == '2'){
			$content = '洗衣机';
			$flag = true;
		}else if($keyword == '3'){
			$content = '空调';
			$flag = true;
		}else if($keyword == '4'){
			$content = '冷柜';
			$flag = true;
		}else if($keyword == '修改'){
			$content = "请修改您上传的视频资料的所属品类：\n1、冰箱 \n2、洗衣机 \n3、空调 \n4、冷柜";
		}else{
			$content = '现在时刻：北京时间'.date("Y-m-d H:i:s",time())."\n我们无法理解您的表述，敬请谅解";
		}
		if(isset($flag)) {
			$wxchat = new wxVideo();
			if($wxchat->selectEmptyBlog($object->FromUserName)){				
				$blog_id = $wxchat->updateBlogContent($content, $object->FromUserName);
				$content = '您已成功把上传的视频类型设置为'.$content;
			}else{
				$content = '对不起，您上传的文件都已经设置所属品类。';
			}
		}

		if(is_array($content)){
			if (isset($content[0])){
				$result = $this->transmitNews($object, $content);
			}else if (isset($content['MusicUrl'])){
				$result = $this->transmitMusic($object, $content);
			}
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}

	//接收图片消息
	private function receiveImage($object)
	{
		$this->logger('this is the receive the video function');
		$url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccess().'&media_id='.$object->MediaId;
		$wxchat = new wxVideo();
		$blog_id = $wxchat->saveBlog($object->FromUserName);
		$filename = $this->downloadFile($url, $object,'jpg', $blog_id);
		$content = "感谢您上传的图像资料，请选择视频资料所属品类：\n1、冰箱 \n2、洗衣机 \n3、空调 \n4、冷柜";
		$result = $this->transmitText($object, $content);
		return $result;
		/**$content = array("MediaId"=>$object->MediaId);
		$result = $this->transmitImage($object, $content);
		return $result;**/
	}

	//接收位置消息
	private function receiveLocation($object)
	{
		$content = "你发送的是位置，经度为：".$object->Location_Y."；纬度为：".$object->Location_X."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//接收语音消息
	private function receiveVoice($object)
	{
		if (isset($object->Recognition) && !empty($object->Recognition)){
			$content = "你刚才说的是：".$object->Recognition;
			$result = $this->transmitText($object, $content);
		}else{
			$content = array("MediaId"=>$object->MediaId);
			$result = $this->transmitVoice($object, $content);
		}
		return $result;
	}

	//接收视频消息
	private function receiveVideo($object)
	{
		$this->logger('this is the receive the video function');
		$url = 'http://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccess().'&media_id='.$object->MediaId;
		$thumburl = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccess().'&media_id='.$object->ThumbMediaId;
		$wxchat = new wxVideo();
		$blog_id = $wxchat->saveBlog($object->FromUserName);
		$filename = $this->downloadFile($url, $object,'mp4', $blog_id);
		$thumbfilename = $this->downloadFile($thumburl, $object,'jpg', $blog_id);
		$content = "感谢您上传的视频资料，请选择视频资料所属品类：\n1、冰箱 \n2、洗衣机 \n3、空调 \n4、冷柜";
		$result = $this->transmitText($object, $content);
		return $result;
	}


	//接收链接消息
	private function receiveLink($object)
	{
		$content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//回复文本消息
	private function transmitText($object, $content)
	{
		if (!isset($content) || empty($content)){
			return "";
		}
			$xmlTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[text]]></MsgType>
							<Content><![CDATA[%s]]></Content>
						</xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);

		return $result;
	}

	//回复图文消息
	private function transmitNews($object, $newsArray)
	{
		if(!is_array($newsArray)){
			return "";
		}
		$itemTpl = "<item>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<PicUrl><![CDATA[%s]]></PicUrl>
						<Url><![CDATA[%s]]></Url>
					</item>";
		$item_str = "";
		foreach ($newsArray as $item){
			$item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
		}
		$xmlTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>
					$item_str    </Articles>
				</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
		return $result;
	}

	//回复音乐消息
	private function transmitMusic($object, $musicArray)
	{
		if(!is_array($musicArray)){
			return "";
		}
		$itemTpl = "<Music>
		<Title><![CDATA[%s]]></Title>
		<Description><![CDATA[%s]]></Description>
		<MusicUrl><![CDATA[%s]]></MusicUrl>
		<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
	</Music>";

		$item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

		$xmlTpl = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[music]]></MsgType>
	$item_str
	</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复图片消息
	private function transmitImage($object, $imageArray)
	{
		$itemTpl = "<Image>
		<MediaId><![CDATA[%s]]></MediaId>
	</Image>";

		$item_str = sprintf($itemTpl, $imageArray['MediaId']);

		$xmlTpl = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[image]]></MsgType>
	$item_str
	</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复语音消息
	private function transmitVoice($object, $voiceArray)
	{
		$itemTpl = "<Voice>
		<MediaId><![CDATA[%s]]></MediaId>
		</Voice>";

		$item_str = sprintf($itemTpl, $voiceArray['MediaId']);
		$xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[voice]]></MsgType>
			$item_str
			</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复视频消息
	private function transmitVideo($object, $videoArray)
	{
		$itemTpl = "<Video>
			<MediaId><![CDATA[%s]]></MediaId>
			<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			</Video>";

		$item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

		$xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[video]]></MsgType>".$item_str."</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复多客服消息
	private function transmitService($object)
	{
		$xmlTpl = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[transfer_customer_service]]></MsgType>
	</xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复第三方接口消息
	private function relayPart3($url, $rawData)
	{
		$headers = array("Content-Type: text/xml; charset=utf-8");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

	//字节转Emoji表情
	function bytes_to_emoji($cp)
	{
		if ($cp > 0x10000){       # 4 bytes
			return chr(0xF0 | (($cp & 0x1C0000) >> 18)).chr(0x80 | (($cp & 0x3F000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x800){   # 3 bytes
			return chr(0xE0 | (($cp & 0xF000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x80){    # 2 bytes
			return chr(0xC0 | (($cp & 0x7C0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else{                    # 1 byte
			return chr($cp);
		}
	}

	private function logger($log_content)
	{
			$max_size = 1000000;
			$log_filename = "./log.txt";
			if(file_exists($log_filename) and (abs(filesize($log_filename))>$max_size))
			{
				unlink($log_filename);
			}
			file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
	}

	function downloadFile($url,$object,$type, $blog_id)
	{
		$this->logger('start download file. and the blog id is '.$blog_id);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
		curl_setopt($ch, CURLOPT_REFERER,_REFERER_);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$file = curl_exec($ch);
		$headerinfo = curl_getinfo($ch);
		curl_close($ch);
		/**$file = file_get_contents($url);**/
		$fileName = time().rand(0,1000).'.'.$type;
		$wxchat = new wxVideo();
		$attachment_id = $wxchat->saveAttachment($blog_id, $object->FromUserName, $fileName, $headerinfo['content_type'], $headerinfo['size_download']);
		if (!file_exists('/root/'.$attachment_id)){ 
			mkdir ('/root/'.$attachment_id);
		}
		file_put_contents('/root/'.$attachment_id.'/'.$fileName,$file);
		return $fileName;
	}

	function getAccess() {
		$tokenFile = "./access_token.txt";
		$data = json_decode(file_get_contents($tokenFile));
		if ($data->expire_time < time() or !$data->expire_time) {
			//plz change to your appid and appsecret
			$appid = "appid";
			$appsecret = "appsecret";
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
			$res = $this->getJson($url);
			$access_token = $res['access_token'];
			if($access_token) {
				$data['expire_time'] = time() + 7200;
				$data['access_token'] = $access_token;
				file_put_contents($savePath.'/'.$fileName,json_encode($data));
			}
		} else {
		  $access_token = $data->access_token;
		}
		 return $access_token;
	}

	function getJson($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		return json_decode($output, true);
	}

}
?>