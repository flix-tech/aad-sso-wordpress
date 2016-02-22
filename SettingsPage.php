<?php

/**
 * Generated by the WordPress Option Page generator
 * at http://jeremyhixon.com/wp-tools/option-page/
 */

class AADSSO_Settings_Page {
	private $aadsso_settings;

	public function __construct( ) {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'reset_settings' ) );
		
		if( isset( $_GET['aadsso_reset'] ) && $_GET['aadsso_reset'] == 'success' )
    		add_action( 'all_admin_notices', array( $this, 'reset_successful' ) );

		/*
			loads the configuration, and assigns defaults for the form.
		*/
		$this->aadsso_settings = get_option( 'aadsso_settings', AADSSO_Settings::get_defaults( ) ); 
		
		
	}
	
	/**
	 * Runs on `admin_init`.  Will clear settings if $_GET['aadsso_nonce'] is set and if the nonce is valid.
	 */
	public function reset_settings( )
	{
		if ( isset( $_GET['aadsso_nonce'] ) && wp_verify_nonce( $_GET['aadsso_nonce'], 'aadsso_reset_settings' ) ) {
    		delete_option( 'aadsso_settings' );
    		wp_redirect( admin_url( 'options-general.php?page=aadsso_settings&aadsso_reset=success' ) );
    	}
	}
	
	
	public function reset_successful( )
	{
		echo '<div id="message" class="notice notice-warning"><p>'
			. __( 'Azure Active Directory Single Sign-on for WordPress Settings have been reset to default.', 'aad-sso-wordpress' )
			.'</p></div>';
	}

	public function add_options_page( ) {
		
    	
		add_options_page(
			'Azure Active Directory Settings', // page_title
			'Azure AD', // menu_title
			'manage_options', // capability
			'aadsso_settings', // menu_slug
			array( $this, 'render_admin_page' ) // function
		);
	}

	public function render_admin_page( ) {

	?>

		<div class="wrap">

			<h2>Azure Active Directory Single Sign-on Settings</h2>
			<p>Settings for Azure Active Directory can be configured here.</p>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'aadsso_settings_group' );
					do_settings_sections( 'aadsso_admin_page' );
					submit_button( );
				?>
			</form>

			<h3>Reset Plugin</h3>
			<p>
				<?php printf(
					'<a href="%s" class="button">%s</a> <span class="description">%s</span>',
					wp_nonce_url( admin_url( 'options-general.php?page=aadsso_settings' ), 'aadsso_reset_settings', 'aadsso_nonce' ),
					"Reset Settings",
					"Reset the plugin to default settings?  Careful! There is no undo for this."
					)
				?>
			</p>
	

		</div>
	<?php }

	public function register_settings( ) {
		register_setting(
			'aadsso_settings_group', // option_group
			'aadsso_settings', // option_name
			array( $this, 'sanitize_settings' ) // sanitize_callback
		);

		add_settings_section(
			'aadsso_settings_section', // id
			'Settings', // title
			array( $this, 'settings_section_info' ), // callback
			'aadsso_admin_page' // page
		);

		add_settings_field(
			'org_display_name', // id
			'Display Name', // title
			array( $this, 'display_name_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);
		
		add_settings_field(
			'org_domain_hint', // id
			'Organization Domain Hint', // title
			array( $this, 'org_domain_hint_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);

		add_settings_field(
			'client_id', // id
			'Azure Client ID', // title
			array( $this, 'azure_client_id_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);

		add_settings_field(
			'client_secret', // id
			'Azure Client Secret', // title
			array( $this, 'client_secret_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);

		add_settings_field(
			'field_to_match_to_upn', // id
			'Field to match to UPN', // title
			array( $this, 'upn_field_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);

		add_settings_field(
			'default_wp_role', // id
			'Default WordPress Role', // title
			array( $this, 'default_role_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);

		add_settings_field(
			'enable_auto_provisioning', // id
			'Enable Auto Provisioning?', // title
			array( $this, 'enable_auto_provisioning_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);
		
		add_settings_field(
			'enable_auto_forward_to_aad', // id
			'Enable Auto-Forward to Azure Active Directory?', // title
			array( $this, 'enable_auto_forward_to_aad_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);
		
		
		add_settings_field(
			'enable_aad_group_to_wp_role', // id
			'Enable AAD Group to WP Role?', // title
			array( $this, 'enable_aad_group_to_wp_role_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);

		add_settings_field(
			'role_map', // id
			'WordPress Role Map', // title
			array( $this, 'role_map_callback' ), // callback
			'aadsso_admin_page', // page
			'aadsso_settings_section' // section
		);
	}

	/**
	 * Returns an array of roles determined by other plugins to be "editable"
	 **/
	function get_editable_roles( ) {
		global $wp_roles;

		$all_roles = $wp_roles->roles;
		$editable_roles = apply_filters( 'editable_roles', $all_roles );

		return $editable_roles;
	}


	/**
	 * Cleans and conforms form information before saving.
	 * 
	 * @param array $input key-value information to be cleaned before saving.
	 */
	public function sanitize_settings( $input ) {
		$sanitary_values = array( );
		
		/* set strings */
		if ( isset( $input['org_display_name'] ) ) {
			$sanitary_values['org_display_name'] = sanitize_text_field( $input['org_display_name'] );
		}
		
		if ( isset( $input['org_domain_hint'] ) ) {
			$sanitary_values['org_domain_hint'] = sanitize_text_field( $input['org_domain_hint'] );
		}

		if ( isset( $input['client_id'] ) ) {
			$sanitary_values['client_id'] = sanitize_text_field( $input['client_id'] );
		}

		if ( isset( $input['client_secret'] ) ) {
			$sanitary_values['client_secret'] = sanitize_text_field( $input['client_secret'] );
		}

		/* set enumerated values */
		
		/*
			Default upn field is 'email'
		*/
		$sanitary_values['field_to_match_to_upn'] = "email";
		if ( isset( $input['field_to_match_to_upn'] )  && in_array( $input['field_to_match_to_upn'], array( 'email', 'login' ) ) ) {
			$sanitary_values['field_to_match_to_upn'] = $input['field_to_match_to_upn'];
		}

		/*
			Default subscriber role is 'subscriber'
		*/
		$sanitary_values['default_wp_role'] = 'subscriber';
		if ( isset( $input['default_wp_role'] ) ) {
			$sanitary_values['default_wp_role'] = sanitize_text_field( $input['default_wp_role'] );
		}
		
		/* 
			set booleans 
			--
			when key == value, this is considered true, otherwise false.
		*/
		foreach( array( 'enable_auto_provisioning', 'enable_auto_forward_to_aad', 'enable_aad_group_to_wp_role' ) as $boolean_setting )
		{
			if( isset( $input[$boolean_setting] ) ) {
				$sanitary_values[$boolean_setting] = ( $boolean_setting == $input[$boolean_setting] ) ? true : false;
			} else {
				$sanitary_values[$boolean_setting] = false;
			}
		}

		/*
			Many of the roles in WordPress will not have AD Groups associated with them.  Unset the role mapping if it is blank.
		*/
		if ( isset( $input['role_map'] ) ) {
			foreach( $input['role_map'] as $role_slug => $azure_guid )
			{
				if( "" === trim( $azure_guid ) )
					unset( $input['role_map'][$role_slug] );
			}
			$sanitary_values['role_map'] = $input['role_map'];
		}
		
		return $sanitary_values;
	}

	/**
	 * Requred callback for settings group
	 * If utilized, would output helptext above the settings section.
	 * */
	public function settings_section_info( )  {
		
	}

	/**
	 * Renders the `role_map` picker control.
	 **/
	function role_map_callback( ) {
		printf( '<p>%s</p>',
			'Map WordPress roles to Active Directory groups.'
		);
		echo '<table>';
		printf(
			'<thead><tr><th>%s</th><th>%s</th></tr></thead>',
			'WordPress Role',
			'Azure Group ID'
		);
		echo '<tbody>';
		foreach( $this->get_editable_roles( ) as $role_slug => $role )
		{
			echo '<tr>';
				echo '<td>';
					echo htmlentities( $role['name'] );
				echo '</td>';
				echo '<td>';
					printf(
						'<input type="text" class="regular-text" name="aadsso_settings[role_map][%1$s]" id="role_map_%1$s" value="%2$s" />',
						$role_slug,
						isset( $this->aadsso_settings['role_map'][$role_slug] ) ? esc_attr( $this->aadsso_settings['role_map'][$role_slug] ) : ''
					);
				echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * Renders the `org_display_name` form control.
	 **/
	public function display_name_callback( )  {
		printf(
			'<input class="regular-text" type="text" name="aadsso_settings[org_display_name]" id="org_display_name" value="%s" />',
			isset( $this->aadsso_settings['org_display_name'] ) ? esc_attr( $this->aadsso_settings['org_display_name'] ) : ''
		);
		printf(
			'<p class="description">%s</p>',
			'Display Name will be shown on the WordPress login screen.'
		);
	}
	
	/**
	 * Renders the `org_domain_hint` form control.
	 **/
	public function org_domain_hint_callback( )  {
		printf(
			'<input class="regular-text" type="text" name="aadsso_settings[org_domain_hint]" id="org_domain_hint" value="%s" />',
			isset( $this->aadsso_settings['org_domain_hint'] ) ? esc_attr( $this->aadsso_settings['org_domain_hint'] ) : ''
		);
		printf(
			'<p class="description">%s</p>',
			'This will be used to provide users a hint about the domain or tenant they will be logging in to.'
		);
	}

	/**
	 * Renders the `client_id` form control
	 **/
	public function azure_client_id_callback( ) {
		printf(
			'<input class="regular-text" type="text" name="aadsso_settings[client_id]" id="client_id" value="%s" />',
			isset( $this->aadsso_settings['client_id'] ) ? esc_attr( $this->aadsso_settings['client_id'] ) : ''
		);
		printf(
			'<p class="description">%s</p>',
			'Your Azure Active Directory Client ID.'
		);
	}

	/**
	 * Renders the `client_secret` form control
	 **/
	public function client_secret_callback( ) {
		printf(
			'<input class="regular-text" type="text" name="aadsso_settings[client_secret]" id="client_secret" value="%s" />',
			isset( $this->aadsso_settings['client_secret'] ) ? esc_attr( $this->aadsso_settings['client_secret'] ) : ''
		);
		printf(
			'<p class="description">%s</p>',
			'Your Azure Active Directory Client Secret.'
		);
	}

	/**
	 * Renders the `field_to_match_to_upn` form control.
	 **/
	public function upn_field_callback( ) {
		?> <select name="aadsso_settings[field_to_match_to_upn]" id="field_to_match_to_upn">
			<?php $selected = ( isset( $this->aadsso_settings['field_to_match_to_upn'] ) && $this->aadsso_settings['field_to_match_to_upn'] === 'email' ) ? 'selected' : '' ; ?>
			<option value="email" <?php echo $selected; ?>>Email Address</option>
			<?php $selected = ( isset( $this->aadsso_settings['field_to_match_to_upn'] ) && $this->aadsso_settings['field_to_match_to_upn'] === 'login' ) ? 'selected' : '' ; ?>
			<option value="login" <?php echo $selected; ?>>Login Name</option>
		</select> <?php
		printf(
			'<p class="description">%s</p>',
			'This specifies the WordPress user field to use for matching to Azure Active Directory UPN. Email Address is fine for most instances.'
		);
	}

	/**
	 * Renders the `default_wp_role` control.
	 **/
	public function default_role_callback( ) {
		// Default configuration should be most-benign
		if( !isset( $this->aadsso_settings['default_wp_role'] ) )
			$this->aadsso_settings['default_wp_role'] = "subscriber"
			
		?> <select name="aadsso_settings[default_wp_role]" id="default_wp_role">
			<?php foreach( $this->get_editable_roles( ) as $role_slug => $role ): ?>
			<?php $selected = (
				isset( $this->aadsso_settings['default_wp_role'] ) 
				&& $this->aadsso_settings['default_wp_role'] === $role_slug ) ? 'selected' : '' ; ?>
			<option value="<?php echo esc_attr( $role_slug ); ?>" <?php echo $selected; ?>><?php echo htmlentities( $role['name'] ); ?></option>
			<?php endforeach; ?>
		</select> <?php
		printf(
			'<p class="description">%s</p>',
			'This is the default role that users will be assigned if their WordPress account is automatically provisioned.'
		);
	}

	/**
	 * Renders the `enable_auto_provisioning` checkbox control.
	 **/
	public function enable_auto_provisioning_callback( ) {
		printf(
			'<input type="checkbox" name="aadsso_settings[enable_auto_provisioning]" id="enable_auto_provisioning" value="enable_auto_provisioning" %s> <label for="enable_auto_provisioning">%s</label>',
			( isset( $this->aadsso_settings['enable_auto_provisioning'] ) && $this->aadsso_settings['enable_auto_provisioning'] ) ? 'checked' : '',
			'Check to automatically create WordPress users when Azure Active Directory users login.'
		);
		
	}
	
	/**
	 * Renders the `enable_auto_forward_to_aad` checkbox control.
	 **/
	public function enable_auto_forward_to_aad_callback( ) {
		printf(
			'<input type="checkbox" name="aadsso_settings[enable_auto_forward_to_aad]" id="enable_auto_forward_to_aad" value="enable_auto_forward_to_aad" %s> <label for="enable_auto_forward_to_aad">%s</label>',
			( isset( $this->aadsso_settings['enable_auto_forward_to_aad'] ) && $this->aadsso_settings['enable_auto_forward_to_aad'] ) ? 'checked' : '',
			'Check to automatically forward users to the Azure Active Directory login screen. The WordPress login screen is bypassed when this is enabled.'
		);
	}
	
	/**
	 * Renders the `enable_aad_group_to_wp_role` checkbox control.
	 **/
	public function enable_aad_group_to_wp_role_callback( ) {
		printf(
			'<input type="checkbox" name="aadsso_settings[enable_aad_group_to_wp_role]" id="enable_aad_group_to_wp_role" value="enable_aad_group_to_wp_role" %s> <label for="enable_aad_group_to_wp_role">%s</label>',
			( isset( $this->aadsso_settings['enable_aad_group_to_wp_role'] ) && $this->aadsso_settings['enable_aad_group_to_wp_role'] ) ? 'checked' : '',
			'Check to automatically assign WordPress user roles based on Azure Active Directory group.'
		);
	}
	
	

}
