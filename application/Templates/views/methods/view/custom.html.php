<?php
namespace Blueline;
use Helpers\Text, Helpers\Stages, Helpers\Dates;

View::element( 'default.header', array(
	'title' => 'Custom Method | Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'headerSearch' => array( 
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	)
) );
?>
<section>
	<header>
		<h1>Custom Method</h1>
	</header>
	<div class="content">
		<p>Enter the basic details of a valid method below, and click 'View' to view the line and extended details.</p>
		<form id="customMethodForm" action="/methods/view/custom" method="get">
			<fieldset>
				<table class="formTable">
					<tr>
						<th><label for="name0">Name:</label></th>
						<td><input class="longText" id="name0" name="name[]" type="text" required /></td>
					</tr>
					<tr>
						<th><label for="notation0">Place Notation:</label></th>
						<td><input class="longText" id="notation0" name="notation[]" type="text" required /></td>
					</tr>
					<tr>
						<th><label for="stage0">Stage:</label></th>
						<td><input class="tinyText" id="stage0" name="stage[]" type="number" min="3" max="24" required /></td>
					</tr>
				</table>
			</fieldset>
			<input class="submit" id="customMethodSubmit" type="submit" value="View" />
		</form>
	</div>
</section>
<?php View::element( 'default.footer' ); ?>
