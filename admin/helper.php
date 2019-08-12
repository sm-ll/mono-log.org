<?php
class CP_Helper
{
	/**
	 * The currently logged in member's roles.
	 * @var array
	 */
	private static $member_roles;

	public static function show_page($page, $default = true)
	{
		$admin_nav = Config::get('_admin_nav');

		return array_get($admin_nav, $page, $default);
	}

	public static function nav_count()
	{
		$default_config = YAML::parse(Config::getAppConfigPath() . '/default.settings.yaml');
		$admin_nav = array_merge($default_config['_admin_nav'], Config::get('_admin_nav', array()));

		return count(array_filter($admin_nav, 'strlen'));

	}

	public static function addon_nav_items()
	{
		$default = array(
			'dashboard',
			'pages',
			'members',
			'account',
			'system',
			'logs',
			'help',
			'view_site',
			'logout'
		);
		$nav = array_keys(array_filter(Config::get('_admin_nav')));

		return array_diff($nav, $default);
	}


	/**
	 * Returns whether or not the specified page is visible to the user
	 * 
	 * @param  array $page  Page data
	 * @return boolean      Access
	 */
	public static function is_page_visible($page)
	{
		$show_page = true;

		// Get visibility scheme and bail if there isn't one.
		if ( ! $scheme = array_get($page, '_admin')) {
			return true;
		}

		// Clean up the scheme
		foreach ($scheme as $key=>$val) {
			if ( ! in_array($key, array('hide','show'))) {
				unset($scheme[$key]);
			}
		}

		// Get/set member's roles
		if ( ! self::$member_roles) {
			self::$member_roles = Auth::getCurrentMember()->get('roles');
		}
		$roles = self::$member_roles;

		// Hiding is checked first because we'd rather show a page than hide it if there's crossover.
		if (array_get($scheme, 'hide')) {
			if ( ! is_array($scheme['hide'])) {
				// Flag as hidden if `hide: true|yes|1` is specified
				$show_page = false;
			} else {
				// Flat as hidden if the member's role is in scheme's "hide" list 
				$show_page = array_intersect($scheme['hide'], $roles) ? false : true;
			}
		}

		if (array_get($scheme, 'show')) {
			// Flat as showable if the member's role is in scheme's "show" list 
			$show_page = (bool) array_intersect(array_get($scheme, 'show', array()), $roles);
		}

		return $show_page;
	}

}
