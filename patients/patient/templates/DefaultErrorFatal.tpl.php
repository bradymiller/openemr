<?php
/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEMR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://www.open-emr.org
 */

	$this->assign('title','Patient Portal');
	$this->assign('nav','home');

	$this->display('_Header.tpl.php');
?>

<div class="container">

	<h1><?php echo xlt('Oh Snap!'); ?></h1>

	<!-- this is used by app.js for scraping -->
	<!-- ERROR <?php $this->eprint($this->message); ?> /ERROR -->

	<h3 onclick="$('#stacktrace').show('slow');" class="well" style="cursor: pointer;"><?php $this->eprint($this->message); ?></h3>

	<p><?php echo xlt('You may want to try returning to the the previous page and verifying that all fields have been filled out correctly.'); ?></p>

	<p><?php echo xlt('If you continue to experience this error please contact support.'); ?></p>

	<div id="stacktrace" class="well hide">
		<p style="font-weight: bold;"><?php echo xlt('Stack Trace'); ?>:</p>
		<?php if ($this->stacktrace) { ?>
			<p style="white-space: nowrap; overflow: auto; padding-bottom: 15px; font-family: courier new, courier; font-size: 8pt;"><pre><?php $this->eprint($this->stacktrace); ?></pre></p>
		<?php } ?>
	</div>

</div> <!-- /container -->

<?php
	$this->display('_Footer.tpl.php');
?>