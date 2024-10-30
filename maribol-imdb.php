<?php
/*
Plugin Name: Maribol IMDB
Plugin URI: http://www.mlabs.info/maribol-imdb
Description: Maribol IMDB is a wordpress plugin that helps you show info about a movie in your blog posts.
Author: Samuel Marian
Version: 0.4
Author URI: http://www.mlabs.info/
*/


	$dir = (defined(__DIR__)) ?  __DIR__ : dirname(__FILE__);
	define('CACHE_PATH', $dir.'/_cache/');
	define('POSTER_CACHE_PATH', $dir.'/_posters/');
	define('TRAILER_CACHE_PATH', $dir.'/_trailers/');
	define('MOVIE_CACHE_LIST', CACHE_PATH.'_movie_cache.db');
	
	define('TRAILER_LOCATION', 'http://www.mlabs.info/api/maribol-imdb/trailer.php?id');
	define('TRAILER_LOCATION_NOIFRAME', 'http://imdb.mlabs.info/trailer?id');
	
	$maribol_imdb_trailer_width = get_option('maribol-imdb-trailer_width');
	$maribol_imdb_trailer_height = get_option('maribol-imdb-trailer_height');
	
	define('TRAILER_WIDTH', (int)$maribol_imdb_trailer_width > 0 ? $maribol_imdb_trailer_width : '400');
	define('TRAILER_HEIGHT', (int)$maribol_imdb_trailer_height > 0 ? $maribol_imdb_trailer_height : '320');
	
	if(!file_exists(CACHE_PATH)){
		mkdir(CACHE_PATH, 0777);
	}
	
	function MaribolIMDB_doOpenMoviesCache(){
		return (array)@json_decode(@file_get_contents(MOVIE_CACHE_LIST), true);
	}
	
	if(is_admin()){
		include($dir.'/admin.php');
	}
	
	function MaribolIMDB_getMovieDetailsTitle($content, $imdb_id, $info){
		$cache_data = MaribolIMDB_check_cache($imdb_id);
		$GLOBALS['maribolimdb'] = $cache_data;
		$GLOBALS['maribolimdb_id'] = $imdb_id;
		
		foreach($info as $info_item):
			$content = str_replace('[imdb:'.$info_item.']', $cache_data[$info_item], $content);
		endforeach;
		
		return $content;
	}
	
	function MaribolIMDB_getMovieDetails($content, $imdb_id, $info){
		$cache_data = $GLOBALS['maribolimdb'];
		if(!is_array($cache_data)){
			$cache_data = MaribolIMDB_check_cache($imdb_id);
			$GLOBALS['maribolimdb'] = $cache_data;
			$GLOBALS['maribolimdb_id'] = $imdb_id;
		}
		
		$info = (array)$info;
		$info[] = 'poster_embed';
		$info[] = 'poster_small_embed';
		foreach($info as $info_item):

			if($info_item == 'poster' && strpos($cache_data['poster'], 'http://') === false){
				$content = str_replace('[imdb:poster]', plugins_url().'/maribol-imdb/_posters/'.$cache_data['poster'], $content);
			
			}elseif($info_item == 'poster_small' && strpos($cache_data['poster_small'], 'http://') === false){
				$content = str_replace('[imdb:poster_small]', plugins_url().'/maribol-imdb/_posters/'.$cache_data['poster_small'], $content);
			
			}elseif($info_item == 'poster_embed'){
				if(strpos($cache_data['poster_embed'], 'http://') === false){
					$content = str_replace('[imdb:poster_embed]', '<img src="'.$cache_data['poster'].'">', $content);
				}else{
					$content = str_replace('[imdb:poster_embed]', '<img src="'.plugins_url().'/maribol-imdb/_posters/'.$cache_data['poster'].'">', $content);
				}
				
			}elseif($info_item == 'poster_small_embed'){
				if(strpos($cache_data['poster_small_embed'], 'http://') === false){
					$content = str_replace('[imdb:poster_small_embed]', '<img src="'.$cache_data['poster_small'].'">', $content);
				}else{
					$content = str_replace('[imdb:poster_small_embed]', '<img src="'.plugins_url().'/maribol-imdb/_posters/'.$cache_data['poster_small'].'">', $content);
				}
				
			}elseif($info_item == 'trailer' && $cache_data['trailer']['file']){
				$content = str_replace('[imdb:trailer]', '
					<video width="'.(TRAILER_WIDTH > 0 ? TRAILER_WIDTH : $cache_data['trailer']['width']).'" height="'.(TRAILER_HEIGHT > 0 ? TRAILER_HEIGHT : $cache_data['trailer']['height']).'" controls="controls">
						<source src="'.plugins_url().'/maribol-imdb/_trailers/'.$cache_data['trailer']['file'].'" type="video/mp4" />
						Your browser does not support the video tag.
					</video>
				', $content);
				
			}elseif($info_item == 'trailer_name'){
				$content = str_replace('[imdb:trailer_name]', $cache_data['trailer']['name'], $content);
			
			}else{
				$content = str_replace('[imdb:'.$info_item.']', $cache_data[$info_item], $content);
			}
		endforeach;
		
		return $content;
	}
	
	function MaribolIMDB_create_filename($string){
		$url = preg_replace('([^a-z\-\_0-9])', '-', strtolower($string));
		$url = preg_replace('([-]+)', '-', $url);
		$url = trim($url, ' _-');
		$url = $url.time();
		return $url;
	}
	
	function MaribolIMDB_create_cache($imdb_id){
		
		$cache_location = CACHE_PATH.'tt'.$imdb_id.'.db';
		$servers = array(
			'http://www.mlabs.info/api/maribol-imdb/api.php?id=',
			'http://www.maribol.ro/api/maribol-imdb/api.php?id='
		);
		
		$false = true;
		foreach($servers as $id=>$server){
			$json = @file_get_contents($server.$imdb_id);
			if(strlen($json) > 20 ){
				$exist = true;
				break;
			}
		}
		
		if($exist == true){
			$fh = @fopen($cache_location, 'w');
			
			$maribol_imdb_copy_long_desc = get_option('maribol-imdb-copy_long_desc');
			$maribol_imdb_copy_poster = get_option('maribol-imdb-copy_poster');
			$maribol_imdb_copy_trailer = get_option('maribol-imdb-copy_trailer');
			
			$open_cache = @json_decode($json, true);
						
			if($maribol_imdb_copy_long_desc == 1){
				unset($open_cache['description']);
			}	
			
			if($maribol_imdb_copy_poster == 1){
				if(!is_dir(POSTER_CACHE_PATH)){
					mkdir(POSTER_CACHE_PATH, 0777);
				}
				if(is_dir(POSTER_CACHE_PATH)){
					if(!is_writable(POSTER_CACHE_PATH)){
						chmod(POSTER_CACHE_PATH, 0777);
					}
					if(is_writable(POSTER_CACHE_PATH)){
						if($open_cache['poster'] != ''){
							$local_file_big = MaribolIMDB_create_filename($open_cache['title'].'-big-').'.'.end(explode('.', $open_cache['poster']));
							$poster_big_copied = copy($open_cache['poster'], POSTER_CACHE_PATH.$local_file_big);
							if($poster_big_copied){
								$open_cache['poster'] = $local_file_big;
							}
							
							$local_file_small = MaribolIMDB_create_filename($open_cache['title'].'-small-').'.'.end(explode('.', $open_cache['poster_small']));
							$poster_small_copied = copy($open_cache['poster_small'], POSTER_CACHE_PATH.$local_file_small);
							if($poster_small_copied){
								$open_cache['poster_small'] = $local_file_small;
							}
						}					
					}
				}
			}
			
			if($maribol_imdb_copy_trailer == 1){
				if(!is_dir(TRAILER_CACHE_PATH)){
					mkdir(TRAILER_CACHE_PATH, 0777);
				}
				if(is_dir(TRAILER_CACHE_PATH)){
					if(!is_writable(TRAILER_CACHE_PATH)){
						chmod(TRAILER_CACHE_PATH, 0777);
					}
					if(is_writable(TRAILER_CACHE_PATH)){
						if($open_cache['trailer']['file'] != ''){
							$local_trailer = MaribolIMDB_create_filename($open_cache['title'].'-big-').'.'.(substr(end(explode('.', $open_cache['trailer']['file'])), 0, 3) == '' ? 'flv' : substr(end(explode('.', $open_cache['trailer']['file'])), 0, 3));
							$trailer_copied = copy(@urldecode($open_cache['trailer']['file']), TRAILER_CACHE_PATH.$local_trailer);
							if($trailer_copied){
								$open_cache['trailer']['file'] = $local_trailer;
							}
						}					
					}
				}
			}
			
			@fwrite($fh, json_encode($open_cache));
			@fclose($fh);
		
			$list_cache = array(
				'title' => $open_cache['title'],
				'year' => $open_cache['year'],
				'imdb_id' => $imdb_id,
				'last_cache' => time()
			);
			
			$movies = MaribolIMDB_doOpenMoviesCache();
			$exist = 0;
			foreach($movies as $key=>$movie){
				if($movie['imdb_id'] == $imdb_id){
					$exist = 1;
					$movies[$key] = $list_cache;
					break;
				}
			}
			
			if($exist == 0){
				$movies[] = $list_cache;
			}
			
			$fh = @fopen(MOVIE_CACHE_LIST, 'w');
			@fwrite($fh, @json_encode($movies));
			@fclose($fh);
			
		}
		
		return $json;
	}
	
	function MaribolIMDB_check_cache($imdb_id){
		$cache_location = CACHE_PATH.'tt'.$imdb_id.'.db';
		if(@file_exists($cache_location) && @is_file($cache_location)){
			return @json_decode(file_get_contents($cache_location), true);
		}else{
			return @json_decode(MaribolIMDB_create_cache($imdb_id), true);
		}
	}
	
	function MaribolIMDB_refresh_cache($imdb_id){
		$cache_location = CACHE_PATH.'tt'.$imdb_id.'.db';
		return @json_decode(MaribolIMDB_create_cache($imdb_id), true);
	}
	
	function MaribolIMDB_doCacheNonCachedMovies(){
		if(!file_exists(MOVIE_CACHE_LIST)){
			foreach(glob(CACHE_PATH.'*.db') as $cached_movie_url){
				$movie = @json_decode(@file_get_contents($cached_movie_url), true);
				$movies[] = array(
					'title' => $movie['title'],
					'year' => $movie['year'],
					'imdb_id' => basename($cached_movie_url),
					'last_cache' => time()
				);
			}
			$fh = @fopen(MOVIE_CACHE_LIST, 'w');
			@fwrite($fh, @json_encode($movies));
			@fclose($fh);
		}
	}

	function MaribolIMDB_generatecontent($content) {
		@preg_match('/\[imdb\]tt([0-9]+)\[\/imdb\]/isU', $content, $imdb_id_exist);
		if(trim($imdb_id_exist[1]) != '' || $GLOBALS['maribolimdb_id']){
			$content = str_replace($imdb_id_exist[0], '', $content);
			@preg_match_all('/\[imdb\:(title|year|description|short_description|poster|poster_small|categories|duration|director|writers|cast|language|release_date|filming_locations|rating|trailer|trailer_name|poster_embed|poster_embed_small)\]/isU', $content, $info_matches);
			if(count($info_matches)>0){
				$content = MaribolIMDB_getMovieDetails($content, ($GLOBALS['maribolimdb_id'] ? $GLOBALS['maribolimdb_id'] : $imdb_id_exist[1]), $info_matches[1]);
			}
		}
		return $content;
	}
	function MaribolIMDB_generatetitle($content) {
		@preg_match('/\[imdb\]tt([0-9]+)\[\/imdb\]/isU', $content, $imdb_id_exist);
		if(trim($imdb_id_exist[1]) != ''){
			$info_matches = array(
				'title','year','release_date'
			);
			$content = str_replace($imdb_id_exist[0], '', $content);
			$content = MaribolIMDB_getMovieDetailsTitle($content, $imdb_id_exist[1], $info_matches);
		}
		return $content;
	}

	add_filter('the_content', 'MaribolIMDB_generatecontent');
	add_filter('get_the_excerpt', 'MaribolIMDB_generatecontent');
	add_filter('the_title', 'MaribolIMDB_generatetitle');
	add_filter('wp_title', 'MaribolIMDB_generatetitle');
	add_filter('get_post_custom_values', 'MaribolIMDB_generatecontent');
	
	// Adding credit for Maribol Labs
	function MaribolIMDB_powered() {
		$maribol_imdb_hide_copyright = get_option('maribol-imdb-hide_copyright');
		
		echo '<span id="maribol_imdb" style="font-size:11px;color:#333;'.($maribol_imdb_hide_copyright == 1 ? 'display:none;' : '').'">
			Powered by 
			<a style="font-size:11px;color:#333;" title="fotograf nunta - fotograf profesionist - fotograf baia mare" href="http://www.maribol.ro" target="_blank">Maribol</a>
			<a style="font-size:11px;color:#333;" title="Maribol IMDB - imdb wordpress plugin" href="http://www.mlabs.info/maribol-imdb" target="_blank">IMDB Plugin</a>
		</span>';
	}
	add_action('wp_footer', 'MaribolIMDB_powered');

?>
