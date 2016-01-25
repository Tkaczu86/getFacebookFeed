<?php

require_once(__DIR__.'/../vendor/autoload.php');

class facebookApi
{
	private $_db;

	function __construct($configs) {

		$db = new MysqliDb($configs['db_host'], $configs['db_user'], $configs['db_pass'], $configs['db_name'], $configs['db_port']);
		$this->_db = $db;
	}

	private function getApiValue ($name) {
		$sql = $this->_db->getOne('facebook_api');
		$apiValue = $sql[$name];
		return $apiValue;
	}

	private function getFbGraphData ($page_id, $params = array()) {
		$fields = '';
		$arr_length = count($params);
		for($i=0; $i < $arr_length; $i++) {
			$fields .= $params[$i].',';
		}
		$output_fields = '/'.$page_id.'?fields='.substr($fields, 0, -1);

		return $output_fields;
	}

	private function getHttpResponseCode($url) {
	    $headers = get_headers($url);

	    return substr($headers[0], 9, 3);
	}

	private function getDateHuman($dataTimeObject) {
		$formatDate = ($dataTimeObject instanceof DateTime) ? $dataTimeObject->format("Y-m-d H:i:s") : strtotime($dataTimeObject);

		return $formatDate;
	}

	private function getPageResponse ($page) {
		$info = false;
		if (!empty($page)) {
    		$url = 'http://graph.facebook.com/'.$page;
    		$opts = array('http' => array('ignore_errors' => true));
    		//Create the stream context
			$context = stream_context_create($opts);
			//Open the file using the defined context
			$file = file_get_contents($url, false, $context);
			$jsonData = json_decode($file, true);
			if ($jsonData['error']['code'] == 104) {
				$info = true;
			}
		}

		return $info;
	}

	private function getFacebookStatus () {
	    $dataShowStatus = false;
	    /* Check data in database is exists */
	    $app_key = $this->getApiValue('api_key');
	    $app_secret = $this->getApiValue('api_secret');
	    $page_id = $this->getApiValue('api_page');
	    $app_token_url = "https://graph.facebook.com/oauth/access_token?client_id=".$app_key."&client_secret=".$app_secret."&grant_type=client_credentials";
	    if ($this->getHttpResponseCode($app_token_url) == 200 && $this->getPageResponse($page_id) == true) {
	    	return true;
	    }

	   throw new Exception('Podane dane uwierzetalniające są błędne');
	}

	private function getFacebookAccount () {

	    /* Check data in database is exists */
	    $fb_data = array();
	    $params = null;
	    $app_key = $this->getApiValue('api_key');
	    $app_secret = $this->getApiValue('api_secret');
	    $page_id = $this->getApiValue('api_page');

        $fb = new Facebook\Facebook(['app_id' => $app_key, 'app_secret' => $app_secret, 'default_graph_version' => 'v2.5']);
        $get_data_setup = array('likes','name','picture');
        $output_data_page = $this->getFbGraphData($page_id, $get_data_setup);
        $app_token_url = "https://graph.facebook.com/oauth/access_token?"."client_id=".$app_key."&client_secret=".$app_secret."&grant_type=client_credentials";
        $response = file_get_contents($app_token_url);
        parse_str($response, $params);
        $page = $fb->get($output_data_page, $params['access_token']);
        $fb_data = $page->getGraphNode()->asArray();


	    return $fb_data;
	}

	private function getFacebookFeed () {

	    /* Check data in database is exists */
	    $fb_data = array();
	    $params = null;
	    $app_key = $this->getApiValue('api_key');
	    $app_secret = $this->getApiValue('api_secret');
	    $page_id = $this->getApiValue('api_page');

        $fb = new Facebook\Facebook(['app_id' => $app_key, 'app_secret' => $app_secret, 'default_graph_version' => 'v2.5']);
        $get_data_setup = array('feed{type,name,message,created_time,link,actions,source,full_picture,description,attachments{subattachments,media}}');
        $output_data_page = $this->getFbGraphData($page_id, $get_data_setup);
        $app_token_url = "https://graph.facebook.com/oauth/access_token?"."client_id=".$app_key."&client_secret=".$app_secret."&grant_type=client_credentials";
        $response = file_get_contents($app_token_url);
        parse_str($response, $params);
        $page = $fb->get($output_data_page, $params['access_token']);
        $fb_data = $page->getGraphNode()->asArray();


	    return $fb_data;
	}

	private function checkDateFeed ($date) {
		$check = false;
		if (!empty($date)) {
			$this->_db->where('created_time', $date);
			$checkSql = $this->_db->getOne('facebook_feed');
			$check = (!$checkSql) ? true : false;
		}

		return $check;
	}

	private function sanitizeValue ($value) {
		$val = (isset($value) && !empty($value)) ? $value : null;

		return $val;
	}

	private function getFeedData () {
		$accountData = $this->getFacebookAccount();
		$feedData = $this->getFacebookFeed();

		$i = 0;
		foreach ($feedData['feed'] as $feed) {
			++$i;
			if (isset($feed['type'])) $feedOut[$i]['type'] = $feed['type'];
			if (isset($feed['name'])) $feedOut[$i]['name'] = $feed['name'];
			if (isset($feed['message'])) $feedOut[$i]['message'] = $feed['message'];
			if (isset($feed['created_time'])) $feedOut[$i]['created_time'] = $this->getDateHuman($feed['created_time']);
			if (isset($feed['link'])) $feedOut[$i]['link'] = $feed['link'];
			if (isset($feed['actions'])) {
				foreach ($feed['actions'] as $linkToFeed) {
					$feedOut[$i]['link_to_feed'] = $linkToFeed['link'];
				}
			}
			if (isset($feed['source'])) $feedOut[$i]['source'] = $feed['source'];
			if (isset($feed['description'])) $feedOut[$i]['description'] = $feed['description'];
			if (isset($feed['attachments'])) {
				foreach ($feed['attachments'] as $media) {
					$j = 0;
					if (isset($media['subattachments'])) {
						foreach ($media['subattachments'] as $images) {
							foreach ($images['media'] as $img) {
								++$j;
								if (isset($img['src'])) $feedOut[$i]['pictures'][$j] = $img['src'];
							}
						}
					} else {
						foreach ($media['media'] as $img) {
							++$j;
							if (isset($img['src'])) $feedOut[$i]['pictures'][$j] = $img['src'];
						}
					}
				}
			} else {
				if (isset($feed['full_picture'])) $feedOut[$i]['pictures'][$j] = $feed['full_picture'];
			}
		}

		return $feedOut;
	}

	// Save in database
	public function saveFeedToDatabase () {
		global $db;

		try {
			$time = microtime();
			$time = explode(' ', $time);
			$time = $time[1] + $time[0];
			$begintime = $time;

			$checkData = $this->getFacebookStatus();
			$feedOut = $this->getFeedData();
			$imagine = new \Imagine\Gd\Imagine();
			$dateNow = new DateTime();

			$feedCount = 0;
			foreach ($feedOut as $value) {
				if ($this->checkDateFeed($value['created_time'])) {
					++$feedCount;
					$data = array(
								'type' => $value['type'],
						  		'name' => $this->sanitizeValue(@$value['name']),
						  		'message' => $this->sanitizeValue(@$value['message']),
						  		'link' => $this->sanitizeValue(@$value['link']),
						  		'link_to_feed' => $this->sanitizeValue(@$value['link_to_feed']),
						  		'source' => $this->sanitizeValue(@$value['source']),
						  		'description' => $this->sanitizeValue(@$value['description']),
						  		'created_time' => $value['created_time']);
					$last_insert = $this->_db->insert('facebook_feed', $data);
					$newFolder = str_replace(array('-',':',' '), '_', $value['created_time']);
					if (!is_dir(__DIR__.'/../img/feed/'.$newFolder)) {
						mkdir(__DIR__.'/../img/feed/'.$newFolder, 0755);
	            		chmod(__DIR__.'/../img/feed/'.$newFolder, 0755);
	            		mkdir(__DIR__.'/../img/feed/'.$newFolder.'/thumbs', 0755);
	            		chmod(__DIR__.'/../img/feed/'.$newFolder.'/thumbs', 0755);
	            	}
	            	$imgCount = 0;
	            	if (!empty($value['pictures'])) {
						foreach ($value['pictures'] as $picture) {
							++$imgCount;
							$picture_name = $imgCount.'_picture.jpg';
							$download = file_get_contents($picture);
							$name = __DIR__.'/../img/feed/'.$newFolder.'/'.$picture_name;
							$fp = fopen($name, "w+");
							fwrite($fp, $download);
							fclose($fp);
							$image = $imagine->open($name);
							$size = new Imagine\Image\Box(265, 166);
							$mode = Imagine\Image\ImageInterface::THUMBNAIL_INSET;
							// $mode = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
							$thumbnail = $image->thumbnail($size, $mode);
							$thumbs_name = $name = __DIR__.'/../img/feed/'.$newFolder.'/thumbs/thumb_'.$picture_name;
							$thumbnail->save($thumbs_name);
							$data = array('feed_id' => $last_insert,
									      'picture_name' => $picture_name,
									      'picture_description' => '',
									      'date_added' => $dateNow->format('Y-m-d H:i:s'));
							$this->_db->insert('facebook_feed_photo', $data);
						}
					}
				}
			}


			$time = microtime();
			$time = explode(" ", $time);
			$time = $time[1] + $time[0];
			$endtime = $time;
			$totaltime = ($endtime - $begintime);
			echo 'Z facebooka pobrano '.$feedCount.' newsow';
			echo '<hr/>';
			echo 'PHP przetworzył tę stronę w ' .$totaltime. ' sekund.';
		} catch (Exception $e) {
			echo '..::Error--> '.$e->getMessage().' <--Error::..';
		}
	}

}