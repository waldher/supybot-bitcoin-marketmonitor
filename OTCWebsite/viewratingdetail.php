<?php
	//error_reporting(-1); ini_set('display_errors', 1);
	$sortby = "rating";
	$sortorder = "ASC";
	$sign = isset($_GET["sign"]) ? $_GET["sign"] : "ANY";
	$validvalues = array("ANY","POS","NEG");
	if (!in_array($sign, $validvalues)) $sign = "ANY";
	$type = isset($_GET["type"]) ? $_GET["type"] : "RECV";
	$validvalues = array("RECV","SENT");
	if (!in_array($type, $validvalues)) $type = "RECV";
	if ($type == "RECV") {
		$validkeys = array('id', 'rater_nick', 'rater_total_rating', 'rated_nick', 'created_at', 'rating', 'notes');
	} else {
		$validkeys = array('id', 'rater_nick', 'rated_nick', 'ratee_total_rating', 'created_at', 'rating', 'notes');
	}
	$nick = isset($_GET["nick"]) ? $_GET["nick"] : "";
	$nick = html_entity_decode($nick);
	$outformat = isset($_GET["outformat"]) ? $_GET["outformat"] : "";
	$outformat = html_entity_decode($outformat);
?>
<?php
	try { $db = new PDO('sqlite:./otc/RatingSystem.db'); }
	catch (PDOException $e) { die($e->getMessage()); }

	include('querytojson.php');
	if ($outformat == 'json'){
		$signqueries = array('ANY' => ' ', 'POS' => ' AND ratings.rating > 0', 'NEG' => ' AND ratings.rating < 0');
		$typequeries = array('RECV' => 'users2.nick LIKE ? AND users2.id = ratings.rated_user_id AND users.id = ratings.rater_user_id', 'SENT' => 'users.nick LIKE ? AND users.id = ratings.rater_user_id AND users2.id = ratings.rated_user_id');
		$sql = "SELECT ratings.id as id, users.nick as rater_nick, users2.nick as rated_nick, ratings.created_at as created_at, ratings.rating as rating, ratings.notes as notes from users, users as users2, ratings WHERE " . $typequeries[$type] . $signqueries[$sign];
		$sth = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($nick));
		if (!$sth) echo "[]";
		else	jsonOutput($sth);
		exit();
	}
	
?>

<!DOCTYPE html>

<?php
 $pagetitle = "Rating Details for User '" . htmlentities($nick) . "'";
 include("header.php");
?>

<div class="breadcrumbs">
<a href="/">Home</a> &rsaquo;
<a href="trust.php">Web of Trust</a> &rsaquo;
<a href="viewratings.php">Web of Trust Data</a> &rsaquo;
Rating for <?php echo htmlentities($nick); ?>
</div>

<?php
	$types = array('RECV' => 'received', 'SENT' => 'sent');
	$signs = array('ANY' => 'all', 'POS' => 'positive', 'NEG' => 'negative');
?>
  <p>You are currently viewing <?php echo $signs[$sign]; ?> ratings <?php echo $types[$type]; ?> by user <?php echo htmlentities($nick); ?>.</p>
  <p>
   [<a href="viewratingdetail.php?nick=<?php echo htmlentities($nick); ?>&sign=<?php echo $sign; ?>&type=RECV">view received</a>] ||
   [<a href="viewratingdetail.php?nick=<?php echo htmlentities($nick); ?>&sign=<?php echo $sign; ?>&type=SENT">view sent</a>]
  </p>
  <p>
   [<a href="viewratingdetail.php?nick=<?php echo htmlentities($nick); ?>&type=<?php echo $type; ?>&sign=POS">view positive</a>] ||
   [<a href="viewratingdetail.php?nick=<?php echo htmlentities($nick); ?>&type=<?php echo $type; ?>&sign=NEG">view negative</a>] ||
   [<a href="viewratingdetail.php?nick=<?php echo htmlentities($nick); ?>&type=<?php echo $type; ?>&sign=ANY">view all</a>]
  </p>
  <h3>About <?php echo htmlentities($nick); ?></h3>
  <ul>
<?php
	$typequeries = array('RECV' => 'users.id = ratings.rated_user_id', 'SENT' => 'users.id = ratings.rater_user_id');
	$signqueries = array('ANY' => ' ', 'POS' => ' AND ratings.rating > 0', 'NEG' => ' AND ratings.rating < 0');

	$sql = "SELECT count(*) as ratingcount, sum(rating) as ratingsum FROM users, ratings WHERE users.nick LIKE ? AND " . $typequeries[$type] . $signqueries[$sign];
	$sth = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	if (!$sth->execute(array($nick))) echo "<li>No positive ratings found</li>\n";
	else {
		$entry = $sth->fetch(PDO::FETCH_BOTH);
		echo "<li>Count of " . $signs[$sign] . " ratings " . $types[$type] . ": " . number_format($entry['ratingcount']) . ". Total of points: " . number_format($entry['ratingsum']) . ".</li>\n";
	}
	echo '<li><a href="viewgpg.php?nick=' . htmlentities($nick) . '">GPG identity</a></li>';
	echo '<li><a href="ratingreciprocity.php?nick=' . htmlentities($nick) . '">Rating reciprocity</a></li>';
?>
  </ul>
  <h3>List of <?php echo $signs[$sign]; ?> ratings <?php echo $types[$type]; ?> <sup>[<a href="<?php jsonlink(); ?>">json</a>]</sup></h3>
  <table class="datadisplay sortable">
   <tr>
<?php
	foreach ($validkeys as $key) $colheaders[$key] = array('linktext' => str_replace("_", " ", $key));
	$colheaders["created_at"]["othertext"] = "(UTC)";
	foreach ($colheaders as $by => $colhdr) {
		echo "    <th>" . $colhdr["linktext"] . (!empty($colhdr["othertext"]) ? "<br>".$colhdr["othertext"] : "")."</th>\n";
	}
?>
   </tr>
<?php
	$typequeries = array('RECV' => 'users2.nick LIKE ? AND users2.id = ratings.rated_user_id AND users.id = ratings.rater_user_id', 'SENT' => 'users.nick LIKE ? AND users.id = ratings.rater_user_id AND users2.id = ratings.rated_user_id');
	$sql = "SELECT ratings.id as id, users.nick as rater_nick, users.total_rating as rater_total_rating, users2.nick as rated_nick, users2.total_rating as ratee_total_rating, ratings.created_at as created_at, ratings.rating as rating, ratings.notes as notes from users, users as users2, ratings WHERE " . $typequeries[$type] . $signqueries[$sign] . " ORDER BY " . $sortby . ' COLLATE NOCASE ' . $sortorder;
	$sth = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute(array($nick));
	if (!$sth) echo "<tr><td>No matching records found</td></tr>\n";
	else {
		//$resultrow = 0;
		//$results = $query->fetchAll(PDO::FETCH_BOTH);
		$color = 0;
		while ($entry = $sth->fetch(PDO::FETCH_BOTH)) {
			if ($color++ % 2) $class="even"; else $class="odd";
?>
   <tr class="<?php echo $class; ?>">
    <td><?php echo $entry['id']; ?></td>
    <td><a href="viewratingdetail.php?nick=<?php echo htmlentities($entry['rater_nick']); ?>&sign=ANY&type=RECV"><?php echo htmlentities($entry['rater_nick']); ?></a></td>
    <?php if ($type == "RECV") echo "<td>" . $entry['rater_total_rating'] . "</td>\n"; ?>
    <td><a href="viewratingdetail.php?nick=<?php echo htmlentities($entry['rated_nick']); ?>&sign=ANY&type=RECV"><?php echo htmlentities($entry['rated_nick']); ?></a></td>
	<?php if ($type == "SENT") echo "<td>" . $entry['ratee_total_rating'] . "</td>\n"; ?>
    <td class="nowrap"><?php echo gmdate('Y-m-d H:i:s', $entry['created_at']); ?></td>
    <td><?php echo $entry['rating']; ?></td>
    <td><?php echo htmlentities($entry['notes']); ?></td>
   </tr>
<?
		}
	}
?>
  </table>

<?php
 include("footer.php");
?>

 </body>
</html>
