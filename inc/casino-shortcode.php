<?php 

/**
 * WP Shortcodes
 */
final class Casino_shortcode {
	/* singleton */
	private static $instance = null;

	// private $pixel = false;

	public $pixels = [];

	public static function get_instance() {
		if (self::$instance === null) self::$instance = new self();

		return self::$instance;
	}

	private function __construct() {
		$this->wp_hooks();
	}


	/**
	 * hooks for wp
	 */
	private function wp_hooks() {

		// loan list
		if (!shortcode_exists('casino')) add_shortcode('casino', array($this, 'add_shortcode'));
		else add_shortcode('emcasino', array($this, 'add_shortcode'));

		// loan thumbnail
		if (!shortcode_exists('casino-bilde')) add_shortcode('casino-bilde', array($this, 'add_shortcode_bilde'));
		else add_shortcode('emcasino-bilde', array($this, 'add_shortcode_bilde'));

		// loan button
		if (!shortcode_exists('casino-bestill')) add_shortcode('casino-bestill', array($this, 'add_shortcode_bestill'));
		else add_shortcode('emcasino-bestill', array($this, 'add_shortcode_bestill'));


		add_filter('search_first', array($this, 'add_serp'));
		// add_action('wp_footer', array($this, 'add_pixel_footer'));



	}










	/**
	 * returns a list of loans
	 */
	public function add_shortcode($atts, $content = null) {

		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		if (!is_array($atts)) $atts = [];

		$args = [
			'post_type' 		=> 'emcasinoer',
			'posts_per_page' 	=> -1,
			'orderby'			=> [
										'meta_value_num' => 'ASC',
										'title' => 'ASC'
								   ],
			'meta_key'			=> 'emcasinoer_sort'.($atts['casino'] ? '_'.sanitize_text_field($atts['casino']) : '')
		];


		$type = false;
		if (isset($atts['casino'])) $type = $atts['casino'];
		if ($type)
			$args['tax_query'] = array(
					array(
						'taxonomy' => 'emcasinoertype',
						'field' => 'slug',
						'terms' => sanitize_text_field($type)
					)
				);


		$names = false;
		if (isset($atts['name'])) $names = explode(',', preg_replace('/ /', '', $atts['name']));
		if ($names) $args['post_name__in'] = $names;
		
		$exclude = get_option('emcasinoer_exclude');

		if (is_array($exclude) && !empty($exclude)) $args['post__not_in'] = $exclude;

		$posts = get_posts($args);	

		$sorted_posts = [];
		if ($names) {
			foreach(explode(',', preg_replace('/ /', '', $atts['name'])) as $n)
				foreach($posts as $p) 
					if ($n === $p->post_name) array_push($sorted_posts, $p);
		
			$posts = $sorted_posts;
		}
				

		$html = $this->get_html($posts, $atts);

		return $html;
	}












	/**
	 * returns only thumbnail from loan
	 */
	public function add_shortcode_bilde($atts, $content = null) {
		if (!isset($atts['name']) || $atts['name'] == '') return;

		$args = [
			'post_type' 		=> 'emcasinoer',
			'posts_per_page'	=> 1,
			'name' 				=> sanitize_text_field($atts['name'])
		];

		$post = get_posts($args);

		if (!is_array($post)) return;

		if (!get_the_post_thumbnail_url($post[0])) return;

		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		$meta = get_post_meta($post[0]->ID, 'emcasinoer_data');
		if (isset($meta[0])) $meta = $meta[0];
	

		$float = false;
		if ($atts['float']) 
			switch ($atts['float']) {
				case 'left': $float = ' style="float: left; margin-right: 3rem;"'; break;
				case 'right': $float = ' style="float: right; margin-left: 3rem;"'; break;
			}

		$html = '';

		if ($meta['bestill']) {
			// adding tracking pixel
			if ($meta['pixel']) {
				if ($meta['qstring']) $html .= $this->add_pixel($this->add_query_string($meta['pixel'], $atts['source'], $atts['page']));
				else $html .= $this->add_pixel($meta['pixel']);
			}

			// fixing query string
			if ($meta['qstring']) $meta['bestill'] = $this->add_query_string($meta['bestill'], $atts['source'], $atts['page']);

			// image with anchor
			// $meta['bestill'] = $this->add_query_string($meta['bestill'], $atts['source'], $atts['page']);
			// if ($meta['pixel']) $html .= $this->add_pixel($this->add_query_string($meta['pixel'], $atts['source'], $atts['page']));
	
			$html .= '<div class="emcasino-logo-ls"'.($float ? $float : '').'><a target="_blank" rel=noopener href="'.esc_url($meta['bestill']).'"><img class="emcasino-image" alt="'.esc_attr($post[0]->post_title).'" src="'.esc_url(get_the_post_thumbnail_url($post[0], 'full')).'"></a></div>';
			return $html;
		}

		// no anchor
		return '<div class="emcasino-logo-ls"'.($float ? $float : '').'><img alt="'.esc_attr($post[0]->post_title).'" src="'.esc_url(get_the_post_thumbnail_url($post[0], 'full')).'"></div>';
	}












	/**
	 * returns bestill button only from loan
	 */
	public function add_shortcode_bestill($atts, $content = null) {
		if (!isset($atts['name']) || $atts['name'] == '') return;

		$args = [
			'post_type' 		=> 'emcasinoer',
			'posts_per_page'	=> 1,
			'name' 				=> sanitize_text_field($atts['name'])
		];

		$post = get_posts($args);
		if (!is_array($post)) return;

		$meta = get_post_meta($post[0]->ID, 'emcasinoer_data');

		if (!is_array($meta)) return;

		$meta = $meta[0];

		if (!$meta['bestill']) return;

		
		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		$float = false;
		if ($atts['float']) 
			switch ($atts['float']) {
				case 'left': $float = ' style="float: left; margin-right: 3rem;"'; break;
				case 'right': $float = ' style="float: right; margin-left: 3rem;"'; break;
			}


		$html = '';

		// fixing query string
		if ($meta['qstring']) $meta['bestill'] = $this->add_query_string($meta['bestill'], $atts['source'], $atts['page']);

		// adding tracking pixel
		if ($meta['pixel']) {
			if ($meta['qstring']) $html .= $this->add_pixel($this->add_query_string($meta['pixel'], $atts['source'], $atts['page']));
			else $html .= $this->add_pixel($meta['pixel']);
		}

		$html .= '<div class="emcasino-fatilbud-solo emcasino-fatilbud-container-solo"'.($float ? $float : '').'><a target="_blank" rel="noopener" class="emcasino-lenke-fatilbud emcasino-lenke" href="'.esc_url($meta['bestill']).'"><svg class="emcasino-svg" version="1.1" x="0px" y="0px" width="26px" height="20px" viewBox="0 0 26 20" enable-background="new 0 0 24 24" xml:space="preserve"><path fill="none" d="M0,0h24v24H0V0z"/><path class="emcasino-thumb" d="M1,21h4V9H1V21z M23,10c0-1.1-0.9-2-2-2h-6.31l0.95-4.57l0.03-0.32c0-0.41-0.17-0.79-0.44-1.06L14.17,1L7.59,7.59C7.22,7.95,7,8.45,7,9v10c0,1.1,0.9,2,2,2h9c0.83,0,1.54-0.5,1.84-1.22l3.02-7.05C22.95,12.5,23,12.26,23,12V10z"/></svg> Søk her!</a></div>';
		return $html;
	}










	/**
	 * adding sands to head
	 */
	public function add_css() {
        wp_enqueue_style('emcasino-style', EM_CASINO_PLUGIN_URL.'assets/css/pub/em-casino.css', array(), '1.0.0', '(min-width: 816px)');
        wp_enqueue_style('emcasino-mobile', EM_CASINO_PLUGIN_URL.'assets/css/pub/em-casino-mobile.css', array(), '1.0.0', '(max-width: 815px)');
	}


	/**
	 * returns the html of a list of loans
	 * @param  WP_Post $posts a wp post object
	 * @return [html]        html list of loans
	 */
	private function get_html($posts, $atts) {
		$html = '<ul class="emcasino-container">';

		$star = '<svg class="emcasino-star" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path class="emcasino-star-path" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/><path d="M0 0h24v24H0z" fill="none"/></svg>';

		$nr = 1;

		foreach ($posts as $p) {
			

			$meta = get_post_meta($p->ID, 'emcasinoer_data');

			// skip if no meta found
			if (isset($meta[0])) $meta = $meta[0];
			else continue;

			if (!is_array($meta)) continue;

			// sanitize meta
			$html .= '<li class="emcasino-listitem">';
			
			if ($meta['qstring']) $meta['bestill'] = $this->add_query_string($meta['bestill'], $atts['source'], $atts['page']);

			if ($meta['pixel']) {
				if ($meta['qstring']) $html .= $this->add_pixel($this->add_query_string($meta['pixel'], $atts['source'], $atts['page']));
				else $html .= $this->add_pixel($meta['pixel']);
			}

			$meta = $this->esc_kses($meta);
			
			$html .= '<div class="emcasino-row">';

			$html .= '<div class="emcasino-number">'.$nr.'</div>';
			$nr++;

			$html .= '<a class="emcasino-thumbnail-link" target="_blank" rel="noopener" href="'.$meta['bestill'].'"><img class="emcasino-thumbnail" src="'.wp_kses_post(get_the_post_thumbnail_url($p,'post-thumbnail')).'"></a>';

			if ($meta['info01'] || $meta['info02']) $html .= '<div class="emcasino-info-container">';
			if ($meta['info01']) $html .= '<div class="emcasino-info emcasino-info-one">'.$meta['info01'].'</div>';
			if ($meta['info02']) $html .= '<div class="emcasino-info emcasino-info-two">'.$meta['info02'].'</div>';
			if ($meta['info01'] || $meta['info02']) $html .= '</div>';


			if ($meta['bestill'] || $meta['readmore']) $html .= '<div class="emcasino-buttons">';
			if ($meta['bestill']) $html .= '<a class="emcasino-link emcasino-playnow" target="_blank" rel=noopener href="'.esc_url($meta['bestill']).'">Spill Nå</a>';
			if ($meta['readmore']) $html .= '<a class="emcasino-link emcasino-readmore" target="_blank" rel=noopener href="'.esc_url($meta['readmore']).'">Les Mer</a>';
			if ($meta['bestill'] || $meta['readmore']) $html .= '</div>';

			// if ($meta['bestill']) $html .= '<div class="emcasino-fatilbud"><a class="emcasino-lenke emcasino-lenke-fatilbud" target="_blank" rel=noopener href="'.esc_url($meta['bestill']).'">Få Tilbud Nå</a></div>';
			$html .= '</div>';



			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}















	private function add_pixel($pixel) {
		if ($this->pixels[$pixel]) return '';

		$this->pixels[$pixel] = true;

		return '<img width=0 height=0 src="'.esc_url($pixel).'" style="position:absolute">';
	}

	// public function add_pixel_footer() {
	// 	foreach ($this->$pixels as $key => $value)
	// 		echo '<img width=0 height=0 src="'.esc_url($key).'" style="position:absolute">';
	// }












	private function add_query_string($link = null, $source = null, $page = null) {
		
		$axo = [
			'page' => 'aff_sub2',
			'source' => 'source',
			'click_id' => 'aff_click_id',
			'type' => 'aff_sub'
		];

		$adservice = [
			'page' => 'sub'
		];

		if (strpos($link, 'axo') !== false) $def = $axo;
		elseif (strpos($link, 'adservice') !== false) $def = $adservice;
		else $def = $axo; // axo is currently default

		// parsing given url for query string
		parse_str(parse_url($link)['query'], $url_query);

		// parsing current url for query string
		parse_str($_SERVER['QUERY_STRING'], $result);
		// wp_die('<xmp>'.print_r($url_query, true).'</xmp>');
		
		// source
		if ($def['source']) {
			if ($source) $result[$def['source']] = $source;
			// elseif ($url_query[$def['source']]) $result[$def['source']] = $url_query[$def['source']];
			elseif (!$url_query[$def['source']]) $result[$def['source']] = $_SERVER['SERVER_NAME'];
		}


		// page (aff_sub2)
		if ($def['page']) {
			global $post;
			// global $pagename;
			if ($page) $result[$def['page']] = $page;
			// elseif ($url_query[$def['page']]) $result[$def['page']] = $url_query[$def['page']]; 
			elseif (!$url_query[$def['page']]) $result[$def['page']] = $post->post_name;
		}
			// wp_die('<xmp>'.print_r($result, true).'</xmp>');
		// aff_sub 
		// aff_click_id
		// from google ad, bing ad or organic
		if ($def['type']) {
		$result[$def['type']] = 'organic';
		foreach($result as $key => $value)
			switch ($key) {
				case 'gclid': 
					$result[$def['type']] = 'google'; 
					$result[$def['click_id']] = $value;
					break;
				case 'msclkid': 
					$result[$def['type']] = 'bing'; 
					$result[$def['click_id']] = $value;
					break;
			}
		// removing google ad and bing ad parameter
			unset($result['gclid']);
			unset($result['msclkid']);
		}

		return add_query_arg($result, $link);
	}











	/**
	 * wp filter for adding to internal serp
	 * array_push to $data
	 * $data['html'] to be printed
	 * 
	 * @param [Array] $data [filter]
	 */
	public function add_serp($data) {
		global $post;

		if ($post->post_type != 'emcasinoer') return $data;

		$exclude = get_option('emcasinoer_exclude');
		if (!is_array($exclude)) $exclude = [];
		if (in_array($post->ID, $exclude)) return $data;

		$exclude_serp = get_option('emcasinoer_exclude_serp');
		if (!is_array($exclude_serp)) $exclude_serp = [];
		if (in_array($post->ID, $exclude_serp)) return $data;

		$html['html'] = $this->get_html([$post]);

		array_push($data, $html);
		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		return $data;
	}















	// private function add_source($meta, $source) {

	// 	// removing current source
	// 	if (preg_match('/(?:(?!\?|&))(?:source=.*?)(?:(&|$))/', $meta, $matches))
	// 		$meta = str_replace($matches[0], '', $meta); 
	// 	$meta = preg_replace('/(\?|&)$/', '', $meta);

	// 	// adding source
	// 	if (strpos($meta, '?') !== false) $meta .= '&source=' . $source;
	// 	else $meta .= '?source=' . $source;

	// 	return $meta;
	// }


	/**
	 * kisses the data
	 * recursive sanitizer
	 * 
	 * @param  Mixed $data Strings or Arrays
	 * @return Mixed       Kissed data
	 */
	private function esc_kses($data) {
		if (!is_array($data)) return wp_kses_post($data);

		$d = [];
		foreach($data as $key => $value)
			$d[$key] = $this->esc_kses($value);

		return $d;
	}
}