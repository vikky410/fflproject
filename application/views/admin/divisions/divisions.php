<?php //print_r($division_array); ?>
<div class="container">
	<div><a href="<?=site_url('admin/divisions/manage')?>">Add/Delete division</a></div>
	<?=form_open(site_url('admin/divisions/save'))?>

	<?php $options[0] = 'none'; foreach($divisions as $d){$options[$d->id] = $d->name;} ?>
	<?php foreach($division_array as $div_id => $div): ?>
	<p></p>
	<table class="table table-condensed">
	    <tr>
	        <td><strong>Division: <?=$div['name']?></strong></td>    
	    </tr>
	    <?php foreach($div['teams'] as $team): ?>
	    <tr>
	        <td><?=$team['name']?></td>
	        <td><?=form_dropdown($team['id'], $options, $div_id)?></td>
	    </tr>
	    <?php endforeach; ?>
	</table>
	<?php endforeach; ?>

	<?=form_submit('save', 'Save')?>
	<?=form_close()?>
</div>