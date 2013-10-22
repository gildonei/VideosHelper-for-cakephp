<?php
/**
 * This helper generates the tag for embedding videos from youtube and vimeo,
 * Next features, integration with Redtube and megavideo. :D
 *
 * @name Video Helper
 * @author Emerson Soares (dev.emerson@gmail.com)
 * @version	1.0
 * @license	MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 */
App::uses('HtmlxHelper', 'View/Helper');

/**
 * @tutorial
 *
 * // basic usage
 * echo $this->Video->embed($video['Video']['url'], array(
 * 		'width' => 450,
 * 		'height' => 300
 * ));
 *
 * //advanced usage
 * echo $this->Video->embed($video['Video']['url'], array(
 * 		'width' => 450,
 * 		'height' => 300,
 * 		'allowfullscreen'=>1,
 * 		'loop'=>1,
 * 		'color'=>'00adef',
 * 		'show_title'=>1,
 * 		'show_byline'=>1,
 * 		'show_portrait'=>0,
 * 		'autoplay'=>1,
 * 		'frameborder'=>0
 * ));
 */
class VideoHelper extends HtmlHelper
{
	/**
	 * Supported APIs
	 *
	 * @access private
	 * @var array
	 */
	private $apis = array(
		'youtube_image' => 'http://i.ytimg.com/vi', // Location of youtube images
		'youtube' => 'http://www.youtube.com', // Location of youtube player
		'vimeo' => 'http://player.vimeo.com/video'
	);

	/**
	 * Outuput Video embed html
	 *
	 * @access public
	 * @param string $url
	 * @param string $size [small, medium, large]
	 * @param array $options [CakePHP Html options]
	 * @return string
	 */
	public function embed($url, $settings = array())
	{
		if ($this->getVideoSource($url) == 'youtube') {
			return $this->youTubeEmbed($url, $settings);
		} elseif ($this->getVideoSource($url) == 'vimeo') {
			return $this->vimeoEmbed($url, $settings);
		} else {
			return $this->tag('notfound', __('Sorry, video not found'), array('type' => 'label', 'class' => 'error'));
		}
	}

	/**
	 * Outuput Video Thumbnail
	 *
	 * @access public
	 * @param string $url
	 * @param string $size [small, medium, large]
	 * @param array $options [CakePHP Html options]
	 * @return string
	 */
	public function thumbnail($url, $size = 'small', $options = array())
	{
		$accepted_sizes = array('small', 'medium', 'large');
		if (!in_array($size, $accepted_sizes)) {
			$size = 'small';
		}
		if ($this->getVideoSource($url) == 'youtube') {
			if ($size == 'medium') {
				$size = 'large';
			}
			return $this->youTubeThumbnail($url, $size, $options);
		} elseif ($this->getVideoSource($url) == 'vimeo') {
			$size = 'thumbnail_'.$size;
			return $this->vimeoThumbnail($url, $size, $options);
		} else {
			return $this->tag('notfound', __('Sorry, image not found'), array('type' => 'label', 'class' => 'error'));
		}
	}

	/**
	 * Outuput YouTube video embed
	 *
	 * @access public
	 * @param string $url
	 * @param array $settings [Youtube Html settings]
	 * @return string
	 */
	public function youTubeEmbed($url, $settings = array())
	{
		$default_settings = array(
			'hd' => true,
			'width' => 624,
			'height' => 369,
			'allowfullscreen' => 'true',
			'frameborder' => 0
		);

		$settings = array_merge($default_settings, $settings);
		$video_id = $this->getVideoId($url);
		$settings['src'] = $this->apis['youtube'] . DS . 'embed' . DS . $video_id . '?hd=' . $settings['hd'];

		return $this->tag('iframe', null, array(
			'width' => $settings['width'],
			'height' => $settings['height'],
			'src' => $settings['src'],
			'frameborder' => $settings['frameborder'],
			'allowfullscreen' => $settings['allowfullscreen'])
		) . $this->tag('/iframe');
	}

	/**
	 * Outuput Vimeo video embed
	 *
	 * @access public
	 * @param string $url
	 * @param array $settings [Youtube Html settings]
	 * @return string
	 */
	public function vimeoEmbed($url, $settings = array())
	{
		$default_settings = array
			(
			'width' => 400,
			'height' => 225,
			'show_title' => 1,
			'show_byline' => 1,
			'show_portrait' => 0,
			'color' => '00adef',
			'allowfullscreen' => 1,
			'autoplay' => 1,
			'loop' => 1,
			'frameborder' => 0
		);
		$settings = array_merge($default_settings, $settings);

		$video_id = $this->getVideoId($url);
		$settings['src'] = $this->apis['vimeo'] . DS . $video_id . '?title=' . $settings['show_title'] . '&amp;byline=' . $settings['show_byline'] . '&amp;portrait=' . $settings['show_portrait'] . '&amp;color=' . $settings['color'] . '&amp;autoplay=' . $settings['autoplay'] . '&amp;loop=' . $settings['loop'];
		return $this->tag('iframe', null, array(
			'src' => $settings['src'],
			'width' => $settings['width'],
			'height' => $settings['height'],
			'frameborder' => $settings['frameborder'],
			'webkitAllowFullScreen' => $settings['allowfullscreen'],
			'mozallowfullscreen' => $settings['allowfullscreen'],
			'allowFullScreen' => $settings['allowfullscreen']
		)) . $this->tag('/iframe');
	}

	/**
	 * Outputs Youtube video image
	 *
	 * @access public
	 * @param string $url
	 * @param string $size [thumb, large, thumb1, thumb2, thumb3]
	 * @param array $options  [CakePHP Html options]
	 * @return string
	 */
	public function youTubeThumbnail($url, $size = 'thumb', $options = array())
	{
		$video_id = $this->getVideoId($url);

		$accepted_sizes = array(
			'thumb' => 'default', // 120px x 90px
			'large' => 0, // 480px x 360px
			'thumb1' => 1, // 120px x 90px at position 25%
			'thumb2' => 2, // 120px x 90px at position 50%
			'thumb3' => 3  // 120px x 90px at position 75%
		);
		if (!in_array($size, $accepted_sizes)) {
			$size = 'thumb';
		}
		$image_url = $this->apis['youtube_image'] . DS . $video_id . DS . $accepted_sizes[$size] . '.jpg';
		return $this->image($image_url, $options);
	}

	/**
	 * Outputs Vimeo video image
	 *
	 * @access public
	 * @param string $url
	 * @param string $size [thumbnail_small, thumbnail_medium, thumbnail_large]
	 * @param array $options [CakePHP Html options]
	 * @return string
	 */
	public function vimeoThumbnail($url, $size = 'thumbnail_small', $options = array())
	{
		$video_id = $this->getVideoId($url);
		if (!in_array($size, array('thumbnail_small', 'thumbnail_medium', 'thumbnail_large'))) {
			$size = 'thumbnail_small';
		}
		$video_info = unserialize(file_get_contents("http://vimeo.com/api/v2/video/{$video_id}.php"));

		return $this->image($video_info[0][$size], $options);
	}

	/**
	 * Returns video id
	 *
	 * @access private
	 * @param string $url
	 * @return string
	 */
	private function getVideoId($url)
	{
		if ($this->getVideoSource($url) == 'youtube') {
			$params = $this->getUrlParams($url);
			return (isset($params['v']) ? $params['v'] : $url);
		} else if ($this->getVideoSource($url) == 'vimeo') {
			$path = parse_url($url, PHP_URL_PATH);
			return substr($path, 1);
		}
	}

	/**
	 * Return url params from video url
	 *
	 * @access private
	 * @param string $url
	 * @returnarray
	 */
	private function getUrlParams($url)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		$queryParts = explode('&', $query);

		$params = array();
		foreach ($queryParts as $param) {
			$item = explode('=', $param);
			$params[$item[0]] = $item[1];
		}
		return $params;
	}

	/**
	 * Return the source provider from video
	 *
	 * @access private
	 * @param string $url
	 * @return string|boolean
	 */
	private function getVideoSource($url)
	{
		$parsed_url = parse_url($url);
		$host = $parsed_url['host'];
		if (!$this->isip($host)) {
			if (!empty($host))
				$host = $this->returnDomain($host);
			else
				$host = $this->returnDomain($url);
		}
		$host = explode('.', $host);
		if (is_int(array_search('vimeo', $host)))
			return 'vimeo';
		elseif (is_int(array_search('youtube', $host)))
			return 'youtube';
		else
			return false;
	}

	/**
	 * Return a boolean validating ip from video url
	 *
	 * @access private
	 * @param string $url
	 * @return boolean
	 */
	private function isip($url)
	{
		//first of all the format of the ip address is matched
		if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $url)) {
			//now all the intger values are separated
			$parts = explode(".", $url);
			//now we need to check each part can range from 0-255
			foreach ($parts as $ip_parts) {
				if (intval($ip_parts) > 255 || intval($ip_parts) < 0)
					return false; //if number is not within range of 0-255
			}
			return true;
		}
		else
			return false; //if format of ip address doesn't matches
	}

	/**
	 * Return the domain name from video provider
	 *
	 * @access private
	 * @param string $domainb
	 * @return string
	 */
	private function returnDomain($domainb)
	{
		$bits = explode('/', $domainb);
		if ($bits[0] == 'http:' || $bits[0] == 'https:') {
			$domainb = $bits[2];
		} else {
			$domainb = $bits[0];
		}
		unset($bits);
		$bits = explode('.', $domainb);
		$idz = count($bits);
		$idz-=3;
		if (strlen($bits[($idz + 2)]) == 2) {
			$url = $bits[$idz] . '.' . $bits[($idz + 1)] . '.' . $bits[($idz + 2)];
		} else if (strlen($bits[($idz + 2)]) == 0) {
			$url = $bits[($idz)] . '.' . $bits[($idz + 1)];
		} else {
			$url = $bits[($idz + 1)] . '.' . $bits[($idz + 2)];
		}
		return $url;
	}
}
