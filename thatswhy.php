<?php
/*
Plugin Name: That's Why
Plugin URI: https://thatswhy.app/?ref=wordpress-plugin-uri
Description: An easy way to implement the That's Why Real User Monitoring snippet to your Wordpress sites.
Author: Astronatic
Author URI: https://astronatic.com/?ref=wordpress-author-uri-thatswhy
Version: 1.7

That's Why for WordPress
Copyright (C) 2020 Astronatic

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

const THATSWHY_CUSTOM_DOMAIN_OPTION_NAME = 'thatswhy_custom_domain';
const THATSWHY_URL_OPTION_NAME = 'thatswhy_url';
const THATSWHY_SITE_HASH_OPTION_NAME = 'thatswhy_site_hash';
const THATSWHY_ADMIN_TRACKING_OPTION_NAME = 'thatswhy_track_admin';
const THATSWHY_SHOW_ANALYTICS_MENU_ITEM = 'thatswhy_show_menu';

/**
 * @since 0.1
 */
function thatsWhyGetUrl() {
    $thatsWhyUrl = get_option(THATSWHY_URL_OPTION_NAME, '');

    // don't print snippet if URL is empty
    if(empty($thatsWhyUrl)) {
        return 'collector.thatswhy.app';
    }

    // trim trailing slash
    $thatsWhyUrl = rtrim($thatsWhyUrl, '/');

    // make relative
    $thatsWhyUrl = str_replace(['https:', 'http:'], '', $thatsWhyUrl);

    return $thatsWhyUrl;
}

/**
 * @since 0.1
 */
function thatsWhyGetSiteHash() {
    return get_option( THATSWHY_SITE_HASH_OPTION_NAME, '' );
}

/**
 * @since 0.1
 */
function thatsWhyGetCustomDomain() {
    // Error proof the input
    $customDomainOption = 'https://' . str_replace(['https://', 'http://'], '', get_option( THATSWHY_CUSTOM_DOMAIN_OPTION_NAME, 'https://collector.thatswhy.app/' ));

    // If we have a host
    if (array_key_exists('host', (array) parse_url($customDomainOption))) {
        return parse_url($customDomainOption)['host'];
    } else {
        return 'https://collector.thatswhy.app/';
    }
}

/**
 * @since 0.1
 */
function thatsWhyGetAdminTracking() {
    return get_option(THATSWHY_ADMIN_TRACKING_OPTION_NAME, '');
}

/**
 * @since 0.1
 */
function thatsWhyPrintJsSnippet() {
    $url = thatsWhyGetUrl();
    $exclude_admin = thatsWhyGetAdminTracking();

    // don't print snippet if thatswhy URL is empty
    if(empty($url)) {
        return;
    }

    if(empty($exclude_admin) && current_user_can('manage_options')) {
        return;
    }

    $siteHash = thatsWhyGetSiteHash();

    if (empty($siteHash)) {
        return;
    }

    if (empty(thatsWhyGetCustomDomain())) {
        $collectorUrl = 'https://collector.thatswhy.app/'.esc_attr($siteHash).'.js';
    } else {
        $collectorUrl = 'https://'.str_replace(['http://', 'https://', '/'], '', thatsWhyGetCustomDomain()).'/'.esc_attr($siteHash).'.js';
    }

    $collectorScriptName = 'thatswhy-collector';
    wp_register_script($collectorScriptName, $collectorUrl, [], false, false);
    wp_enqueue_script($collectorScriptName);
}

/**
 * @since 0.1
 */
function thatsWhyStatsPage() {
    add_menu_page('That\'s Why', 'That\'s Why', 'edit_pages', 'analytics', 'thatsWhyPrintStatsPage', 'dashicons-chart-bar', 6);
}

/**
 * @since 0.1
 */
function thatsWhyPrintStatsPage() {
    if (!empty(get_option( THATSWHY_SITE_HASH_OPTION_NAME ))) {
        $openUrl = 'https://thatswhy.app/external/'.get_option(THATSWHY_SITE_HASH_OPTION_NAME).'/plugins/wordpress/dashboard';

        wp_register_script('thatswhy-dashboard-redirect', '');
        wp_enqueue_script('thatswhy-dashboard-redirect');
        wp_add_inline_script('thatswhy-dashboard-redirect', 'setTimeout(function(){ let win = window.open("'.$openUrl.'", "_blank");win.focus();}, 500);');

        echo '<div class="wrap">';
        echo 'Redirecting you to <a target="_blank" href="'.$openUrl.'">'.$openUrl.'</a>...';
        echo '</div>';
    } else {
        echo '<div class="wrap">You have not configured That\'s Why. Go to Settings -> That\'s Why to configure this page.</div>';
    }
}

/**
 * @since 0.1
 */
function thatsWhyRegisterSettings() {
    $thatswhy_logo_html = sprintf( '<a target="_blank" href="https://thatswhy.app/?ref=wordpress-logo">That\'s Why <img src="%s" width=20 height=20 style="margin-left: 6px; vertical-align: bottom;"></a>', plugins_url( 'thatswhy.png', __FILE__ ) );

    // register page + section
    add_options_page( 'That\'s Why Real User Monitoring', 'That\'s Why', 'manage_options', 'thatswhy', 'thatsWhyPrintSettingsPage' );
    add_settings_section( 'default', $thatswhy_logo_html, '__return_true', 'thatswhy' );

    // register options
    register_setting( 'thatswhy', THATSWHY_SITE_HASH_OPTION_NAME, array( 'type' => 'string' ) );
    register_setting( 'thatswhy', THATSWHY_ADMIN_TRACKING_OPTION_NAME, array( 'type' => 'string') );
    register_setting( 'thatswhy', THATSWHY_SHOW_ANALYTICS_MENU_ITEM, array( 'type' => 'boolean' ) );
    register_setting( 'thatswhy', THATSWHY_CUSTOM_DOMAIN_OPTION_NAME, array( 'type' => 'string' ) );

    // register settings fields
    add_settings_field( THATSWHY_SITE_HASH_OPTION_NAME, __( 'Site Hash', 'thatswhy' ), 'thatsWhyPrintSiteHashSettingField', 'thatswhy', 'default' );
    add_settings_field( THATSWHY_ADMIN_TRACKING_OPTION_NAME, __('Track Administrators', 'thatswhy'), 'thatsWhyPrintAdminTrackingSettingField', 'thatswhy', 'default');
    // add_settings_field( THATSWHY_CUSTOM_DOMAIN_OPTION_NAME, __( 'Custom Domain', 'thatswhy' ), 'thatsWhyPrintCustomDomainSettingField', 'thatswhy', 'default' ); // Upcoming feature?
    add_settings_field( THATSWHY_SHOW_ANALYTICS_MENU_ITEM,  __( 'Display Analytics Menu Item', 'thatswhy' ), 'thatsWhyPrintDisplayAnalyticsMenuSettingField', 'thatswhy', 'default' );
}

/**
 * @since 0.1
 */
function thatsWhyPrintSettingsPage() {
    echo '<div class="wrap">';
    echo sprintf( '<form method="POST" action="%s">', esc_attr( admin_url( 'options.php' ) ) );
    settings_fields( 'thatswhy' );
    do_settings_sections( 'thatswhy' );
    submit_button();
    echo '</form>';
    echo '</div>';
}

/**
 * @since 0.1
 */
function thatsWhyPrintDisplayAnalyticsMenuSettingField( $args = array() ) {
    $value = get_option( THATSWHY_SHOW_ANALYTICS_MENU_ITEM );
    echo sprintf( '<input type="checkbox" name="%s" id="%s" class="regular-text" ' . (esc_attr($value) ? 'checked' : '') .' />', THATSWHY_SHOW_ANALYTICS_MENU_ITEM, THATSWHY_SHOW_ANALYTICS_MENU_ITEM);
    echo '<p class="description">' . __( 'Pro: Display the That\'s Why Tab in the sidebar for easy access to your dashboard', 'thatswhy' ) . '</p>';
}

/**
 * @since 0.1
 */
function thatsWhyPrintCustomDomainSettingField( $args = array() ) {
    $value = get_option( THATSWHY_CUSTOM_DOMAIN_OPTION_NAME );
    $placeholder = 'https://cname.yourwebsite.com';
    echo sprintf( '<input type="text" name="%s" id="%s" class="regular-text" value="%s" placeholder="%s" />', THATSWHY_CUSTOM_DOMAIN_OPTION_NAME, THATSWHY_CUSTOM_DOMAIN_OPTION_NAME, esc_attr( $value ), esc_attr( $placeholder ) );
    echo '<p class="description">' . __( 'Optional. Do not put anything in here unless you have a custom domain', 'thatswhy' ) . '</p>';
}

/**
 * @since 0.1
 */
function thatsWhyPrintSiteHashSettingField($args = []) {
    $value = get_option( THATSWHY_SITE_HASH_OPTION_NAME );
    $placeholder = 'ABCDEF';
    echo sprintf( '<input type="text" name="%s" id="%s" class="regular-text" value="%s" placeholder="%s" />', THATSWHY_SITE_HASH_OPTION_NAME, THATSWHY_SITE_HASH_OPTION_NAME, esc_attr( $value ), esc_attr( $placeholder ) );
    echo '<p class="description">' . __( 'This is the <a href="https://thatswhy.app/support/wordpress#unique-tracking-id" target="_blank">unique Tracking ID</a> for your site', 'thatswhy' ) . '</p>';
}

/**
 * @since 0.1
 */
function thatsWhyPrintAdminTrackingSettingField($args = []) {
    $value = get_option( THATSWHY_ADMIN_TRACKING_OPTION_NAME );
    echo sprintf( '<input type="checkbox" name="%s" id="%s" value="1" %s />', THATSWHY_ADMIN_TRACKING_OPTION_NAME, THATSWHY_ADMIN_TRACKING_OPTION_NAME, checked( 1, $value, false ) );
    echo '<p class="description">' . __( 'Check if you want to track visits by administrators', 'thatswhy' ) . '</p>';
}

add_action('wp_head', 'thatsWhyPrintJsSnippet', 50);

if(is_admin() && ! wp_doing_ajax()) {
    add_action('admin_menu', 'thatsWhyRegisterSettings');
}

if (get_option(THATSWHY_SHOW_ANALYTICS_MENU_ITEM)) {
    add_action('admin_menu', 'thatsWhyStatsPage');
}
