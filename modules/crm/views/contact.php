<?php
    if ( isset( $_GET['filter_assign_contact' ] ) && !empty( $_GET['filter_assign_contact' ] ) ) {
        $id          = intval( $_GET['filter_assign_contact'] );
        $custom_data = [
            'filter_assign_contact' => [
                'id'           => $id,
                'display_name' => get_the_author_meta( 'display_name', $id ),
            ],
            'searchFields' => array_keys( erp_crm_get_serach_key( 'contact' ) ),
        ];
    } elseif ( isset( $_GET['filter_contact_company' ] ) && !empty( $_GET['filter_contact_company' ] ) ) {
        $id          = intval( $_GET['filter_contact_company'] );
        $custom_data = [
            'filter_contact_company' => [
                'id'           => $id,
                'display_name' => erp_get_people( $id )->company,
            ],
            'searchFields' => array_keys( erp_crm_get_serach_key( 'contact' ) ),
        ];
    } else {
        $custom_data = [
            'searchFields' => array_keys( erp_crm_get_serach_key( 'contact' ) ),
        ];
    }
?>

<div class="wrap erp-crm-customer erp-crm-customer-listing" id="wp-erp" v-cloak>

    <h2>
        <?php esc_attr_e( 'Contacts', 'erp' ); ?>

        <?php if ( current_user_can( 'erp_crm_add_contact' ) ) : ?>
            <a href="#" @click.prevent="addContact( 'contact', '<?php esc_attr_e( 'Add New Contact', 'erp' ); ?>' )" id="erp-customer-new" class="erp-contact-new add-new-h2"><?php esc_attr_e( 'Add New Contact', 'erp' ); ?></a>
            <a href="#" @click.prevent="importUsers()" id="erp-contact-import-users" class="erp-contact-import-users add-new-h2"><?php esc_attr_e( 'Import Users', 'erp' ); ?></a>
        <?php endif; ?>

        <a href="#" @click.prevent="addSearchSegment()" id="erp-contact-search-segmen" class="erp-search-segment add-new-h2">{{{ segmentBtnText }}}</a>

        <?php if ( current_user_can( 'erp_crm_add_contact' ) ) : ?>
            <div class="erp-btn-group" id="crm-import-export">
                <button @click.prevent="importCsv( 'contact' )"><?php esc_html_e( 'Import', 'erp' ); ?></button>
                <button @click.prevent="exportCsv( 'contact' )"><?php esc_html_e( 'Export', 'erp' ); ?></button>
            </div>
        <?php endif; ?>
    </h2>

    <?php do_action( 'erp_crm_contact_menu', 'contacts' ); ?>

    <!-- Advance search filter vue component -->
    <advance-search :show-hide-segment="showHideSegment"></advance-search>

    <!-- vue table for displaying contact list -->
    <vtable v-ref:vtable
        wrapper-class="erp-crm-list-table-wrap"
        table-class="customers"
        row-checkbox-id="erp-crm-customer-id-checkbox"
        row-checkbox-name="customer_id"
        action="erp-crm-get-contacts"
        :wpnonce="wpnonce"
        page = "<?php echo esc_url_raw( add_query_arg( [ 'page' => 'erp-crm' ], admin_url( 'admin.php' ) ) ); ?>"
        per-page="20"
        :fields=fields
        :item-row-actions=itemRowActions
        :search="search"
        :top-nav-filter="topNavFilter"
        :bulkactions="bulkactions"
        :extra-bulk-action="extraBulkAction"
        :additional-params="additionalParams"
        :remove-url-params="removeUrlParams"
        :custom-data = '<?php echo json_encode( $custom_data, JSON_UNESCAPED_UNICODE ); ?>'
    ></vtable>

</div>
