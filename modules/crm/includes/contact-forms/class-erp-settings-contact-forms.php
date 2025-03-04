<?php

namespace WeDevs\ERP\CRM\ContactForms;

/**
 * ERP Settings Contact Form class
 */
class ERP_Settings_Contact_Forms {
    use ContactForms;

    protected $crm_options = [];
    protected $active_plugin_list = [];
    protected $forms = [];

    /**
     * Class constructor
     */
    public function __construct() {
        $this->crm_options        = $this->get_crm_contact_options();
        $this->active_plugin_list = $this->get_active_plugin_list();

        $this->filter( 'erp_settings_crm_section_fields', 'crm_contact_forms_section_fields', 10, 2 );
        $this->action( 'erp_admin_field_contact_form_options', 'output_contact_form_options' );

        foreach ( $this->active_plugin_list as $slug => $plugin ) {
            $this->forms[ $slug ] = apply_filters( "crm_get_{$slug}_forms", [] );
        }

        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
    }

    /**
     * Initializes the class
     *
     * Checks for an existing instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Include required CSS and JS
     *
     * @return void
     */
    public function admin_scripts() {
        $crm_contact_forms_settings = [
            'nonce'             => wp_create_nonce( 'erp_settings_contact_forms' ),
            'plugins'           => array_keys( $this->active_plugin_list ),
            'forms'             => $this->forms,
            'mappedData'        => get_option( 'wperp_crm_contact_forms', '' ),
            'crmOptions'        => $this->crm_options,
            'scriptDebug'       => defined( 'SCRIPT_DEBUG' ) ? SCRIPT_DEBUG : false,
            'contactGroups'     => erp_crm_get_contact_groups_list(),
            'contactOwners'     => erp_crm_get_crm_user_dropdown(),
            'i18n'              => [
                'notMapped'             => __( 'Not Set', 'erp' ),
                'labelOK'               => __( 'OK', 'erp' ),
                'labelContactGroups'    => __( 'Contact Group', 'erp' ),
                'labelSelectGroup'      => __( 'Select Contact Group', 'erp' ),
                'labelContactOwner'     => __( 'Contact Owner', 'erp' ),
                'labelSelectOwner'      => __( 'Select Owner', 'erp' ),
            ],
        ];

        wp_enqueue_style( 'erp-sweetalert' );
        wp_enqueue_script( 'erp-sweetalert' );
        wp_enqueue_script( 'erp-vuejs' );
        wp_enqueue_script( 'erp-settings-contact-forms', WPERP_CRM_ASSETS . '/js/erp-settings-contact-forms.js', [ 'erp-vuejs', 'jquery', 'erp-sweetalert' ], WPERP_VERSION, true );
        wp_localize_script( 'erp-settings-contact-forms', 'crmContactFormsSettings', $crm_contact_forms_settings );
    }

    /**
     * Settings fields for contact forms
     *
     * @param array $fields
     * @param array $sections
     *
     * @return array
     */
    public function crm_contact_forms_section_fields( $fields, $sections ) {
        $plugins = $this->active_plugin_list;

        if ( empty( $plugins ) ) {
            $fields['contact_forms'] = [
                [
                    'title' => __( 'Contact Forms Integration', 'erp' ),
                    'type'  => 'title',
                    'desc'  => sprintf(
                                '%s' . __( 'No supported contact form plugin is currently active. WP ERP has built-in support for <strong>Contact Form 7</strong> and <strong>Ninja Forms</strong>.', 'erp' ) . '%s',
                                '<section class="notice notice-warning cfi-hide-submit"><p>',
                                '</p></section>'
                            ),
                    'id' => 'contact_form_options',
                ],
            ];

            return $fields;
        }

        $keys        = array_keys( $plugins );
        $cur_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
        $sub_section = isset( $_GET['sub-section'] ) ? sanitize_text_field( wp_unslash( $_GET['sub-section'] ) ) : $keys[0];
        $forms       = $this->forms[ $sub_section ];

        if ( 'contact_forms' === $cur_section ) {
            printf( '<ul class="subsubsub" style="margin: -12px 0 20px 0;">' );

            foreach ( $plugins as $slug => $plugin ) {
                printf(
                    '<li"><a href="%s" class="%s">%s</a> %s </li>',
                    esc_url( admin_url( 'admin.php?page=erp-settings&tab=erp-crm&section=contact_forms&sub-section=' . sanitize_title( $slug ) ) ),
                    ( $sub_section === $slug ? 'current' : '' ),
                    esc_html( $plugin['title'] ),
                    ( end( $keys ) === $slug ? '' : '|' )
                );
            }

            printf( '</ul><br class="clear" />' );
        }

        if ( empty( $forms ) ) {
            /* If no form created with respective plugin this notice will show.
                Also if there is no function hook to the "crm_get_{$slug}_forms",
                filter we'll see this notice */
            $fields['contact_forms'] = [
                [
                    'title' => $plugins[ $sub_section ]['title'],
                    'type'  => 'title',
                    'desc'  => sprintf(
                                '%s' . __( "You don't have any form created with %s!", 'erp' ) . '%s',
                                '<section class="notice notice-warning cfi-hide-submit"><p>',
                                $plugins[ $sub_section ]['title'],
                                '</p></section>'
                            ),
                    'id' => 'section_' . $sub_section,
                ],
            ];
        } else {
            foreach ( $forms as $form_id => $form ) {
                $fields['contact_forms'][] = [
                    'title' => $form['title'],
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'section_' . $form['name'],
                ];

                $fields['contact_forms'][] = [
                    'plugin'        => $sub_section,
                    'form_id'       => $form_id,
                    'type'          => 'contact_form_options',
                ];

                $fields['contact_forms'][] = [ 'type' => 'sectionend', 'id' => 'section_' . $form['name'] ];
            }
        }

        return $fields;
    }

    /**
     * Hook new type of option field
     *
     * @param array $value contains the field configs
     *
     * @return void
     */
    public function output_contact_form_options( $value ) {
        ?>
        <tr class="cfi-table-container cfi-hide-submit">
            <td style="padding-left: 0; padding-top: 0;">
                <table
                    class="wp-list-table widefat fixed striped cfi-table"
                    id="<?php echo esc_attr( $value['plugin'] ) . '_' . esc_attr( $value['form_id'] ); ?>"
                    data-plugin="<?php echo esc_attr( $value['plugin'] ); ?>"
                    data-form-id="<?php echo esc_attr( $value['form_id'] ); ?>"
                    v-cloak
                >
                    <tbody>
                        <tr>
                            <th class="cfi-table-wide-column"><?php esc_html_e( 'Form Field', 'erp' ); ?></th>
                            <th class="cfi-table-wide-column"><?php esc_html_e( 'CRM Contact Option', 'erp' ); ?></th>
                            <th class="cfi-table-narrow-column">&nbsp;</th>
                        </tr>
                    </tbody>

                    <tbody class="cfi-mapping-row {{ lastOfTypeClass($index) }}" v-for="(field, title) in formData.fields">
                        <tr>
                            <td>{{ title }}</td>
                            <td>{{ getCRMOptionTitle(field) }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="button button-default"
                                    v-on:click="resetMapping(field)"
                                    :disabled="isMapped(field)"
                                >
                                    <i class="dashicons dashicons-no-alt"></i>
                                </button>
                                <button type="button" class="button button-default" v-on:click="setActiveDropDown(field)">
                                    <i class="dashicons dashicons-screenoptions"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="cfi-option-row" v-show="field === activeDropDown">
                            <td colspan="3" class="cfi-contact-options">
                                <button
                                    type="button"
                                    v-for="(option, optionTitle) in crmOptions"
                                    v-if="!optionIsAnObject(option)"
                                    v-on:click="mapOption(field, option)"
                                    :class="['button', isOptionMapped(field, option) ? 'button-primary active' : '']"
                                >{{ optionTitle }}</button>

                                <span v-for="(option, options) in crmOptions" v-if="optionIsAnObject(option)">
                                    <button
                                        type="button"
                                        v-for="(childOption, childOptionTitle) in options.options"
                                        v-if="optionIsAnObject(option)"
                                        v-on:click="mapChildOption(field, option, childOption)"
                                        :class="['button', isChildOptionMapped(field, option, childOption) ? 'button-primary active' : '']"
                                    >{{ options.title + ' - ' + childOptionTitle }}</button>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <label>
                                    {{ i18n.labelContactGroups }} <span>&nbsp;&nbsp;&nbsp;</span>
                                    <select class="cfi-contact-group" v-model="formData.contactGroup">
                                        <option value="0">{{ i18n.labelSelectGroup }}</option>
                                        <option v-for="(groupId, groupName) in contactGroups" value="{{ groupId }}">{{ groupName }}</option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <label>
                                    {{ i18n.labelContactOwner }} <span class="required">*</span>
                                    <select class="cfi-contact-group" v-model="formData.contactOwner">
                                        <option value="0">{{ i18n.labelSelectOwner }}</option>
                                        <option v-for="(userId, user) in contactOwners" value="{{ userId }}">{{ user }}</option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <button
                                    type="button"
                                    class="button"
                                    v-on:click="reset_mapping"
                                ><?php esc_html_e( 'Reset', 'erp' ); ?></button>
                                <button
                                    type="button"
                                    class="button button-primary"
                                    v-on:click="save_mapping"
                                ><?php esc_html_e( 'Save Changes', 'erp' ); ?></button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </td>
            <td></td>
        </tr>
        <?php
    }

    /**
     * Ajax hook function to save the ERP Settings
     *
     * @return void prints json object
     */
    public function save_erp_settings() {
        $response = [
            'success' => false,
            'msg'     => null,
        ];

        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'erp_settings_contact_forms' ) ) {
            $this->send_error( __( 'Error: Nonce verification failed', 'erp' ) );
        }

        if ( ! erp_crm_is_current_user_manager() ) {
            $response['msg'] = __( 'Unauthorized operation', 'erp' );
        }

        if ( ! empty( $_POST['plugin'] ) && ! empty( $_POST['formId'] ) && ! empty( $_POST['map'] ) ) {
            $required_options = $this->get_required_crm_contact_options();

            // if map contains full_name, then remove first and last names from required options
            if ( in_array( 'full_name', $_POST['map'], true ) ) {
                $index = array_search( 'first_name', $required_options );
                unset( $required_options[ $index ] );

                $index = array_search( 'last_name', $required_options, true );
                unset( $required_options[ $index ] );

                array_unshift( $required_options, 'full_name' );
            }

            $diff = array_diff( $required_options, array_map( 'sanitize_text_field', wp_unslash( $_POST['map'] ) ) );

            if ( ! empty( $diff ) ) {
                $required_options = array_map( function ( $option ) {
                    return ucwords( str_replace( '_', ' ', $option ) );
                }, $required_options );

                $response['msg'] = sprintf(
                    __( '%s fields are required', 'erp' ),
                    implode( ', ', $required_options )
                );
            } elseif ( empty( $_POST['contactOwner'] ) && absint( $_POST['contactOwner'] ) ) {
                $response['msg'] = __( 'Please set a contact owner.', 'erp' );
            } else {
                $settings = get_option( 'wperp_crm_contact_forms' );

                $settings[ sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) ][ sanitize_text_field( wp_unslash( $_POST['formId'] ) ) ] = [
                    'map'           => array_map( 'sanitize_text_field', wp_unslash( $_POST['map'] ) ),
                    'contact_group' => isset( $_POST['contactGroup'] ) ? sanitize_text_field( wp_unslash( $_POST['contactGroup'] ) ) : '',
                    'contact_owner' => isset( $_POST['contactOwner'] ) ? sanitize_text_field( wp_unslash( $_POST['contactOwner'] ) ) : '',
                ];

                update_option( 'wperp_crm_contact_forms', $settings );

                $response = [
                    'success' => true,
                    'msg'     => __( 'Settings saved successfully', 'erp' ),
                ];
            }
        } elseif ( empty( $_POST['forms'] ) ) {
            $response['msg'] = __( 'No settings data found', 'erp' );
        }

        wp_send_json( $response );
    }

    /**
     * Ajax hook function to reset ERP Settings for a form
     *
     * @return void prints json object
     */
    public function reset_erp_settings() {
        $response = [
            'success' => false,
            'msg'     => null,
        ];

        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'erp_settings_contact_forms' ) ) {
            $this->send_error( __( 'Error: Nonce verification failed', 'erp' ) );
        }

        if ( ! erp_crm_is_current_user_manager() ) {
            $response['msg'] = __( 'Unauthorized operation', 'erp' );
        } elseif ( ! empty( $_POST['plugin'] ) && !empty( $_POST['formId'] ) ) {
            $settings = get_option( 'wperp_crm_contact_forms' );

            if ( ! empty( $settings[ $_POST['plugin'] ][ $_POST['formId'] ] ) ) {
                $map = $settings[ sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) ][ sanitize_text_field( wp_unslash( $_POST['formId'] ) ) ]['map'];

                unset( $settings[ $_POST['plugin'] ][ $_POST['formId'] ] );

                update_option( 'wperp_crm_contact_forms', $settings );

                // map the $map array to null values
                $map = array_map( function () {
                    return null;
                }, $map );

                $response = [
                    'success'      => true,
                    'msg'          => __( 'Settings reset successfully', 'erp' ),
                    'map'          => $map,
                    'contactGroup' => 0,
                    'contactOwner' => 0,
                ];
            } else {
                $response['msg'] = __( 'Nothing to reset', 'erp' );
            }
        }

        wp_send_json( $response );
    }
}
    