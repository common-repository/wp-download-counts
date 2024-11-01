<?php
/*
Plugin Name: WP Download Counts
Plugin URI: http://www.danonwordpress.com/wp-download-counts-plugin-for-wordpress/
Description: Adds a widget which displays a list of your published WordPress plugins, with links and download counts.
Author: Dan Mossop
Version: 1.0
Author URI: http://www.danmossop.com
*/

/* === DETAILS WIDGET === */

class dowpdc_pluginCountWidget extends WP_Widget {
	
	private $wid = 'dowpdc_pluginCountWidget'; // should be same as class name
	private $wname = "WP Download Counts";
	private $wdescr = 'Adds a widget which displays a list of your published WordPress plugins, with links and download counts.';
	
  function dowpdc_pluginCountWidget() {
    $this->WP_Widget($this->wid, $this->wname, array('classname'=>$this->wid, 'description'=>$this->wdescr));
  }
 
  function form($instance) {
    $instance = wp_parse_args((array) $instance, array('title'=>'', 'author'=>''));
	$id = $this->get_field_id('title');
	$name = $this->get_field_name('title');
	$val = attribute_escape($instance['title']);
	echo <<<END
	<p><label for="$id">Title: <input class="widefat" id="$id" name="$name" type="text" value="$val" /></label></p> 
END;
	$id = $this->get_field_id('author');
	$name = $this->get_field_name('author');
	$val = attribute_escape($instance['author']);
	echo <<<END
	<p><label for="$id">WordPress.org Username: <input class="widefat" id="$id" name="$name" type="text" value="$val" /></label></p> 
END;
  }
 
  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['author'] = $new_instance['author'];
    return $instance;
  }
 
  function widget($args, $instance) {
	global $post;
    extract($args, EXTR_SKIP);
    echo $before_widget;
    $title = empty($instance['title'])?' ':apply_filters('widget_title', $instance['title']);
    $author = empty($instance['author'])?'':$instance['author'];
	if (!empty($title)) { echo $before_title.$title.$after_title; }
    // WIDGET CODE GOES HERE
	
	echo '<ul class="dowpdc">';
	
	$counts = $names = array();
	$plugins = !empty($author)?dowpdc_getWordpressPluginAuthorPlugins($author):array();
	foreach($plugins as $slug) { 
		$plugin = dowpdc_getWordpressPluginDetails($slug);
		$counts[$slug] = $plugin->downloaded;
		$names[$slug] = $plugin->name;
	}
	arsort($counts);

	if (!empty($counts)) { 
		foreach($counts as $slug=>$count) { 
			echo '<li><a href="http://wordpress.org/plugins/'.$slug.'">'.$names[$slug].'</a><br><span> - '.number_format($count).' downloads</span></li>';
		}
	} else { 
		echo '<li><span>No plugins found</span></li>';
	}
	echo '</ul>';
	
	// END WIDGET CODE
    echo $after_widget;
  }
}
add_action( 'widgets_init', create_function('', 'return register_widget("dowpdc_pluginCountWidget");') );

function dowpdc_css() { ?>
<style>
.dowpdc span { font-size:smaller;opacity:0.6; filter:alpha(opacity=60); }
</style>
<?php 
} 
add_action('wp_head', 'dowpdc_css');

function dowpdc_getWordpressPluginDetails($plugin) { 
	return dowpdc_wordpressPluginApiQuery('plugin_information', array('slug'=>$plugin, 'fields'=>array('sections'=>false)));
}

function dowpdc_getWordpressPluginAuthorPlugins($author) { 
	$author = dowpdc_wordpressPluginApiQuery('query_plugins', array('author'=>$author));
	$plugins = array();
	foreach($author->plugins as $plugin) { 
		$plugins[] = $plugin->slug;
	}
	return ($plugins);
}

function dowpdc_wordpressPluginApiQuery($action, $properties) { 
	$request = array('action'=>$action, 'request'=>serialize((object) $properties));
	$cache = get_temp_dir().'/dowpdc-'.md5(serialize($request)).'.txt';
	$mins = 15; // update every 15 minutes
	if (file_exists($cache) && (filemtime($cache) > (time()-60*$mins))) { 
		$response = unserialize(file_get_contents($cache));
	} else {
	   $response = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array('body'=>$request));
	   file_put_contents($cache, serialize($response), LOCK_EX);
	}    
	return empty($response)?array():unserialize($response['body']);
}

?>