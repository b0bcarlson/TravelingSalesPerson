<?php
require_once "config.php";
if(isset($_POST["name"])){
	if($query = $conn->prepare("INSERT INTO `places` (`place`, `name`) VALUES (?, ?)")){
		$query->bind_param("ss", $_POST["addr"], $_POST["name"]);
		$query->execute();
		if($query->error)
			die("Error inserting row " . $query->error);
		$query->close();
	}
	else
		die("Error in query inserting row " . $conn->error);
}
$places = array();
if($query = $conn->prepare("SELECT `place`, `name`, `id` FROM `places` WHERE `id` != ?")){
	$query->bind_param("i", $home);
	$query->execute();
	if($query->error)
		die("Error getting places " . $query->error);
	$query->bind_result($place, $name, $id);
	while($query->fetch())
		$places[] = [$place, $name, $id];
	$query->close();
}
else
	die("Error in query getting places " . $conn->error);
?>
<style>
table, th, td {
  border: 1px solid black;
}
</style>
<form action="gen.php" method="POST">
	<table>
		<?php
			foreach($places as $k => $v)
				echo
				"<tr>
					<td>$v[0]</td>
					<td>$v[1]</td>
					<td><input type=\"checkbox\" name=$v[2]></td>
				</tr>";
		?>
	</table>
	<input type="submit" value="Generate"/>
</form>
<form action="index.php" method="POST">
	<input name="addr">
	<input name="name">
	<input type="submit">
</form>
