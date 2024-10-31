<?php
/*
Plugin Name: ReWeby 
Plugin URI: #
Description: User Activity Tracking & Video Reording
Author: ReWeby
Version: 2.0.4
Author URI: https://reweby.com/
*/
if( !class_exists('HITUsrActivityRecording') ){
    Class HITUsrActivityRecording
    {
        private $errror = '';
        public function __construct() {
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
            add_action( 'admin_menu', array($this, 'menu_page' ));
            add_action( 'show_user_profile', array($this, 'show_edit_user_profile' ));
            add_action( 'edit_user_profile', array($this, 'show_edit_user_profile' ));
            add_action( 'user_new_form', array($this, 'user_new_form' ));
            add_action( 'personal_options_update', array($this, 'save_user_profile_fields' ));
            add_action( 'edit_user_profile_update', array($this, 'save_user_profile_fields' ));
            add_action( 'user_register', array($this, 'save_user_profile_fields' ));
            add_action( 'reweby_delete_user_cron_event', array($this, 'reweby_delete_user_cron_event' ));
            add_action( 'init', array($this,'init') );
            add_action( 'admin_footer', array($this,'admin_footer') );
            add_action( 'wp_footer', array($this,'admin_footer') );
            add_filter('wp_head', array($this, 'order_received_title'));

            add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts') );
            add_action( 'wp_enqueue_scripts', array($this,'enqueue_scripts') );
        }
        function order_received_title() {
            if(function_exists("is_order_received_page")){
                if (is_order_received_page()) {
                    echo '<input type="hidden" value="Order Placed Successfully" id="reweby_override_title">';
                }
            }
            
        }
        public function action_links($links){
            $plugin_links = array(
                '<a href="' . admin_url( 'options-general.php?page=usractv' ) . '" style="color:green;">' . __( 'Configure', 'usractv' ) . '</a>',
                '<a href="https://support.reweby.com/support" target="_blank" >' . __('Support', 'usractv') . '</a>'
                );
            return array_merge( $plugin_links, $links );
        }
        function menu_page() {	
            add_submenu_page( 'options-general.php', 'ReWebY', 'ReWebY - Record', 'manage_options', 'usractv', array($this, 'usractv') ); 
        }
        function usractv(){
            include_once("views/settings.php");
        }
        function user_new_form(){
            ?>
           <h3><?php _e("reweby", "usractv"); ?></h3>

            <table class="form-table">
            <tr>
                <th><label for="reweby_user_level_tracking"><?php _e("Enable Tracking"); ?></label></th>
                <td>
                    <input type="checkbox" name="reweby_user_level_tracking" id="reweby_user_level_tracking" value="true" class="regular-text" />
                    <span class="description" style="vertical-align:middle;"><?php _e("Enable this option to record the user activity."); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="reweby_user_delete"><?php _e("Delete the user after x days"); ?></label></th>
                <td>
                    <input type="number" name="reweby_user_delete" id="reweby_user_delete" class="regular-text" />
                    <span class="description" style="vertical-align:middle;"><?php _e("Days."); ?></span>
                </td>
            </tr>
            </table>
           <?php
        }
        function show_edit_user_profile($user){
           ?>
           <h3><?php _e("reweby", "usractv"); ?></h3>

            <table class="form-table">
            <tr>
                <th><label for="reweby_user_level_tracking"><?php _e("Enable Tracking"); ?></label></th>
                <td>
                    <input type="checkbox" name="reweby_user_level_tracking" id="reweby_user_level_tracking" <?php echo ((get_the_author_meta( 'reweby_user_level_tracking', $user->ID ) == "true") ? "checked='true'" : ""  ); ?> value="true" class="regular-text" />
                    <span class="description" style="vertical-align:middle;"><?php _e("Enable this option to record the user activity."); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="reweby_user_delete"><?php _e("Delete the user after x days"); ?></label></th>
                <td>
                    <input type="number" name="reweby_user_delete" id="reweby_user_delete" value="<?php echo esc_attr( get_the_author_meta( 'reweby_user_delete', $user->ID ) ); ?>" class="regular-text" />
                    <span class="description" style="vertical-align:middle;"><?php _e("Days."); ?></span>
                </td>
            </tr>
            </table>
           <?php
        }
        function save_user_profile_fields( $user_id ) {
            if ( !current_user_can( 'edit_user', $user_id ) ) { 
                return false; 
            }
            if(isset($_POST['reweby_user_level_tracking'])){
                $user_tracking = sanitize_text_field($_POST['reweby_user_level_tracking']);
                update_user_meta( $user_id, 'reweby_user_level_tracking', $user_tracking );
            }
            if(isset($_POST['reweby_user_delete'])){
                $user_delete = sanitize_text_field($_POST['reweby_user_delete']);
                if($reweby_user_delete){
                    wp_schedule_single_event( time() + (3600 * (24 * $user_delete)), "reweby_delete_user_cron_event", array($user_id) );
                }
                update_user_meta( $user_id, 'reweby_user_delete', $user_delete );
            }
            
        }
        function reweby_delete_user_cron_event($user){
            require_once(ABSPATH.'wp-admin/includes/user.php' );
            wp_delete_user($user);
        }
        public function init(){
           

            if(isset($_GET['hit_track_user_key'])){
                $hitshipo_key = sanitize_text_field($_GET['hit_track_user_key']);
                if($hitshipo_key == 'fetch'){
                    echo json_encode(array(get_transient('hit_track_nonce_temp')));
                    die();
                }
            }
        }
        function admin_footer(){
            global $current_user;
            wp_get_current_user();

            $general_settings = get_option('reweby_record_main_settings');
         
            $general_settings = empty($general_settings) ? array() : $general_settings;
            if(isset($general_settings['reweby_record_int_key'])){
                $show = false;
                $saved_roles = explode(",", $general_settings['hit_global_roles']);

                if ( !empty( $current_user->roles ) && is_array( $current_user->roles )) {
                    foreach ( $current_user->roles as $role ){
                        if(in_array($role, $saved_roles)){
                            $show = true;
                        }
                    }
                }

                $folder_name = "";
                if(!is_user_logged_in() && in_array("guest", $saved_roles)){
                    $show = true;
                    $folder_name = "Guest User";
                }else{
                    $folder_name = $current_user->user_email;
                }

                if($current_user && $show == false){
                    $user_level = get_user_meta($current_user->ID, "reweby_user_level_tracking", TRUE);
                    if($user_level == "true"){
                        $show = true;
                    }
                }

                if($show == true){
                ?>
                <script type="text/javascript">
                    let reweby_events = [];

                    reweby.record({
                    emit(event) {
                        // push event into the events array
                        if(event){
                            reweby_events.push(event);
                        }
                    },
                    packFn: reweby.pack,
                    ignoreClass: '<?php echo esc_attr($general_settings['hit_global_class_exclude']); ?>',
                    maskAllInputs: <?php echo esc_attr($general_settings['hit_global_mask_inputs']) == "true" ? 1 : 0; ?>,
                    recordCanvas: <?php echo esc_attr($general_settings['hit_global_record_canvas']) == "true" ? 1 : 0; ?>});

                    // this function will send events to the backend and reset the events array
                    function save() {
                        var hit_act_sess_id = sessionStorage.getItem('hit_act_sess_id');
                        if(!hit_act_sess_id){
                            hit_act_sess_id = Math.floor(100000 + Math.random() * 900000);
                            sessionStorage.setItem('hit_act_sess_id', hit_act_sess_id);
                        }

                        if(reweby_events && reweby_events.length > 0){
                            var reweby_override_title = document.getElementById("reweby_override_title");
                            var pname = document.title;
                            if (reweby_override_title && reweby_override_title.value) {
                                pname = reweby_override_title.value;
                            }
                            const body = JSON.stringify(reweby_events);
                            reweby_events = [];
                            fetch('https://app.reweby.com/json-api/v3/record.php?key=<?php echo esc_attr($general_settings['reweby_record_int_key']); ?>&fname=<?php echo  esc_attr($folder_name); ?>&sname=' + hit_act_sess_id + '&pname=' + pname, {
                                method: 'POST',
                                body,
                            });
                        }
                        
                    }

                    setInterval(save, 2 * 1000);
                </script>
                <?php
                }
             
            }
        }
        
        function enqueue_scripts(){
            wp_enqueue_script('jquery');
            wp_enqueue_script('reweby_script', plugins_url('js/record.js',__FILE__ ));
        }
    }
    
}
new HITUsrActivityRecording();