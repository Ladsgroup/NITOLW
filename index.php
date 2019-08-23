<?php
require_once( 'header.php' );
error_reporting(E_ERROR|E_CORE_ERROR|E_COMPILE_ERROR); // E_ALL|
ini_set('display_errors', 'On');

$fromLang = isset( $_REQUEST['fromLang'] ) ? $_REQUEST['fromLang'] : '';
$toLang = isset( $_REQUEST['toLang'] ) ? $_REQUEST['toLang'] : '';
$category = isset( $_REQUEST['category'] ) ? $_REQUEST['category'] : '';
if (isset($_REQUEST['limit']) && $_REQUEST['limit']) {
	$limit = (int)$_REQUEST['limit'];
} else {
	$limit = 50;
}
$hasFormData = $fromLang && $toLang && $category;
?>
<script>
$(function() {
	$('table.sortable').tablesort();
});
</script>
<div style="padding: 3em;">
<form action="<?php echo basename( __FILE__ ); ?>">
  <label for="fromLang">Language code</label><br>
  <div class="ui corner labeled input">
  <input style="margin-bottom: 0.5em" type="text" name="fromLang" id="fromLang" required placeholder="en" <?php
  if ( $fromLang !== '' ) {
	echo 'value="' . htmlspecialchars( $fromLang ) . '"';
  }
?>>
  <div class="ui corner label">
    <i class="asterisk icon"></i>
  </div></div><br>
  <label for="toLang">Excluding language code</label><br>
  <div class="ui corner labeled input">
  <input style="margin-bottom: 0.5em" type="text" name="toLang" id="toLang" required placeholder="fa" <?php
  if ( $toLang !== '' ) {
	echo 'value="' . htmlspecialchars( $toLang ) . '"';
  }
?>>
  <div class="ui corner label">
    <i class="asterisk icon"></i>
  </div></div><br>
  <label for="category">Category</label><br>
  <div class="ui corner labeled input">
  <input style="margin-bottom: 0.5em" type="text" name="category" id="category" required placeholder="French nouns" <?php
  if ( $category !== '' ) {
	echo 'value="' . htmlspecialchars( $category ) . '"';
  }
?>>
  <div class="ui corner label">
    <i class="asterisk icon"></i>
  </div></div><br>
<label for="limit">Limit</label><br>
<div class="ui labeled input">
  <input style="margin-bottom: 0.5em" id="limit" name="limit" type="number" min="1" max="500" required value="<?php echo htmlspecialchars( $limit ); ?>">
</div>
<br>
  <button type="submit" class="ui primary button">Submit</button>
</form>
<?php

if ( $hasFormData ) {
	$limit = addslashes( (string)min( [ $limit, 500 ] ) );
	$dbmycnf = parse_ini_file("../../../replica.my.cnf");
	$dbuser = $dbmycnf['user'];
	$dbpass = $dbmycnf['password'];
	$fromLang = htmlspecialchars( $fromLang );
	unset($dbmycnf);
	$dbhost = "{$fromLang}wiktionary.web.db.svc.eqiad.wmflabs";
	$dbname = "{$fromLang}wiktionary_p";
	$db = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8', $dbuser, $dbpass);
	$category = str_replace(' ', '_', htmlspecialchars( $category ));
	$categoryLimit = $limit * 10;
	$sql = "SELECT cl_from FROM categorylinks WHERE cl_to = '{$category}' LIMIT $categoryLimit";
	$result = $db->query($sql)->fetchAll();
	$pages = [];
	foreach ( $result as $row ) {
		$pages[] = (integer)$row['cl_from'];
	}

	$toLang = htmlspecialchars( $toLang );
	$sql = "SELECT ll_from FROM langlinks WHERE ll_lang = '{$toLang}' and ll_from IN (" . implode( ',', $pages ) . ');';
	$result = $db->query($sql)->fetchAll();
	$pagesInOther = [];
	foreach ( $result as $row ) {
		$pagesInOther[] = (integer)$row['ll_from'];
	}
	$pagesNotInOther = array_slice( array_diff( $pages, $pagesInOther ), 0, $limit);
	$sql = "SELECT page_title, page_namespace " .
		"FROM page " .
		"WHERE page_id IN (" . implode( ',', $pagesNotInOther ) .") AND page_namespace=0;";
	$result = $db->query($sql)->fetchAll();
	echo '<table class="ui sortable celled table"><thead><tr><th>Page</th></tr></thead><tbody>';
	echo "\n";
	foreach ($result as $row) {
		$title = $row['page_title'];
		echo "<tr><td><a href=https://{$fromLang}.wiktionary.org/wiki/{$title} target='_blank'>{$title}</a></td></tr>\n";
	}
	echo "</table>\n";
} else {
    echo '<div class="ui negative message">
    <div class="header">
     No query
    </div>
    <p>You need to enable at least one case</p></div>';
}

?>
</div>
</body>
</html>

