<?php

	add_action('admin_menu', 'MaribolIMDB_add_pages');
	
	function MaribolIMDB_add_pages() {
		add_menu_page('Maribol IMDB', 'Maribol IMDB', 'administrator', 'maribol_imdb', 'maribol_imdb', plugins_url('maribol-imdb/imdb.png'));

		add_submenu_page('maribol_imdb', 'Movies List', 'Movies List', 'administrator', 'maribol-imdb', 'maribol_imdb');
		add_submenu_page('maribol_imdb', 'Add Movie', 'Add Movie', 'administrator', 'maribol_imdb_import', 'maribol_imdb_import');
		add_submenu_page('maribol_imdb', 'Options', 'Options', 'administrator', 'maribol-imdb-options', 'maribol_imdb_options');
		add_submenu_page('maribol_imdb', 'Donate', '<b style="color:#cc0000;">Donate</b>', 'administrator', 'maribol_imdb_donate', 'maribol_imdb_donate');

		remove_submenu_page('maribol_imdb','maribol_imdb');
	}
	
	function maribol_imdb_import(){
	
		if($_POST['submit'] != ''){
			MaribolIMDB_create_cache(str_replace('tt', '', $_POST['maribol-imdb-imdb_id']));
			$saved = 1;
		}
		
		if($saved){
			?><div style="padding:5px;background:lightYellow;border:1px solid #E6DB55;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">Movie successfully cached</div><?php
		}
		
		?>
		<div class="wrap">
			<h2>Maribol IMDB - Add Movie</h2> 
			<?php if($saved==1):?><div style="padding:5px;background:lightYellow;border:1px solid #E6DB55;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">Settings saved</div><?php endif;?>
			<form method="post" action="admin.php?page=maribol_imdb_import"> 
			<input type='hidden' name='option_page' value='general' /><input type="hidden" name="action" value="update" /><input type="hidden" id="_wpnonce" name="_wpnonce" value="da140e65f5" /><input type="hidden" name="_wp_http_referer" value="/wp/wp-admin/options-general.php" /> 
				<table class="form-table"> 
					<tr valign="top"> 
						<th scope="row"><label for="maribol-imdb-copy_long_desc">IMDB ID</label></th> 
						<td> 
							<input type="text" name="maribol-imdb-imdb_id"><br />
							<small>Example: tt1399103</small>
						</td> 
					</tr> 
					<tr valign="top"> 
						<td> 
							<input type="submit" name="submit" id="submit" class="button-primary" value="Save Options"  />
						</td> 
					</tr> 
				</table>
				
			</form>
		</div>
		<?php
	}
	
	function maribol_imdb(){
		MaribolIMDB_doCacheNonCachedMovies();
		$movies = MaribolIMDB_doOpenMoviesCache();
		?>
		<div class="wrap">
		<h2>Maribol IMDB - Movies List from cache</h2>
		<?php
		
			if($_GET['action'] == 'refresh'){
						
				?><div style="padding:5px;background:lightYellow;border:1px solid #E6DB55;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">Movie successfully cached</div><?php
				
				foreach($movies as $key=>$movie){
					if($movie['imdb_id'] == $_GET['id']){
					
						$movie_CACHE_ = @json_decode(@file_get_contents(CACHE_PATH.'tt'.$_GET['id'].'.db'), true);
						@unlink(POSTER_CACHE_PATH.$movie_CACHE_['poster_small']);
						@unlink(POSTER_CACHE_PATH.$movie_CACHE_['poster']);
						
						$list_cache = array(
							'title' => $movie['title'],
							'year' => $movie['year'],
							'imdb_id' => $movie['imdb_id'],
							'last_cache' => time()
						);
						$movies[$key] = $list_cache;
			
						break;
					}
				}
				
				@unlink(CACHE_PATH.'tt'.$_GET['id'].'.db');
				MaribolIMDB_refresh_cache($_GET['id']);
				
				$fh = @fopen(MOVIE_CACHE_LIST, 'w');
				@fwrite($fh, @json_encode($movies));
				@fclose($fh);
						
			}elseif($_GET['action'] == 'delete'){
				foreach($movies as $key=>$movie){
					if($movie['imdb_id'] == $_GET['id']){
						unset($movies[$key]);
						$movie = @json_decode(@file_get_contents(CACHE_PATH.'tt'.$_GET['id'].'.db'), true);
						@unlink(POSTER_CACHE_PATH.$movie['poster_small']);
						@unlink(POSTER_CACHE_PATH.$movie['poster']);
						@unlink(CACHE_PATH.'tt'.$_GET['id'].'.db');
						
						
						$fh = @fopen(MOVIE_CACHE_LIST, 'w');
						@fwrite($fh, @json_encode($movies));
						@fclose($fh);
			
						break;
					}
				}
				?><div style="padding:5px;background:lightYellow;border:1px solid #E6DB55;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">Movie successfully deleted</div><?php
			}
			
		?>
			<table class="wp-list-table widefat fixed bookmarks" cellspacing="0">
				<thead>
				<tr>
					<th scope="col" class="manage-column" style=""><span>Title (Year)</span></th>
					<th scope="col" class="manage-column" style="width:150px;">IMDb ID</th>
					<th scope="col" class="manage-column" style="width:250px;">Last cache date</th>
				</tr>
				</thead>
				<tbody id="the-list">
					<?php
						$i = 0;
						foreach($movies as $movie){
					?>
					<tr valign="middle"<?php echo ($i % 2) ? '' : 'class="alternate"';?>>
						<td class="column-name">
							<strong><?php echo $movie['title'];?> (<?php echo (int)$movie['year'];?>)</strong>
							<div class="row-actions">
								<span class="delete"><a class="submitdelete" href="admin.php?page=maribol-imdb&action=delete&id=<?php echo $movie['imdb_id'];?>" onclick="if ( confirm( 'You are about to delete this movie from cache \'Cancel\' to stop, \'OK\' to delete.' ) ) { return true;}return false;">Delete</a> | </span>
								<span class="edit"><a href="admin.php?page=maribol-imdb&action=refresh&id=<?php echo $movie['imdb_id'];?>">Refresh cache</a></span>
							</div>
						</td>
						<td><a href="http://www.imdb.com/title/tt<?php echo $movie['imdb_id'];?>/" target="_blank">tt<?php echo $movie['imdb_id'];?></a></td>
						<td><?php echo @date('d M, Y', $movie['last_cache']);?> at <?php echo @date('H:i A', $movie['last_cache']);?><br /></td>
					</tr>
					<?php $i++;}?>
				</tbody>
			</table>
			<?php if($i==0){?><div style="padding:5px;background:lightYellow;border:1px solid #E6DB55;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">There are no movies in cache</div><?php }?>
		</div>
		<?php
	}
	
	function maribol_imdb_options(){
	
		if($_POST['submit'] != ''){
			if($_POST['maribol-imdb-copy_long_desc'] != ''){
				update_option('maribol-imdb-copy_long_desc', ($_POST['maribol-imdb-copy_long_desc'] == '0') ? 0 : 1);
			}
			if($_POST['maribol-imdb-copy_poster'] != ''){
				update_option('maribol-imdb-copy_poster', ($_POST['maribol-imdb-copy_poster'] == '0') ? 0 : 1);
			}
			if($_POST['maribol-imdb-copy_trailer'] != ''){
				update_option('maribol-imdb-copy_trailer', ($_POST['maribol-imdb-copy_trailer'] == '0') ? 0 : 1);
				update_option('maribol-imdb-trailer_width', $_POST['maribol-imdb-trailer_width']);
				update_option('maribol-imdb-trailer_height', $_POST['maribol-imdb-trailer_height']);
			}
			if($_POST['maribol-imdb-copy_trailer'] != ''){
				update_option('maribol-imdb-hide_copyright', ($_POST['maribol-imdb-hide_copyright'] == '0') ? 0 : 1);
			}
			$saved = 1;
		}
		
		?>
		<div class="wrap">
			<h2>Maribol IMDB - Options</h2> 
			<?php if($saved==1):?><div style="padding:5px;background:lightYellow;border:1px solid #E6DB55;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">Settings saved</div><?php endif;?>
			<form method="post" action="admin.php?page=maribol-imdb-options"> 
			<input type='hidden' name='option_page' value='general' /><input type="hidden" name="action" value="update" /><input type="hidden" id="_wpnonce" name="_wpnonce" value="da140e65f5" /><input type="hidden" name="_wp_http_referer" value="/wp/wp-admin/options-general.php" /> 
				<table class="form-table"> 
					<tr valign="top"> 
						<th scope="row"><label for="maribol-imdb-copy_long_desc">Save full description</label></th> 
						<td> 
							<select name="maribol-imdb-copy_long_desc" id="maribol-imdb-copy_long_desc"> 
								<option <?php echo (get_option('maribol-imdb-copy_long_desc') == 0) ? 'selected' : '' ;?> value="0">Yes</option> 
								<option <?php echo (get_option('maribol-imdb-copy_long_desc') == 1) ? 'selected' : '' ;?> value="1">No</option> 
							</select>
							<small>default is <b>yes</b></small>
						</td> 
					</tr> 
					<tr valign="top"> 
						<th scope="row"><label for="maribol-imdb-copy_poster">Save poster in local cache</label></th> 
						<td> 
							<select name="maribol-imdb-copy_poster" id="maribol-imdb-copy_poster"> 
								<option <?php echo (get_option('maribol-imdb-copy_poster') == 1) ? 'selected' : '' ;?> value="1">Yes</option> 
								<option <?php echo (get_option('maribol-imdb-copy_poster') == 0) ? 'selected' : '' ;?> value="0">No</option> 
							</select>
							<small>default is <b>no</b></small>
						</td> 
					</tr> 
					<tr valign="top"> 
						<th scope="row"><label for="maribol-imdb-copy_poster">Save trailer in local cache</label></th> 
						<td> 
							<script>
								function showSizes(val){
									if(val == 1){
										jQuery('#trailer_size').css('display', 'inline');
									}else{
										jQuery('#trailer_size').hide();
									}
								}
							</script>
							<select name="maribol-imdb-copy_trailer" id="maribol-imdb-copy_trailer" onchange="showSizes(this.value);"> 
								<option <?php echo (get_option('maribol-imdb-copy_trailer') == 1) ? 'selected' : '' ;?> value="1">Yes</option> 
								<option <?php echo (get_option('maribol-imdb-copy_trailer') == 0) ? 'selected' : '' ;?> value="0">No</option> 
							</select>
							<small>default is <b>no</b></small>
							<br />
							
							<div id="trailer_size" style="display:<?php echo get_option('maribol-imdb-copy_trailer') == 1 ? 'inline' : 'none';?>;">
								<b>width: </b> <input type="text" name="maribol-imdb-trailer_width" value="<?php echo (int)get_option('maribol-imdb-trailer_width') == 0 ? '400' : get_option('maribol-imdb-trailer_width');?>" style="width:35px;">
								<b>height: </b> <input type="text" name="maribol-imdb-trailer_height" value="<?php echo (int)get_option('maribol-imdb-trailer_height') == 0 ? '320' : get_option('maribol-imdb-trailer_height');?>" style="width:35px;">
							</div>
						</td> 
					</tr> 
					<tr><td height="10"></td></tr>
					<tr valign="top"> 
						<th scope="row"><label for="maribol-imdb-hide_copyright">Hide Maribol IMDB Link</label></th> 
						<td> 
							<select name="maribol-imdb-hide_copyright" id="maribol-imdb-hide_copyright"> 
								<option <?php echo (get_option('maribol-imdb-hide_copyright') == 1) ? 'selected' : '' ;?> value="1">Yes</option> 
								<option <?php echo (get_option('maribol-imdb-hide_copyright') == 0) ? 'selected' : '' ;?> value="0">No</option> 
							</select>
							<small>default is <b>no</b></small><br />
							<small>If my link bothers you, feel free to hide it.<br /><b>But i would like if you will leave it there.</b></small>
						</td> 
					</tr> 
					<tr valign="top"> 
						<td> 
							<input type="submit" name="submit" id="submit" class="button-primary" value="Save Options"  />
						</td> 
					</tr> 
				</table>
				
			</form>
		</div>
		<?php
	}
	
	function maribol_imdb_donate(){
		echo'<div class="wrap"><h2>Please wait...</h2></div>';
		echo'<script>window.location="http://etiny.info/rracd";</script>';
	}
	
?>