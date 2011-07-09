<?php
/**
 * Ionize
 *
 * @package		Ionize
 * @author		Ionize Dev Team
 * @license		http://ionizecms.com/doc-license
 * @link		http://ionizecms.com
 * @since		Version 0.9.7
 *
 */

/**
 * Ionize Tagmanager Navigation Class
 *
 * @package		Ionize
 * @subpackage	Libraries
 * @category	TagManager Libraries
 *
 */
class TagManager_Navigation extends TagManager
{

	public static $tag_definitions = array
	(
		'navigation' => 					'tag_navigation',
//		'navigation:title' =>				'tag_page_title',			
//		'navigation:subtitle' =>			'tag_page_subtitle',
		'navigation:active_class' =>		'tag_navigation_active_class',			
		'navigation:url' =>					'tag_navigation_url',			
		'tree_navigation' => 				'tag_tree_navigation',
		'tree_navigation:active_class' =>	'tag_navigation_active_class',			
//		'tree_navigation:base_link' =>		'tag_navigation_base_link',			
//		'tree_navigation:lang_link' =>		'tag_navigation_lang_link',			
		'sub_navigation' => 				'tag_sub_navigation',
		'sub_navigation_title' => 			'tag_sub_navigation_title',
	);
	
	
	// ------------------------------------------------------------------------

	
	/**
	 * Navigation tag definition
	 * @usage	
	 *
	 */
	public static function tag_navigation($tag)
	{
		// Tag cache
		if (($str = self::get_cache($tag)) !== FALSE)
			return $str;

		// Final string to print out.
		$str = '';

		// Helper / No helper ?
		$no_helper = (isset($tag->attr['no_helper']) ) ? TRUE : FALSE;
		$helper = (isset($tag->attr['helper']) ) ? $tag->attr['helper'] : 'navigation';
		
		// Menu : Main menu by default
		$menu_name = isset($tag->attr['menu']) ? $tag->attr['menu'] : 'main';
		$id_menu = 1;
		foreach($tag->globals->menus as $menu)
		{
			if ($menu_name == $menu['name'])
				$id_menu = $menu['id_menu'];
		}
		
		// Navigation level. FALSE if not defined
		$asked_level = isset($tag->attr['level']) ? $tag->attr['level'] : FALSE;

		// Display hidden navigation elements ?
		$display_hidden = isset($tag->attr['display_hidden']) ? TRUE : FALSE;

		// Current page
		$current_page =& $tag->locals->page;

		// Attribute : active CSS class
		$active_class = (isset($tag->attr['active_class']) ) ? $tag->attr['active_class'] : 'active';
		if (strpos($active_class, 'class') !== FALSE) $active_class= str_replace('\'', '"', $active_class);
		

		/*
		 * Getting menu data
		 *
		 */
		// Page from locals
		$global_pages = $tag->globals->pages;

		// Add the active class key
		$id_current_page = ( ! empty($current_page['id_page'])) ? $current_page['id_page'] : FALSE;
		
		$active_pages = Structure::get_active_pages($global_pages, $id_current_page);

		foreach($global_pages as &$page)
		{
			// Add the active_class key
			$page['active_class'] = in_array($page['id_page'], $active_pages) ? $active_class : '';
		}

		// Filter by menu and asked level : We only need the asked level pages !
		// $pages = array_filter($global_pages, create_function('$row','return ($row["level"] == "'. $asked_level .'" && $row["id_menu"] == "'. $id_menu .'") ;'));
		$pages = array();
		$parent_page = array();
		
		// Asked Level exists
		if ($asked_level !== FALSE)
		{
			foreach($global_pages as $p)
			{
				if ($p['level'] == $asked_level && $p['id_menu'] == $id_menu)
					$pages[] = $p;
			}
		}
		// Get navigation from current page
		else
		{
			foreach($global_pages as $p)
			{
				// Child pages of id_subnav
				if ($p['id_parent'] == $tag->locals->page['id_subnav'])
					$pages[] = $p;

				// Parent page is the id_subnav page
				if ($p['id_page'] == $tag->locals->page['id_subnav'])
					$parent_page = $p;
			}
		}
		
		// Filter on 'appears'=>'1'
		if ($display_hidden == FALSE)
			$pages = array_values(array_filter($pages, array('TagManager_Page', '_filter_appearing_pages')));
		
		// Get the parent page from one level upper
		if ($asked_level > 0)
		{
			// $parent_pages = array_filter($global_pages, create_function('$row','return $row["level"] == "'. ($asked_level-1) .'";'));
			$parent_pages = array();
			foreach($global_pages as $p)
			{
				if ($p['level'] == ($asked_level-1))
					$parent_pages[] = $p;
			}
			
			// $parent_page = array_values(array_filter($parent_pages, create_function('$row','return $row["active_class"] != "";')));
			// $parent_page = ( ! empty($parent_page)) ? $parent_page[0] : FALSE;
			foreach($parent_pages as $p)
			{
				if ($p['active_class'] != '')
					$parent_page = $p;
			}
		}
		
		// Filter the current level pages on the link with parent page
		if ( ! empty($parent_page ))
		{
			// $pages = array_filter($pages, create_function('$row','return $row["id_parent"] == "'. $parent_page['id_page'] .'";'));
			$o_pages = $pages;
			$pages = array();
			foreach($o_pages as $p)
			{
				if ($p['id_parent'] == $parent_page['id_page'])
					$pages[] = $p;
			}
		}
		else
		{
			if ($asked_level > 0)
				$pages = array();
		}

		// Get helper method
		$helper_function = (substr(strrchr($helper, ':'), 1 )) ? substr(strrchr($helper, ':'), 1 ) : 'get_navigation';
		$helper = (strpos($helper, ':') !== FALSE) ? substr($helper, 0, strpos($helper, ':')) : $helper;

		// load the helper
		self::$ci->load->helper($helper);
		
		// Return the helper function result
		if (function_exists($helper_function) && $no_helper === FALSE)
		{
			$nav = call_user_func($helper_function, $pages);
			
			$output = self::wrap($tag, $nav);
			
			// Tag cache
			self::set_cache($tag, $output);

			return $output;
		}
		else
		{
			foreach($pages as $index => $p)
			{
				$tag->locals->page = $p;
				$tag->locals->index = $index;
				$str .= $tag->expand();
			}

			$output = self::wrap($tag, $str);
			
			// Tag cache
			self::set_cache($tag, $output);

			return $output;
		}
		
		return self::show_tag_error($tag->name, 'Error message');
	}

	
	// ------------------------------------------------------------------------


	public static function tag_sub_navigation_title($tag)
	{
		if ($tag->locals->page['subnav_title']  != '')
		{
			return self::wrap($tag, $tag->locals->page['subnav_title']);
		}
		else
		{
			foreach($tag->globals->pages as $page)
			{
				if ($page['id_page'] == $tag->locals->page['id_subnav'])
				{
					return self::wrap($tag, $page['subnav_title']);
				}
			}
		}		
		return '';		
	}
	
	
	// ------------------------------------------------------------------------


	/**
	 * Return a tree navigation based on the given helper.
	 *
	 * @param	FTL_Binding object
	 *
	 */
	public static function tag_tree_navigation($tag)
	{
		// Current page
		$page = $tag->locals->page;
	
		// If 404 : Put empty vars, so the menu will prints out without errors
		if ( !isset($page['id_page']))
		{
			$page = array(
				'id_page' => '',
				'id_parent' => ''
			);
		}

		// Menu : Main menu by default
		$menu_name = isset($tag->attr['menu']) ? $tag->attr['menu'] : 'main';
		$id_menu = 1;
		foreach($tag->globals->menus as $menu)
		{
			if ($menu_name == $menu['name'])
			{
				$id_menu = $menu['id_menu'];
			}	
		}
		
		// If set, attribute level, else parent page level + 1
		$from_level = (isset($tag->attr['level']) ) ? $tag->attr['level'] :0 ;

		// If set, depth
		$depth = (isset($tag->attr['depth']) ) ? $tag->attr['depth'] : -1;
		
		// Attribute : active class, first_class, last_class
		$active_class = (isset($tag->attr['active_class']) ) ? $tag->attr['active_class'] : 'active';
		$first_class = (isset($tag->attr['first_class']) ) ? $tag->attr['first_class'] : '';
		$last_class = (isset($tag->attr['last_class']) ) ? $tag->attr['last_class'] : '';

		// Display hidden navigation elements ?
		$display_hidden = isset($tag->attr['display_hidden']) ? TRUE : FALSE;

		// Attribute : HTML Tree container ID & class attribute
		$id = (isset($tag->attr['id']) ) ? $tag->attr['id'] : NULL ;
		if (strpos($id, 'id') !== FALSE) $id= str_replace('\'', '"', $id);

		$class = (isset($tag->attr['class']) ) ? $tag->attr['class'] : NULL ;
		if (strpos($active_class, 'class') !== FALSE) $active_class= str_replace('\'', '"', $active_class);
		
		// Attribute : Use lang_url or url ?
		$lang_url = (isset($tag->attr['lang']) && $tag->attr['lang'] === 'TRUE') ? TRUE : FALSE ;
		if ($lang_url == FALSE)
			$lang_url = (isset($tag->attr['lang_url']) && $tag->attr['lang_url'] === 'TRUE') ? TRUE : FALSE ;
		
		// Attribute : Helper to use to print out the tree navigation
		$helper = (isset($tag->attr['helper']) && $tag->attr['helper'] != '' ) ? $tag->attr['helper'] : 'navigation';

		// Get helper method
		$helper_function = (substr(strrchr($helper, ':'), 1 )) ? substr(strrchr($helper, ':'), 1 ) : 'get_tree_navigation';
		$helper = (strpos($helper, ':') !== FALSE) ? substr($helper, 0, strpos($helper, ':')) : $helper;

		// load the helper
		self::$ci->load->helper($helper);

		// Page from locals : By ref because of active_class definition
		$pages =&  $tag->locals->pages;

		/* Get the reference parent page ID
		 * Note : this is depending on the whished level.
		 * If the curent page level > asked level, we need to find recursively the parent page which has the good level.
		 * This is done to avoid tree cut when navigation to a child page
		 *
		 * e.g :
		 *
		 * On the "services" page and each subpage, we want the tree navigation composed by the sub-pages of "services"
		 * We are in the page "offer"
		 * We have to find out that the level 1 parent is "services"
		 *
		 *	Page structure				Level
		 *
		 *	home						0
		 *	 |_ about					1		
		 *	 |_ services				1		<- We want all the nested nav starting at level 1 from this parent page
		 *	 	   |_ development		2
		 *		   |_ design			2
		 *				|_ offer		3		<- We are here.
		 *				|_ portfolio	3	
		 *
		 */
		$page_level = (isset($page['level'])) ? $page['level'] : 0;
	 
		$parent_page = array(
			'id_page' => ($from_level > 0) ? $page['id_page'] : 0,
			'id_parent' => isset($page['id_parent']) ? $page['id_parent'] : 0
		);

		// Find out the wished parent page 
		while ($page_level >= $from_level && $from_level > 0)
		{
			// $potential_parent_page = array_values(array_filter($pages, create_function('$row','return $row["id_page"] == "'. $parent_page['id_parent'] .'";')));
			$potential_parent_page = array();
			foreach($pages as $p)
			{
				if($p['id_page'] == $parent_page['id_parent'])
				{
					$potential_parent_page = $p;
					break;
				}
			}
			// if (isset($potential_parent_page[0]))
			if ( ! empty($potential_parent_page))
			{
				$parent_page = $potential_parent_page;
				$page_level = $parent_page['level'];
			}
			else
			{
				$page_level--;
			}
		}
		// Active pages array. Array of ID
		$active_pages = Structure::get_active_pages($pages, $page['id_page']);
		
		foreach($pages as $key => $p)
		{
			$pages[$key]['active_class'] = in_array($p['id_page'], $active_pages) ? $active_class : '';
		}

		// Filter on 'appears'=>'1'
		$nav_pages = array();
		if ($display_hidden == FALSE)
			$nav_pages = array_values(array_filter($pages, array('TagManager_Page', '_filter_appearing_pages')));
		
		// $nav_pages = array_filter($nav_pages, create_function('$row','return ($row["id_menu"] == "'. $id_menu .'") ;'));
		$final_nav_pages = array();
		foreach($nav_pages as $k => $np)
		{
			if ($np['id_menu'] == $id_menu )
				$final_nav_pages[] = $np;
		}
		
		// Get the tree navigation array
		$tree = Structure::get_tree_navigation($final_nav_pages, $parent_page['id_page'], $from_level, $depth);

		// Return the helper function
		if (function_exists($helper_function))
			return call_user_func($helper_function, $tree, $lang_url, $id, $class, $first_class, $last_class);
	}


	// ------------------------------------------------------------------------


	public static function tag_navigation_active_class($tag) { return isset($tag->locals->page['active_class']) ? $tag->locals->page['active_class'] : ''; }
	

	// ------------------------------------------------------------------------


	/** 
	 * Return the URL of a navigation menu item.
	 *
	 */
	public static function tag_navigation_url($tag) 
	{
		// If lang attribute is set to TRUE, force the lang code to be in the URL
		// Usefull only if the website has only one language
		$lang_url = (isset($tag->attr['lang']) && $tag->attr['lang'] == 'TRUE' ) ? TRUE : FALSE;
		
		if ($tag->locals->page['link'] != '' && $tag->locals->page['link_type'] == '')
		{
			return $tag->locals->page['absolute_url'];
		}
		
		/*
		 * In this case, the <ion:url /> tag of the <ion:navigation /> tag forces the lang code to be in the URL
		 * Because the function init_pages_urls() has already put the lang code, this check is only useful
		 * for internal link if the lang code isn't set by init_pages_urls()
		 *
		 */
		if ($lang_url === TRUE)
		{
			if (strpos($tag->locals->page['absolute_url'], base_url().Settings::get_lang()) === FALSE)
			{
				$tag->locals->page['absolute_url'] = str_replace(base_url(), base_url().Settings::get_lang() . '/', $tag->locals->page['absolute_url']);
			}
			
			return $tag->locals->page['absolute_url'];
		}
		
		return $tag->locals->page['absolute_url'];
	}
	
	

}

/* End of file Navigation.php */
/* Location: /application/libraries/Tagmanager/Navigation.php */