<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Menu object
 *
 * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/364
 * @since 6.9.19 https://github.com/aamplugin/advanced-access-manager/issues/331
 *               https://github.com/aamplugin/advanced-access-manager/issues/334
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/293
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
 * @since 6.2.2  Added new filter `aam_backend_menu_is_restricted_filter` so it can
 *               be integrated with access policy wildcard
 * @since 6.0.0  Initial implementation of the method
 *
 * @package AAM
 * @version 6.9.36
 */
class AAM_Core_Object_Menu extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'menu';

    /**
     * @inheritdoc
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
     * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/105
     * @since 6.0.0  Initial implementation of the method
     *
     * @version 6.9.31
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->setExplicitOption($option);

        // Trigger custom functionality that may populate the menu options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters('aam_menu_object_option_filter', $option, $this);

        // Making sure that all menu keys are lowercase
        $normalized = array();
        foreach($option as $key => $val) {
            $normalized[strtolower($key)] = $val;
        }

        $this->setOption(is_array($normalized) ? $normalized : array());
    }

    /**
     * Check is menu or submenu is restricted
     *
     * @param string  $menu
     *
     * @return boolean
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
     * @since 6.2.2  Added new filter `aam_backend_menu_is_restricted_filter`
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.36
     */
    public function isRestricted($menu)
    {
        // Decode URL in case of any special characters like &amp;
        $menu_item = htmlspecialchars_decode(strtolower($menu));

        if (!in_array($menu_item, array('index.php', 'menu-index.php'))) {
            $options = $this->getOption();
            $parent  = $this->getParentMenu($menu_item);

            // Step #1. Check if menu is directly restricted
            $direct = !empty($options[$menu_item]);

            // Step #2. Check if whole branch is restricted
            $branch = !empty($options['menu-' . $menu_item]);

            // Step #3. Check if dynamic submenu is restricted because of whole branch
            $indirect = ($parent && (!empty($options['menu-' . $parent])));

            $restricted = apply_filters(
                'aam_backend_menu_is_restricted_filter',
                $direct || $branch || $indirect,
                $menu_item,
                $this
            );
        } else {
            $restricted = false;
        }

        return $restricted;
    }

    /**
     * Get parent menu
     *
     * @param string $search
     *
     * @return string|null
     *
     * @since 6.9.19 https://github.com/aamplugin/advanced-access-manager/issues/331
     *               https://github.com/aamplugin/advanced-access-manager/issues/334
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/293
     * @since 6.2.2  Made the method public
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @global array $submenu
     * @version 6.9.19
     */
    public function getParentMenu($search)
    {
        global $submenu;

        $result = $this->findParentInArray($submenu, $search);

        // If we cannot find parent menu in current $submenu array, try to find it
        // in the cached menu generated by super admin. This is important to cover
        // scenarios where submenus bubble up to menu. E.g. Profile
        if (is_null($result)) {
            $cache  = AAM_Service_AdminMenu::getInstance()->getMenuCache();
            $result = $this->findParentInArray(
                isset($cache['submenu']) ? $cache['submenu'] : array(), $search
            );
        }

        return $result;
    }

    /**
     * Find parent menu from the array of menu items
     *
     * @param array  $array
     * @param string $search
     *
     * @return null|string
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
     * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/364
     * @since 6.9.19 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.36
     */
    protected function findParentInArray($array, $search)
    {
        $result = null;

        if (is_array($array)) {
            // Covering scenario when the submenu is also a link to the parent branch
            $keys = array_map(function($k) {
                return strtolower(htmlspecialchars_decode($k));
            }, array_keys($array));

            if (array_key_exists($search, $keys)) {
                $result = $search;
            } else {
                foreach ($array as $parent => $subs) {
                    foreach ($subs as $sub) {
                        if (isset($sub[2])
                            && strtolower(htmlspecialchars_decode($sub[2])) === $search
                        ) {
                            $result = $parent;
                            break;
                        }
                    }

                    if ($result !== null) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

}