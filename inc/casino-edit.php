<?php 
defined('ABSPATH') or die('Blank Space');


/*
*/
final class Casino_edit {
	/* singleton */
	private static $instance = null;


	public static function get_instance() {
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}



	private function __construct() {


		add_action('manage_emcasino_posts_columns', array($this, 'column_head'));
		add_filter('manage_emcasino_posts_custom_column', array($this, 'custom_column'));
		add_filter('manage_edit-emcasino_sortable_columns', array($this, 'sort_column'));
		
		/* metabox, javascript */
		add_action('add_meta_boxes_emcasino', array($this, 'create_meta'));
		/* hook for page saving/updating */
		add_action('save_post', array($this, 'save'));


		add_filter('emtheme_doc', array($this, 'add_doc'), 99);

	}

	/**
	 * theme filter for populating documentation
	 * 	
	 * @param [array] $data [array passing through theme filter]
	 */
	public function add_doc($data) {
		$data['emcasino']['title'] = '<h1 id="emcasino">Lånlist (Plugin)</h1>';

		$data['emcasino']['index'] = '<li><h2><a href="#emcasino">Lånlist (Plugin)</a></h2>
											<ul>
												<li><a href="#emcasino-shortcode">Shortcode</a></li>
												<li><a href="#emcasino-aldri">Aldri vis</a></li>
												<li><a href="#emcasino-sort">Sorting order</a></li>
												<li><a href="#emcasino-overview">Overview</a></li>
											</ul>
										</li>';
		$data['emcasino']['info'] = '<li id="emcasino-shortcode"><h2>Shortcodes</h2>
										<ul>
											<li><b>[lan]</b>
											<p>[lan] will show all.</p>
											</li>
											<li><b>[lan name="xx, yy"]</b>
											<p>Shows only the loans that is mentioned in the shortcode.
											<br>The name needs to be the slug-name of the loan.
											<br>Loans are sorted by the position they have in name=""
											<br>eks.: [lan name="lendo-privatlan"] will only show the loan with slug-name "lendo-privatlån.
											<br>[lan name="lendo-privatlan, axo-finans"] will show 2 loans: lendo and axo.</p>
											<li><b>[lan lan="xx"]</b>
											<p>lan must match the slug-name of the lan type.
											<br>The loans are sorted by the sort order given in load edit page for that type.
											<br>Eks: [lan lan="frontpage"] shows all loans with the category "frontpage" in the order of lowest number
											<br>of field "Sort frontpage" has in the load editor page.</p>
											</li>
											</li>
											<li><b>[lan-bilde name="xx"]</b>
											<p>Name is required. Will show the loan\'s thumbnail with a link.
											<br>[kredittkort-bestlil name="xx" source="test"] will append &source=test at the link.</p></li>
											<li><b>[lan-bestill name="xx"]</b>
											<p>Name is required. Will show the loan\'s button.
											<br>[kredittkort-bestlil name="xx" source="test"] will append &source=test at the link.</p></li>
											
										</ul>
										</li>
										<li id="emcasino-aldri"><h2>Aldri vis</h2>
										<p>If tagged, then the loan will never appear on the front-end.</p>
										</li>
										</li>
										<li id="emcasino-sort"><h2>Sorting order</h2>
										<p>The loans will be shown with the lowest "Sort"-value first.
										<br>When only showing a specific category on loan page, then the sort order column will reflect 
										<br>that category\'s sort order.</p>
										</li>
										<li id="emcasino-overview"><h2>Overview</h2>
										<p> The <a target="_blank" href="'.get_site_url().'/wp-admin/edit.php?post_type=emcasino&page=emcasino-overview">overview page</a> will show every post and page and whether or not there are
										<br>any lan shortcodes in them.
										<br>You can sort the columns alphabetically</p>
										</li>
										';

		return $data;
	}

	/**
	 * wp filter for adding columns on ctp list page
	 * 
	 * @param  [array] $defaults [array going through wp filter]
	 * @return [array]           [array going through wp filter]
	 */
	public function column_head($defaults) {
		$defaults['emcasino_sort'] = 'Sorting Order';
		return $defaults;
	}


	/**
	 * filter for populating columns on ctp list page
	 * 
	 * @param  [array] $defaults [array going through wp filter]
	 * @return [array]           [array going through wp filter]
	 */
	public function custom_column($column_name) {
		global $post;
		// echo $_SERVER['QUERY_STRING'];

		// echo parse_url()
		
		// echo print_r($q_out, true);

		if ($column_name == 'emcasino_sort') {
			$q_out = null;
			parse_str($_SERVER['QUERY_STRING'], $q_out);

			$meta = 'emcasino_sort';
			if (isset($q_out['emcasinotype'])) $meta = $meta.'_'.$q_out['emcasinotype'];

			$meta = get_post_meta($post->ID, $meta);
			
			if (isset($meta[0])) echo $meta[0];
		}
	}


	/**
	 * filter for sorting by columns on ctp list page
	 * 
	 * @param  [array] $defaults [array going through wp filter]
	 * @return [array]           [array going through wp filter]
	 */
	public function sort_column($columns) {
		$columns['emcasino_sort'] = 'emcasino_sort';
		return $columns;
	}



	/*
		creates wordpress metabox
		adds javascript
	*/
	public function create_meta() {

		/* lan info meta */
		add_meta_box(
			'emcasino_meta', // name
			'Lån Info', // title 
			array($this,'create_meta_box'), // callback
			'emcasino' // page
		);

		/* to show or not on front-end */
		add_meta_box(
			'emcasino_exclude',
			'Aldri vis',
			array($this, 'exclude_meta_box'),
			'emcasino',
			'side'
		);
		
		/* adding admin css and js */
		wp_enqueue_style('em-emcasino-admin-style', EM_CASINO_PLUGIN_URL . 'assets/css/admin/em-casino.css', array(), '1.0.2');
		wp_enqueue_script('em-emcasino-admin', EM_CASINO_PLUGIN_URL . 'assets/js/admin/em-casino.js', array(), '1.0.3', true);
	}


	/*
		creates content in metabox
	*/
	public function create_meta_box($post) {
		wp_nonce_field('em'.basename(__FILE__), 'emcasino_nonce');

		$meta = get_post_meta($post->ID, 'emcasino_data');
		$sort = get_post_meta($post->ID, 'emcasino_sort');

		$tax = wp_get_post_terms($post->ID, 'emcasinotype');

		$taxes = [];
		if (is_array($tax))
			foreach($tax as $t)
				array_push($taxes, $t->slug);

		$json = [
			'meta' => isset($meta[0]) ? $meta[0] : '',
			'meta' => isset($meta[0]) ? $this->sanitize($meta[0]) : '',
			'emcasino_sort' => isset($sort[0]) ? floatval($sort[0]) : '',
			'tax' => $taxes
		];

		$ameta = get_post_meta($post->ID);
		foreach($ameta as $key => $value)
			if (strpos($key, 'emcasino_sort_') !== false && isset($value[0])) $json[$key] = esc_html($value[0]);

		// wp_die('<xmp>'.print_r($_POST, true).'</xmp>');

		wp_localize_script('em-emcasino-admin', 'emcasino_data', json_decode(json_encode($json), true));
		echo '<div class="emcasino-meta-container"></div>';
	}
 

 	/**
 	 * [exclude_meta_box description]
 	 */
	public function exclude_meta_box() {
		global $post;

		$exclude = get_option('emcasino_exclude');
		if (!is_array($exclude)) $exclude = [];


		$exclude_serp = get_option('emcasino_exclude_serp');
		if (!is_array($exclude_serp)) $exclude_serp = [];


		echo '<p><input name="emcasino_exclude" id="emcasino_exc" type="checkbox"'.(array_search($post->ID, $exclude) !== false ? ' checked' : '').'><label for="emcasino_exc">Lån vil ikke vises på front-end når boksen er markert.</label></p>
		      <p><input name="emcasino_exclude_serp" id="emcasino_exc_serp" type="checkbox"'.(array_search($post->ID, $exclude_serp) !== false ? ' checked' : '').'><label for="emcasino_exc_serp">Ikke vis i internal SERP.</label></p>';
	}



	/**
	 * wp action when saving
	 */
	public function save($post_id) {
		// wp_die('<xmp>'.print_r($_POST, true).'</xmp>');
		
		// post type is emcasino
		if (!get_post_type($post_id) == 'emcasino') return;

		// is on admin screen
		if (!is_admin()) return;

		// user is logged in and has permission
		if (!current_user_can('edit_posts')) return;

		// nonce is sent
		if (!isset($_POST['emcasino_nonce'])) return;

		// nonce is checked
		if (!wp_verify_nonce($_POST['emcasino_nonce'], 'em'.basename(__FILE__))) return;

		// saves to wp option instead of post meta
		// when adding
		$this->u_option('emcasino_exclude', $post_id);
		$this->u_option('emcasino_exclude_serp', $post_id);
		// if (isset($_POST['emcasino_exclude'])) {
		// 	$option = get_option('emcasino_exclude');

		// 	// to avoid php error
		// 	if (!is_array($option)) $option = [];

		// 	// if not already added
		// 	if (array_search($post_id, $option) === false) {

		// 		// if to add to collection
		// 		if (is_array($option)) {
		// 			array_push($option, intval($post_id));

		// 			update_option('emcasino_exclude', $option);
		// 		}
				
		// 		// if to create collection (of one)
		// 		else update_option('emcasino_exclude', [$post_id]);
		// 	}
		// }
		// // when removing
		// else {
		// 	$option = get_option('emcasino_exclude');

		// 	if (array_search($post_id, $option) !== false) {
		// 		unset($option[array_search($post_id, $option)]);
		// 		update_option('emcasino_exclude', $option);
		// 	}
		// }

		// data is sent, then sanitized and saved
		// if (isset($_POST['emcasino_data'])) update_post_meta($post_id, 'emcasino_data', $_POST['emcasino_data']);
		if (isset($_POST['emcasino_data'])) update_post_meta($post_id, 'emcasino_data', $this->sanitize($_POST['emcasino_data']));
		if (isset($_POST['emcasino_sort'])) update_post_meta($post_id, 'emcasino_sort', floatval($_POST['emcasino_sort']));

		// saving emcasino_sort_***
		foreach($_POST as $key => $po) {
			if (strpos($key, 'emcasino_sort_') !== false)
				update_post_meta($post_id, sanitize_text_field(str_replace(' ', '', $key)), floatval($po));
		}

	}

	/**
	 * update option
	 * @param  [type] $data  [var to save to]
	 * @param  [type] $value [data to be saved]
	 */
	private function u_option($data, $value) {
		$option = get_option($data);
		if (!is_array($option)) $option = []; // to avoid php error
		
		$value = intval($value);

		if (isset($_POST[$data])) {

			// if not already added
			if (array_search($value, $option) === false) {

				// if to add to collection
				if (is_array($option)) {
					array_push($option, $value);
					update_option($data, $option);
				}
				
				// if to create collection (of one)
				else update_option($data, [$value]);
			}
		}
		// when removing
		else {
			// $option = get_option($data);

			if (array_search($value, $option) !== false) {
				unset($option[array_search($value, $option)]);
				update_option($data, $option);
			}
		}
	}

	/*
		recursive sanitizer
	*/
	private function sanitize($data) {
		if (!is_array($data)) return wp_kses_post($data);

		$d = [];
		foreach($data as $key => $value) {
			switch ($key) {
				case 'bestill':
				case 'pixel':
				case 'readmore': $d[$key] = sanitize_text_field($value); break;
				default: $d[$key] = $this->sanitize($value); break;
			}
		}

		return $d;
	}
}