<!DOCTYPE html>
<?php session_start();

include('header.php');

?>

<h1>To-Do</h1>

<br/>User Input:
<ul>	
	<li>Completed<ul>
		<li>Gather username</li>
		<li>Take input and store in database</li>
		<li>Allow edit if already in database</li>
		<li>Validate hours preferences input with PHP</li>
		<li>Validate Input with JS</li>
		<li>Validate Input with PHP</li>
	</ul></li>

	<li>To do<ul>
		<li>Login/security</li>
	</ul></li>
</ul>


<br/>
Management Page Tasks:
<ul>
	<li>Completed<ul>
		<li>Delete user option in list users</li>
		<li>Scheduler Linked</li>
	</ul></li>	

	<li>To do<ul>
		<li>Last modified value on deskie info</li>
		<li>Validate all button?</li>
		<li>Login/security</li>
		<li>Fix sort by desk (Works, hacked together, not best solution)</li>
		<li>Place at desk: Max at desk. Is a DR? Check algorithm</li>
		<li>Availability by shift page</li>

	</ul></li>
</ul>

<br/>
Availability By Hour Page Tasks:
<ul>
	<li>Completed<ul>
		<li>Gather username by hour</li>
		<li>Print table by hour with box colored by rank</li>
	</ul></li>

	<li>To do<ul>
		<li>Optimize, joins, objects, no info table</li>
		<li>Allow user input... AJAX</li>
		<li>Make it not look like crap</li>
	</ul></li>
</ul>


<br/>
Scheduler Tasks:
<ul>
	<li>Completed<ul>
		<li>Take input: desk desired</li>
		<li>Select only deskies at this desk</li>
		<li>Rank shifts by difficulty with average rank of shift</li>
		<li>Sort the shifts</li>
		<li>Start with hardest shift, until all shifts covered</li>
		<li>Assign deskie</li>	
		<li>Determine who can cover<ul>
			<li>1.5*shift_rank - average_rank</li>
			<li>Need to factor in remaining shifts alloted</li>
			<li>Make sure ONLY negative if there is a class</li>
			<li>remaining_ranks*(3*shift_rank - average_rank). If average is 300, shift rank is 100, will be zero.</li>
			<li>Don't allow multiple shifts in a day (Except for dubs)</li>
		</ul></li>
		<li>Color background of person's shift preference on schedule</li>
		<li>Count total shifts given</li>
		<li>Print table</li>
		<li>Output CSV</li>
		<li>Wads Mail Shift...</li>

	</ul></li>	

	<li>To do<ul>
		<li>Store in database.</li>
		<li>Split shifts</li>
		<li>No shift next to class (Implement at assignment, with composite)</li>
		<li>Branching/improve logic</li>
		<li>Reserve shifts (ACs)</li>
		<li>Check if something should be run first</li>
		<li>Think of a less hack-y way to do wads mail shift</li>
	</ul></li>	
</ul>

<br/>
Documentation:
<ul>
	<li>Completed<ul>
	</ul></li>	

	<li>To do<ul>
		<li>Everything</li>
		<li>Check over commenting in code. Make very clear</li>
	</ul></li>	
</ul>

<br/>
Etc Tasks:
<ul>
	<li>Completed<ul>
		<li>Status page</li>
		<li>LOTS of optimizing/fixing my crappy first time coding parts/modulizing</li>
	</ul></li>	

	<li>To do<ul>
		<li>Availability by hour. Desk and global</li>
		<li>More optimization/fix crappy bits</li>
		<li>Switch most arrays over to objects, use joins rather than hour and info tables</li>
	</ul></li>	
</ul>

<script language="Javascript">
document.write("This page last updated: " + document.lastModified +"");
</SCRIPT>

<?php
include('footer.php');
?>

