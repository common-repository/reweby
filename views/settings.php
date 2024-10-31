<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
wp_enqueue_script("jquery");
$error = $success =  '';

global $wp_roles;
// delete_option("reweby_record_main_settings");
$general_settings = get_option('reweby_record_main_settings');

$general_settings = empty($general_settings) ? array() : $general_settings;

if (isset($_POST['save'])) {
	$reweby_record_shipo_password = '';

		$general_settings['hit_global_roles'] = sanitize_text_field(isset($_POST['hit_global_roles']) ? implode(",", $_POST['hit_global_roles']) : '');
		$general_settings['hit_global_class_exclude'] = sanitize_text_field(isset($_POST['hit_global_class_exclude']) ? $_POST['hit_global_class_exclude'] : '');
		$general_settings['hit_global_mask_inputs'] = sanitize_text_field(isset($_POST['hit_global_mask_inputs']) ? $_POST['hit_global_mask_inputs'] : '');
		$general_settings['hit_global_record_canvas'] = sanitize_text_field(isset($_POST['hit_global_record_canvas']) ? $_POST['hit_global_record_canvas'] : '');
		$general_settings['reweby_record_signup'] = sanitize_text_field(isset($_POST['reweby_record_signup']) ? $_POST['reweby_record_signup'] : '');
		$general_settings['reweby_record_int_key'] = sanitize_text_field(isset($_POST['reweby_record_int_key']) ? $_POST['reweby_record_int_key'] : '');

		update_option('reweby_record_main_settings', $general_settings);

		$success = 'Settings Saved Successfully.';

		if (!isset($general_settings['reweby_record_int_key']) || empty($general_settings['reweby_record_int_key']) && isset( $_POST['reweby_record_password']) &&  $_POST['reweby_record_password'] != '') {
			$random_nonce = wp_generate_password(16, false);
			set_transient('hit_track_nonce_temp', $random_nonce, HOUR_IN_SECONDS);
			$reweby_record_shipo_password = sanitize_text_field(isset($_POST['reweby_record_password']) ? $_POST['reweby_record_password'] : '');
			
			$link_reweby_request = json_encode(array(
				'site_url' => site_url(),
				'site_name' => get_bloginfo('name'),
				'email_address' => $general_settings['reweby_record_signup'],
				'password' => $reweby_record_shipo_password,
				'nonce' => $random_nonce
			));

			$link_site_url = "https://app.reweby.com/json-api/link-site.php";
			$link_site_response = wp_remote_post(
				$link_site_url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
					'body'        => $link_reweby_request,
				)
			);

			$link_site_response = (is_array($link_site_response) && isset($link_site_response['body'])) ? json_decode($link_site_response['body'], true) : array();
			if ($link_site_response) {
				if ($link_site_response['status'] != 'error') {
					$general_settings['reweby_record_int_key'] = sanitize_text_field($link_site_response['integration_key']);
					update_option('reweby_record_main_settings', $general_settings);
					$success = 'Site Linked Successfully.<br><br> It\'s great to have you here. ' . (isset($link_site_response['trail']) ? 'Your 7days Trail period is started. To know about this more, please check your inbox.' : '') . '<br><br><button class="button" type="submit">Back to Settings</button>';
				} else {
					$error = '<p style="color:red;">' . $link_site_response['message'] . '</p>';
					$success = '';
				}
			} else {
				$error = '<p style="color:red;">Failed to connect with ReWeby</p>';
				$success = '';
			}
		}else if($general_settings['reweby_record_int_key'] == ''){
			$error = '<p style="color:red;">Please Fill the fields</p>';
			$success = '';
		}
}
?>

<style>
	.notice {
		display: none;
	}

	#multistepsform {
		width: 80%;
		margin: 50px auto;
		text-align: center;
		position: relative;
	}

	#multistepsform fieldset {
		background: white;
		text-align: left;
		border: 0 none;
		border-radius: 5px;
		box-shadow: 0 0 15px 1px rgba(0, 0, 0, 0.4);
		padding: 20px 30px;
		box-sizing: border-box;
		position: relative;
	}

	#multistepsform fieldset:not(:first-of-type) {
		display: none;
	}

	#multistepsform input[type=text],
	#multistepsform input[type=password],
	#multistepsform input[type=number],
	#multistepsform input[type=email],
	#multistepsform textarea {
		padding: 5px;
		width: 95%;
	}

	#multistepsform input:focus,
	#multistepsform textarea:focus {
		border-color: #679b9b;
		outline: none;
		color: #637373;
	}

	#multistepsform .action-button {
		width: 100px;
		background: #ff9a76;
		font-weight: bold;
		color: #fff;
		transition: 150ms;
		border: 0 none;
		float: right;
		border-radius: 1px;
		cursor: pointer;
		padding: 10px 5px;
		margin: 10px 5px;
	}

	#multistepsform .action-button:hover,
	#multistepsform .action-button:focus {
		box-shadow: 0 0 0 2px #f08a5d, 0 0 0 3px #ff976;
		color: #fff;
	}

	#multistepsform .fs-title {
		font-size: 15px;
		text-transform: uppercase;
		color: #2c3e50;
		margin-bottom: 10px;
	}

	#multistepsform .fs-subtitle {
		font-weight: normal;
		font-size: 13px;
		color: #666;
		margin-bottom: 20px;
	}

	#multistepsform #progressbar {
		margin-bottom: 30px;
		overflow: hidden;
		counter-reset: step;
	}

	#multistepsform #progressbar li {
		list-style-type: none;
		color: #FF6600;
		text-transform: uppercase;
		font-size: 9px;
		width: 48%;
		float: left;
		position: relative;
	}

	#multistepsform #progressbar li:before {
		content: counter(step);
		counter-increment: step;
		width: 20px;
		line-height: 20px;
		display: block;
		font-size: 10px;
		color: #fff;
		background: #FF6600;
		border-radius: 3px;
		margin: 0 auto 5px auto;
	}

	#multistepsform #progressbar li:after {
		content: "";
		width: 100%;
		height: 2px;
		background: #FF6600;
		position: absolute;
		left: -50%;
		top: 9px;
		z-index: -1;
	}

	#multistepsform #progressbar li:first-child:after {
		content: none;
	}

	#multistepsform #progressbar li.active {
		color: #4D148C;
	}

	#multistepsform #progressbar li.active:before,
	#multistepsform #progressbar li.active:after {
		background: #4D148C;
		color: white;
	}
	.separator {
  display: flex;
  align-items: center;
  text-align: center;
}

.separator::before,
.separator::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid #666;
}

.separator:not(:empty)::before {
  margin-right: .25em;
}

.separator:not(:empty)::after {
  margin-left: .25em;
}
	.setting {
		border: 0px;
		padding: 10px 5px;
		margin: 10px 5px;
		background-color: #ff9a76 !important;
		font-weight: bold;
		color: #ffffff !important;
		border-radius: 3px;
	}
</style>
<?php 

if ($success != '') {
	echo '<form id="multistepsform" method="post"><fieldset>
    <center><h2 class="fs-title" style="line-height:27px;">' . esc_attr($success) . '</h2>
	<input type="button" class="setting" value="GO TO SETTINGS" onClick="window.location.href=window.location.href">
	</center></form>';
} else {
?>
	<!-- multistep form -->
	<form id="multistepsform" method="post">

		<!-- progressbar -->
		<ul id="progressbar">
			<li class="active">Global Settings</li>
			<li>Connect</li>

		</ul>
		<?php if ($error == '') {

		?>
			<!-- fieldsets -->
			<fieldset>
				<center>
					<h2 class="fs-title">Global Settings</h2>
					
					<?php _e('<b>Select User Roles to record:</b><p style="color:red;">(Leave it empty, If you are going to configure it in user edit page for induviuals)</p>', 'reweby_record') ?>
							<?php
								$saved_roles  = (isset($general_settings['hit_global_roles']) && !empty($general_settings['hit_global_roles'])) ? explode(",", $general_settings['hit_global_roles']) : array(); 
								foreach($wp_roles->roles as $key => $role){
									echo "<span style='margin-left: 10px' ><input type='checkbox' name='hit_global_roles[]' value='". $key ."' ". (in_array($key, $saved_roles) ? "checked='true'" : '') .">". $role["name"] ."</span>";
								}
								echo "<span style='margin-left: 10px' ><input type='checkbox' name='hit_global_roles[]' value='guest' ". (in_array("guest", $saved_roles) ? "checked='true'" : '') .">Guest users</span>";

							?>
				</center>
				<table style="width:100%;margin-top:20px;">
					
					<tr>
						<td style="width: 100%;padding:10px;" colspan="2">
							<?php _e('Class Names to Exclude. (Separate by comma to add more classes)', 'reweby_record') ?>
							<input type="text" class="input-text regular-input" name="hit_global_class_exclude" value="<?php echo (isset($general_settings['hit_global_class_exclude'])) ? $general_settings['hit_global_class_exclude'] : ''; ?>">
						</td>
						
					</tr>
					<tr>
						<td style="width:50%;padding:10px;">
							<?php _e('Mask all Input Elements', 'reweby_record') ?><br>
							<select name="hit_global_mask_inputs" class="wc-enhanced-select" style="width:95%;padding:5px;">
								<option value="true" <?php echo (isset($general_settings['hit_global_mask_inputs']) && $general_settings['hit_global_mask_inputs'] == 'true') ? 'Selected="true"' : ''; ?>> True </option>
								<option value="false" <?php echo (isset($general_settings['hit_global_mask_inputs']) && $general_settings['hit_global_mask_inputs'] == 'false') ? 'Selected="true"' : ''; ?>> False </option>
							</select>
						</td>
						<td style="padding:10px;">
						<?php _e('Record Canvas', 'reweby_record') ?><br>
							<select name="hit_global_record_canvas" class="wc-enhanced-select" style="width:95%;padding:5px;">
								<option value="false" <?php echo (isset($general_settings['hit_global_record_canvas']) && $general_settings['hit_global_record_canvas'] == 'false') ? 'Selected="true"' : ''; ?>> False </option>
								<option value="true" <?php echo (isset($general_settings['hit_global_record_canvas']) && $general_settings['hit_global_record_canvas'] == 'true') ? 'Selected="true"' : ''; ?>> True </option>
							</select>
						</td>
					</tr>


				</table>
				<center><p style="color:green;margin:20px;">NOTE: We wont record the password and Payment related fields by default</p></center>
				<?php if (isset($general_settings['reweby_record_int_key']) && $general_settings['reweby_record_int_key'] != '') {
					echo '<input type="submit" name="save" class="action-button" style="width:auto;float:left;" value="Save Changes" />';
				}

				?>
				<input type="button" name="next" class="next action-button" value="Next" />
			</fieldset>

		<?php }
		?>
		<fieldset>
			<center>
				<h2 class="fs-title">LINK ReWeby</h2><br>
				<h3 class="fs-subtitle">ReWeby is performs all the operations in its own server. So it won't affect your page speed or server usage.</h3>
				</center>
				<table style="width:100%;text-align:center;">
				
				<tr>
					<td>
					
					<?php _e('ReWeby Integration Key', 'reweby_record') ?><br>
						<input style="width:330px;" type="text" id="intergration" name="reweby_record_int_key" placeholder="" value="<?php echo (isset($general_settings['reweby_record_int_key'])) ? $general_settings['reweby_record_int_key'] : ''; ?>"><br><br>
					</td>
				</tr>
				<?php
				if (!isset($general_settings['reweby_record_int_key']) || empty($general_settings['reweby_record_int_key'])) {
				?>
					<tr>
						<td style="padding:10px;">
						<div class="separator">OR</div><br>
						<h2 class="fs-title">CREATE ReWeby Account</h2>
						</td>
					</tr>

					<tr>
						<td style=" width: 50%;padding:10px;">
							<?php _e('Email address to signup / check the registered email.', 'reweby_record') ?><font style="color:red;">*</font><br>
							<input type="email" style="width:330px;" placeholder="Enter email address" name="reweby_record_signup" placeholder="" value="<?php echo (isset($general_settings['reweby_record_shipo_signup'])) ? $general_settings['reweby_record_shipo_signup'] : ''; ?>">
						</td>

					</tr>
					<tr>
						<td style=" width: 50%;padding:10px;">
							<?php _e('password.', 'reweby_record') ?><font style="color:red;">*</font><br>
							<input type="password" style="width:330px;" placeholder="Enter password" name="reweby_record_password" placeholder="" value="">
						</td>

					</tr>

					<tr>
						<td style="padding:10px;">
							<hr>
						</td>
					</tr>


					</table>

		<?php } else {
		?>
			

		</table>
			<p style="font-size:14px;line-height:24px;">
				Site Linked Successfully. <br><br>
				It's great to have you here. Your account has been linked successfully with reweby. <br><br>
				Make your customers happier by reacting faster and handling their service requests in a timely manner, meaning higher store reviews and more revenue.</p>
		<?php
					echo '</center>';
				}
		?>
		<?php echo '<center>' . $error . '</center>'; ?>

		<?php if (!isset($general_settings['reweby_record_int_key']) || empty($general_settings['reweby_record_int_key'])) {
		?>
			<input type="submit" name="save" class="action-button" style="width:auto;" value="SAVE & START 5 days Free Trail" />
		<?php	} else {	?>
			<input type="submit" name="save" class="action-button" style="width:auto;" value="Save Changes" />
		<?php	}	?>

		<input type="button" name="previous" class="previous action-button" value="Previous" />
		</fieldset>
	<?php
} ?>
	</form>
	<center><a href="https://app.reweby.com/support" target="_blank" style="width:auto;margin-right :20px;" class="button button-primary">Trouble in configuration? / not working? Email us.</a>
		<a href="https://calendly.com/reweby/30min" target="_blank" style="width:auto;" class="button button-primary">Looking for demo ? Book your slot with our expert</a></center>

	<script type="text/javascript">
		var current_fs, next_fs, previous_fs;
		var left, opacity, scale;
		var animating;
		jQuery(".next").click(function() {
			if (animating) return false;
			animating = true;

			current_fs = jQuery(this).parent();
			next_fs = jQuery(this).parent().next();
			jQuery("#progressbar li").eq(jQuery("fieldset").index(next_fs)).addClass("active");
			next_fs.show();
			document.body.scrollTop = 0; // For Safari
			document.documentElement.scrollTop = 0;
			current_fs.animate({
				opacity: 0
			}, {
				step: function(now, mx) {
					scale = 1 - (1 - now) * 0.2;
					left = now * 50 + "%";
					opacity = 1 - now;
					current_fs.css({
						transform: "scale(" + scale + ")"
					});
					next_fs.css({
						left: left,
						opacity: opacity
					});
				},
				duration: 0,
				complete: function() {
					current_fs.hide();
					animating = false;
				},
				//easing: "easeInOutBack"
			});
		});

		jQuery(".previous").click(function() {
			if (animating) return false;
			animating = true;

			current_fs = jQuery(this).parent();
			previous_fs = jQuery(this).parent().prev();
			jQuery("#progressbar li")
				.eq(jQuery("fieldset").index(current_fs))
				.removeClass("active");

			previous_fs.show();
			current_fs.animate({
				opacity: 0
			}, {
				step: function(now, mx) {
					scale = 0.8 + (1 - now) * 0.2;
					left = (1 - now) * 50 + "%";
					opacity = 1 - now;
					current_fs.css({
						left: left
					});
					previous_fs.css({
						transform: "scale(" + scale + ")",
						opacity: opacity
					});
				},
				duration: 0,
				complete: function() {
					current_fs.hide();
					animating = false;
				},
				//easing: "easeInOutBack"
			});
		});

		jQuery(".submit").click(function() {
			return false;
		});

	</script>
