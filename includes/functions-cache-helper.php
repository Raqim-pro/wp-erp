<?php

/**
 * Get Last Time Cache Changed for a module/menu
 *
 * A custom `wp_cache_last_change` implementation
 * It would give us a performance boost rather than using a simple `last_changed` for wp_cache
 *
 * @since 1.8.3
 *
 * @param string $module_name, eg: crm, hrm, accounting
 * @param string $list or menu name, eg: contacts, employees
 * @param string $group_name ie: erp, erp-asset, erp-attendance
 *
 * @return string $last_changed microtime
 */
function erp_cache_get_last_changed ( $module_name, $list_or_menu_name, $group_name = 'erp' ) {
    $last_changed = wp_cache_get( "last_changed_$module_name:$list_or_menu_name", $group_name );

    if ( ! $last_changed ) {
        $last_changed = microtime();
        wp_cache_set( "last_changed_$module_name:$list_or_menu_name", $last_changed, $group_name );
    }

    return $last_changed;
}

/**
 * Purge the cache for ERP
 *
 * Update cache and invalidate cache data for module, list and group wise
 * Change list for different types like: contacts, employees, deals, accounting etc.
 *
 * @since 1.8.3
 *
 * @param array $args ie: ['group'=>'erp','module','list']
 *
 * @return void
 */
function erp_purge_cache( $args = [] ) {

    $defaults = [
        'group'  => 'erp',
        'module' => '',
        'list'   => '',
    ];

    $args = wp_parse_args( $args, $defaults );

    // Delete all of the list, if given multiple list by comma seprator
    $lists = explode( ',', $args['list'] );

    foreach( $lists as $list ) {
        $last_changed_key = 'last_changed_' . $args['module'] . ':' . $list;

        // invalidate the last change key for this module, group and list
        wp_cache_set( $last_changed_key, microtime(), $args['group'] );
    }
}


/**
 * Purge cache for HRM Module
 *
 * Remove all cache for HRM Module
 *
 * @since 1.8.3
 *
 * @param array $args
 *
 * @return void
 */
function erp_hrm_purge_cache( $args = [] ) {

    $group = 'erp';

    if ( isset( $args['employee_id'] ) ) {
        wp_cache_delete( 'erp-employee-by-' . $args['employee_id'], $group );
    }

    if ( isset( $args['designation_id'] ) ) {
        wp_cache_delete( 'erp-get-designation-by-' . $args['designation_id'], $group );
    }

    if ( isset( $args['list'] ) ) {
        $list = $args['list'];

        if( $list === 'employee' ) {
            wp_cache_delete( 'erp-hr-employee-status-counts', $group );
        }

        if( $list === 'leave_request' ) {
            wp_cache_delete( 'erp-hr-leave-request-counts', $group );
        }

        erp_purge_cache( [ 'module' => 'hrm', 'list' => $args['list'], 'group' => $group ] );
    }
}


/**
 * Purge the cache for ERP CRM module
 *
 * Update cache and invalidate cache data
 *
 * @since 1.8.3
 *
 * @param array $args
 *
 * @return void
 */
function erp_crm_purge_cache( $args = [] ) {
    $group = 'erp';

    // Delete contact-group-detail cache
    if ( ! empty ( $args['erp-crm-contact-group-detail'] ) ) {
        wp_cache_delete( 'erp-crm-contact-group-detail-' . $args['erp-crm-contact-group-detail'], $group );
    }

    // Delete if any 'erp-people-by-' is in cache - By id, email or user_id field
    if ( ! empty ( $args['erp-people-by'] ) ) {
        $values = $args['erp-people-by'];

        array_map( function ( $value ) {
            $cache_key      = 'erp-people-by-' . md5( serialize( $value ) );
            wp_cache_delete( $cache_key, 'erp' );
        }, $values );
    }

    // Delete if any 'feed-detail' is in cache by id
    if ( ! empty ( $args['feed-detail'] ) ) {
        wp_cache_delete( 'erp-feeds-by-' . $args['feed-detail'], $group );
    }

    // change list for different type like, people, contact, contact_groups etc. and invalidate the last change key
    if( ! empty ( $args['list'] ) ) {

        if( $args['list'] === 'people' && ! empty ( $args['type'] ) ) {
            wp_cache_delete( 'erp-crm-customer-status-counts-' . $args['type'], $group );
        }

        erp_purge_cache( ['module' => 'crm', 'list' => $args['list'] ] );
    }
}

/**
 * Purge cache data for accounting module
 *
 * Remove all cache for accounting module
 *
 * @since 1.8.3
 *
 * @param array $args
 *
 * @return void
 */
function erp_acct_purge_cache( $args = [] ) {

    $group = 'erp-accounting';

    if ( isset( $args['transaction_id'] ) ) {
        wp_cache_delete( "erp-transaction-by-" . $args['transaction_id'], $group );
    }

    if ( isset( $args['key'] ) ) {
        wp_cache_delete( $args['key'], $group );
    }

    if ( isset( $args['list'] ) ) {
        erp_purge_cache( [ 'group' => $group, 'module' => 'accounting', 'list' => $args['list'] ] );
    }
}
