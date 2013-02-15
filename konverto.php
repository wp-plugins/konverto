<?php
/**
 * Plugin Name: Konverto
 * Description: Konverto converts every URL in your posts from something like www.domain.com into clickable links like &lt;a href="http://www.domain.com" title="www.domain.com"&lt;www.domain.com&lt;/a&gt;
 * Author: Jörg Burbach
 * Plugin URI: http://joerg-burbach.de/
 * Author URI: http://joerg-burbach.de/
 * Version: 1.1.0
 * Text Domain: konverto-plugin
 * License: GPL2v2
**/

define( 'konverto_VERSION', '1.1.0' );
define( 'konverto_PATH', dirname( __FILE__ ) );
define( 'konverto_FOLDER', basename( konverto_PATH ) );
define( 'konverto_URL', plugins_url() . '/' . konverto_FOLDER );

class konverto_Base {
	function __construct() {
		add_action( 'admin_menu', array( $this, 'konverto_menu' ) );
		add_action( 'the_content', array( $this, 'konverto_the_content' ) );
		add_filter( 'plugin_action_links', array( $this, 'konverto_settings_link' ), 10, 2 );
	}	

	/* Link zu den Einstellungen */
	function konverto_settings_link($links,$file) {
		if ($file == plugin_basename(__FILE__))
            $links[] = '<a href="' . admin_url("options-general.php?page=konverto-id") . '">'. __('Settings') .'</a>';
        return $links;
	}

	/* Admin-Seite */

	function konverto_menu() {
		add_options_page( 'Konverto', 'Konverto', 'manage_options', 'konverto-id', array( $this, 'konverto_options' ) );
	}

	function konverto_options() {
	
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'Sorry. Du darfst hier nichts machen...<br>Sorry. Access prohibited.' ) );
		}

		// Holen, falls noch nicht gesetzt, dann speichern, falls es sie noch nicht gibt.
		$konverto=get_option("konverto");
		
		if ($konverto===false) { 
			add_option("konverto", array("aktiv"=>"","titel"=>"","ziel"=>"_blank","email"=>""));
		}

		// Änderungen durchführen, falls Formular abgeschickt wurde
		if (($_POST)) {
			update_option("konverto", array("aktiv"=>$_POST["konverto_aktiv"],"titel"=>$_POST["konverto_titel"],"ziel"=>$_POST["konverto_target"],"email"=>$_POST["konverto_email"]));
		}
		$konverto=get_option("konverto");

		echo "<h2>Konverto Admin</h2>";
		echo "<div class=\"wrap\">";
		echo "<p><form method=\"post\" action=\"\">";
		echo "<table cellpadding=\"8\">";
		echo "<tr><td>Plugin aktiv<br>Activate Plugin</td>\n";
		echo "<td><input type=\"checkbox\" name=\"konverto_aktiv\" id=\"konverto_aktiv\"";
		if ($konverto["aktiv"]=="on") { echo " checked"; }
		echo "></td></tr>\n";

		echo "<tr><td>URL als Titel in den Link übernehmen:<br>Check here to use URL as title:</td>\n";
		echo "<td><input type=\"checkbox\" name=\"konverto_titel\" id=\"konverto_titel\"";
		if ($konverto["titel"]=="on") { echo " checked"; }
		echo "></td></tr>\n";

		echo "<tr><td>Konvertiere auch E-Mail-Adressen:<br>Convert mail-addresses into mailto:</td>\n";
		echo "<td><input type=\"checkbox\" name=\"konverto_email\" id=\"konverto_email\"";
		if ($konverto["email"]=="on") { echo " checked"; }
		echo "></td></tr>\n";

		echo "<tr><td>URL öffnen<br>Open URL</td>\n";
		echo "<td><select type=\"checkbox\" name=\"konverto_target\" id=\"konverto_target\">";
		echo "<option value=\"_blank\""; if ($konverto["ziel"]=="_blank") { echo " selected"; }; echo ">auf einer neuen Seite / on new page</option>";
		echo "<option value=\"\""; if ($konverto["ziel"]=="") { echo " selected"; }; echo ">auf derselben Seite / on same page</option>";
		echo "</select>\n";
		echo "</td></tr>\n";

		echo "<tr><td><input type=\"hidden\" name=\"action\" value=\"update\" /><input type=\"submit\" value=\"Einstellungen Sichern / Save\" /></td><td>Version " . konverto_VERSION . "</td></tr>";
		echo "</table>";
		echo "</form></p>";
		echo "</div>";

		echo "<form action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\"><div class=\"paypal-donations\"><input type=\"hidden\" name=\"cmd\" value=\"_donations\"><input type=\"hidden\" name=\"business\" value=\"joerg.burbach@quadworks.de\"><input type=\"hidden\" name=\"item_name\" value=\"Konverto-Plugin (Jörg Burbach)\"><input type=\"hidden\" name=\"item_number\" value=\"Konverto-Plugin (Jörg Burbach)\"><input type=\"hidden\" name=\"amount\" value=\"2.50\"><input type=\"hidden\" name=\"currency_code\" value=\"EUR\"><input type=\"image\" src=\"https://www.paypal.com/de_DE/DE/i/btn/btn_donate_SM.gif\" name=\"submit\" alt=\"PayPal - The safer, easier way to pay online.\"><img alt=\"\" src=\"https://www.paypal.com/en_US/i/scr/pixel.gif\" width=\"1\" height=\"1\" target=\"_blank\"></div></form>";

	}

	// Change URLs from www.domain.com to clickable links
	// Changes mail-addresses, too
	// removes all trailing tags, and refurnish
	// removes all links, instead recreates them
	function konverto_the_content($content) {
		$konverto=get_option("konverto");
		if ($konverto["aktiv"]<>"on") { return $content; }	
		if ($konverto["titel"]=="on") { $link = "title=\"\$1\""; } else { $link = ""; }
		if ($konverto["ziel"]<>"") {  $ziel = "target=\"_blank\""; }
		$text = preg_replace("/\<a([^>]*)\>([^<]*)\<\/a\>/i", "$2", $content);	// Strip-Tags first
		$text = preg_replace('/(?<!http:\/\/|https:\/\/|\"|=|\'|\'>|\">)(www\..*?)(\s|\Z|\.\Z|\.\s|\<|\>|,)/i',"<a href=\"http://$1\"" . $ziel . " " . $link . ">$1</a>$2",$text);
		$text = preg_replace('/(?<!\"|=|\'|\'>|\">|site:)(https?:\/\/(www){0,1}.*?)(\s|\Z|\.\Z|\.\s|\<|\>|,)/i',"<a href=\"$1\"" . $ziel . " " . $link . ">$1</a>$3",$text);
		if ($konverto["email"]=="on") { $text = preg_replace('/<[^>]+\>(\S+@\S+\.\S+)<[^>]+\>/i',"<a href=\"mailto:$1\" " . $link . ">$1</a>",$text); }
		return $text;		
	}
	
}

$konverto_base = new konverto_Base(); // Initialize everything
