<?php
/**
 * Copyright (c) 2021 Kount, Inc.

 * This file is part of Kount Fraud Prevention.

 * Kount Fraud Prevention is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Kount Fraud Prevention is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kount Fraud Prevention.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/admin/partials
 */
?>
<?php
class KFPWOO_Download_Log_File{

	public $upload_path;
	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct(){
		$uploads = wp_upload_dir();
		$this->upload_path = $uploads['basedir'].'/wc-logs' ;
	}

	/**
	 * kfpwoo_server_protocol
	 * returing server protocol
	 * @return string
	 */
	public function kfpwoo_server_protocol(){
		$protocol = (!empty(sanitize_text_field($_SERVER['HTTPS'])) && sanitize_text_field($_SERVER['HTTPS']) !== 'off' || sanitize_text_field($_SERVER['SERVER_PORT']) == 443) ? "https://" : "http://";
		$host = isset($_SERVER['HTTP_HOST'])?sanitize_text_field($_SERVER['HTTP_HOST']):'';
		$request_uri = isset($_SERVER['REQUEST_URI'])?sanitize_text_field($_SERVER['REQUEST_URI']):'';
		return $protocol.$host.$request_uri;
	}

	/**
	 * kfpwoo_download_log
	 * creating zip and downloading file
	 * @param  mixed $download_file
	 * @return void
	 */
	public function kfpwoo_download_log($download_file){
		//checking if folder exists or not
		if(is_dir($this->upload_path)){
			$filepath  =  $this->upload_path."/".$download_file;
			if(file_exists($filepath)) {
				header('Cache-Control: public');
				header('Content-Description: File Transfer');
				header('Content-Disposition: attachment; filename="'.basename($download_file).'"');
				header("Content-Type: application/zip");
				header("Content-Transfer-Encoding: binary");
				header("Pragma: no-cache");
				header("Expires: 0");
				//clean headers
				ob_clean();
				flush();
				//read file
				readfile($filepath);
				exit;
			}
		}
	}

	/**
	 * kfpwoo_sort_logs_list_array
	 * returing logs files creation dates array
	 * @return array
	 */
	public function kfpwoo_sort_logs_list_array(){
		//creating file creation dates array
		$files_arr	=	[];
		if(is_dir($this->upload_path)){
			$files	=	opendir($this->upload_path);
			if($files){
				while(($filename  =	readdir($files)) != false ){
					if($filename != '.' && $filename !=".." && strpos($filename, 'kount_logs-') !== false){
						$file_date = str_replace("kount_logs-","",$filename);
						$file_date = str_replace(".log","",$file_date);
						array_push($files_arr, $file_date);
					}
				}
			}
		}
		return $files_arr;
	}

	/**
	 * kfpwoo_logs_files_option_list
	 * creating option of log files
	 * @param  mixed $files_arr_new
	 * @return void
	 */
	public function kfpwoo_logs_files_option_list($files_arr_new){
		foreach($files_arr_new as $key => $filename){
			echo '<option value="kount_logs-'.esc_attr($filename).'.log" '.($key == 0?'selected':'').'>kount_logs-'.esc_attr($filename).'.log</option>';
		}
	}

	public function kfpwoo_section_data(){
		$section_array = array (
			array("Account Information","account-information",''),
			array("Payment Settings","payment-functionality"),
		  	array("Event Logging","event_logging",''),
		  );
		  return $section_array;
	}
}

//object of class
$download_obj = new KFPWOO_Download_Log_File();

//if clicked on download button
if(isset($_GET['download_file']))
{
	$download_obj->kfpwoo_download_log(sanitize_text_field($_GET['download_file']));
}
//file dates array
$files_arr = $download_obj->kfpwoo_sort_logs_list_array();
//function for sort dates
function kfpwoo_date_sort($a, $b) {
	return strtotime($b) - strtotime($a);
}
usort($files_arr, "kfpwoo_date_sort");
//server protocol
$protocol = $download_obj->kfpwoo_server_protocol();

//section array
$section_array = $download_obj->kfpwoo_section_data();
?>

<?php if( isset($_GET['settings-updated']) ) {
	settings_errors();
} ?>


<!-- The Modal -->
<div id="kountModal" class="modal kount-admin-content">
	<!-- Modal content -->
	<div class="modal-content">
		<span class="close_kount_modal">&times;</span><!-- .close_kount_modal-->
	</div>
</div><!--Modal-->
<form method="post" action="options.php" class="kount-admin-content"><!--form start-->
	<div class="wrap"><!-- .wrap-->

	<div id="icon-options-general" class="icon32"></div><!-- #icon-options-general-->

	<div class="wrap">
		<div id="col-container">
			<div id="col-right">
				<div class="col-wrap">
					<div style="text-align: right;">
						<img src="<?php echo esc_url(plugins_url( '/assets/images/kount_logo.svg', dirname(__FILE__)));?>" />
					</div>
				</div>
			</div>
			<div id="col-left">
				<div class="col-wrap">
					<h1><?php esc_attr_e( 'Kount', 'kount-fraud-prevention' ); ?></h1>
					<div id="notice_block">
						<div id="setting-notice" class="notice notice-success is-dismissible">
							<p><strong><?php esc_attr_e('Settings saved.','kount-fraud-prevention') ?></strong></p>
							<button type="button" class="notice-dismiss">
								<span class="screen-reader-text">Dismiss this notice.</span>
							</button>
						</div>
					</div>
					<h2><?php esc_attr_e( 'A WooCommerce Extension', 'kount-fraud-prevention' ); ?></h2>
					<h1><?php esc_attr_e( 'Configuration Settings', 'kount-fraud-prevention' ); ?></h1>
				</div>
			</div>

		</div>
	</div>

	<div id="poststuff" ><!-- #poststuff start-->

		<div id="post-body" class="metabox-holder columns-2">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

					<div class="postbox">
					<!--Section-->
						<?php
							for ($row = 0; $row < count($section_array); $row++) {
									?>
									<div class="setttings-section">
										<div class="collapsible">
											<div class="left-col">
												<h2><?php esc_attr_e( $section_array[$row][0], 'WpAdminStyle' ); ?></h2>
											</div><!-- .left-col-->
											<div class="right-col" style="text-align: right;">
											<img src="<?php echo esc_url(plugins_url( '/assets/images/expand_icon.svg', dirname(__FILE__)));?>" />
											</div><!-- .right-col-->
										</div>
										<div class="content" align="right">
											<div class="inside-div"><?php
												settings_fields( 'kfpwoo_option_group' );
												do_settings_sections( $section_array[$row][1] );
											?>
											<?php
											if($row == 0){
												?>
													<p class="submit"><input type="button" name="regenerate_btn" id="regenerate_btn" class="button button-primary" value="Regenerate Consumer key & secret key"></p>
												<?php
											}
											if($row == 2){
												?>
												<table class="form-table" role="presentation">
													<tbody>
														<tr>
															<th scope="row"><?php esc_attr_e( 'Download log file', 'WpAdminStyle' ); ?></th>
															<td>
																<select name="logs_files" id="logs_files" class="regular-text" style="width: 240px;">
																	<?php
																		$download_obj->kfpwoo_logs_files_option_list($files_arr);
																	?>
																</select>
																<input type="hidden" id="kount_page_url" value="<?php echo esc_url($protocol); ?>" />
																<a name="download_btn" id="download_btn" class="button button-primary" href="#"><?php esc_attr_e( 'Download file', 'WpAdminStyle' ); ?></a>
															</td>
														</tr>
													</tbody>
												</table>
												<?php
											}
											?>
											</div><!-- inside-div-->
									</div><!-- .collapsible-->
									<hr />
								</div><!-- .setttings-section -->
								<?php
							}
						?>
						<div class="setttings-section">
							<div class="submit-button" align="right" style="display:none;"><?php submit_button();?></div>
							<div class="submit-button" align="right">
								<p class="submit"><input type="button" name="submit_btn" id="submit_btn" class="button button-primary" value="Save Changes"></p>
							</div><!--Custom .submit-button -->
						</div> <!-- .setttings-section -->
					</div>
					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables .ui-sortable -->

			</div>
			<!-- #postbox-container-1 .postbox-container -->

		</div>
		<!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div>
	<!-- #poststuff -->

</div> <!-- .wrap -->

</form><!--form end-->
