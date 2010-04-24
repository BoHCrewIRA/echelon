<?php
$page = "active";
$page_title = "Inactive Admins";
$auth_name = 'clients';
$b3_conn = true; // this page needs to connect to the B3 database
$pagination = true; // this page requires the pagination part of the footer
require 'inc.php';

##########################
######## Varibles ########

## Default Vars ##
$orderby = "time_edit";
$order = "asc";

//$limit_rows = 75; // limit_rows can be set by the DB settings // uncomment this line to manually overide the number of table rows per page

$time = time();
$length = 7; // default length in days that the admin must be in active to show on this list


## Sorts requests vars ##
if($_GET['ob'])
	$orderby = addslashes($_GET['ob']);

if($_GET['o']) 
	$order = addslashes($_GET['o']);
	
if($_GET['d'])
	$length = addslashes($_GET['d']);

// allowed things to sort by
$allowed_orderby = array('id', 'name', 'connections', 'group_bits', 'time_edit');
if(!in_array($orderby, $allowed_orderby)) // Check if the sent varible is in the allowed array 
	$orderby = 'time_edit'; // if not just set to default id

// allowed times to limit by
$allowed_length = array('1', '3', '7', '21', '28', '35', '182', '365');
if(!in_array($length, $allowed_length)) // Check if the sent varible is in the allowed array 
	$length = 7; // reset to default of 7 days
	
## Page Vars ##
if ($_GET['p'])
  $page_no = addslashes($_GET['p']);

$start_row = $page_no * $limit_rows;


###########################
######### QUERIES #########

$query = sprintf("SELECT c.id, c.name, c.connections, c.time_edit, g.name as level
	FROM clients c LEFT JOIN groups g ON c.group_bits = g.id
	WHERE c.group_bits >= 8
	AND  c.group_bits <=64 AND(%d - c.time_edit > %d*60*60*24 )", $time, $length);

$query .= sprintf("ORDER BY %s ", $orderby);

## Append this section to all queries since it is the same for all ##
if($order == "desc")
	$query .= " DESC"; // set to desc 
else
	$query .= " ASC"; // default to ASC if nothing adds up

$query_limit = sprintf("%s LIMIT %s, %s", $query, $start_row, $limit_rows); // add limit section

## Prepare and run Query ##
$stmt = $db->mysql->prepare($query_limit) or die('Database Error: '.$db->mysql->error);
$stmt->execute(); // run query
$stmt->store_result(); // store results (needed to count num_rows)
$num_rows = $stmt->num_rows; // finds the number fo rows retrieved from the database
$stmt->bind_result($id, $name, $connections, $time_edit, $level); // store results

if($num_rows > 0) :
	while($stmt->fetch()) : // get results and put results in an array
		$clients_data[] = array(
			'id' => $id,
			'name' => $name,
			'connect' => $connections,
			'time_edit' => $time_edit,
			'level' => $level
		);
	endwhile;
endif;

$stmt->free_result(); // free the data in memory from store_result
$stmt->close(); // closes the prepared statement

## Require Header ##	
require 'inc/header.php';
?>

<table summary="A list of <?php echo limit_rows; ?> admins who could be deemed as inactive">
	<caption>Inactive Admins<small>There are <strong><?php echo $total_rows; ?></strong> admins who have not been seen by B3 for</small>
		<form action="active.php" method="get" class="sm-f-select">
			<select name="d" onchange="this.form.submit()">
				<option value="1"<?php if($length == '1') echo ' selected="selected"'; ?>>1 Day</option>
				<option value="3"<?php if($length == '3') echo ' selected="selected"'; ?>>3 Days</option>
				<option value="7"<?php if($length == '7') echo ' selected="selected"'; ?>>1 Week</option>
				<option value="14"<?php if($length == '14') echo ' selected="selected"'; ?>>2 Weeks</option>
				<option value="21"<?php if($length == '21') echo ' selected="selected"'; ?>>3 Weeks</option>
				<option value="28"<?php if($length == '28') echo ' selected="selected"'; ?>>4 Weeks</option>
				<option value="35"<?php if($length == '35') echo ' selected="selected"'; ?>>5 Weeks</option>
				<option value="182"<?php if($length == '182') echo ' selected="selected"'; ?>>6 Months</option>
				<option value="365"<?php if($length == '365') echo ' selected="selected"'; ?>>1 Year</option>
			</select>
		</form>
	</caption>
	<thead>
		<tr>
			<th>Name
				<?php linkSort('name', 'Name'); ?>
			</th>
			<th>Client-id
				<?php linkSort('id', 'Client-id'); ?>
			</th>
			<th>Level
				<?php linkSort('group_bits', 'Level'); ?>
			</th>
			<th>Connections
				<?php linkSort('connections', 'Connections'); ?>
			</th>
			<th>Last Seen
				<?php linkSort('time_edit', 'Last Seen'); ?>
			</th>
			<th>
				Duration
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th colspan="6">Click client name to see details</th>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$rowcolor = 0;

	if($num_rows > 0) { // query contains stuff
	 
		foreach($clients_data as $clients): // get data from query and loop
			$cid = $clients['id'];
			$name = $clients['name'];
			$level = $clients['level'];
			$connections = $clients['connect'];
			$time_edit = $clients['time_edit'];
			
			## Change to human readable		
			$time_diff = time_duration($time - $time_edit, 'yMwd');		
			$time_edit = date($tformat, $time_edit); // this must be after the time_diff
			
			## Row color
			$rowcolor = 1 - $rowcolor;	
			if($rowcolor == 0)
				$odd_even = "odd";
			else 
				$odd_even = "even";
	
			// setup heredoc (table data)			
			$data = <<<EOD
			<tr class="$odd_even">
				<td><strong><a href="clientdetails.php?id=$cid">$name</a></strong></td>
				<td>@$cid</td>
				<td>$level</td>
				<td>$connections</td>
				<td><em>$time_edit</em></td>
				<td><em>$time_diff</em></td>
			</tr>
EOD;

			echo $data;
		endforeach;
		
		$no_data = false;
	} else {
		$no_data = true;
		echo '<tr class="odd"><td colspan="6">There are no admins that have been in active for more than '. $length . ' days.</td></tr>';
	} // end if query contains information
	?>
	</tbody>
</table>

<?php require 'inc/footer.php'; ?>