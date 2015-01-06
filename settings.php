<div class="wrap">

	<h2>Thingdom WordPress Configuration</h2>

	<form method="post" action="options.php">

	<?php 
		settings_fields('thingdom-options'); 
		do_settings_sections('thingdom-settings-admin');
	?>

		<table class="form-table">

	<?php 
		foreach($this->options as $id => $option) : 

			$attr = array(
				'id' => $this->tag.$id,
				'name' => $this->tag.$id,
				'type' => $option['type'],
				'placeholder' => $option['type'] == 'text' ? "placeholder = '{$option['placeholder']}'" : ''
			);

			$value = get_option($attr['name']);

			if($attr['type'] == 'checkbox' && empty($value)) {
				$value = 1;
			}
	?>
			<tr valign="top">
				<th scope="row"><?php echo $option['label']; ?>:</th>
				<td>
					<input 
						type="<?php echo $attr['type']; ?>" 
						id="<?php echo $attr['id']; ?>"
						name="<?php echo $attr['name']; ?>" 
						<?php echo $attr['placeholder']; ?> 
						value="<?php echo $value; ?>" 
						<?php if($attr['type'] == 'checkbox') {
							if(get_option($attr['name']) == 1) {
								echo " checked='checked'";
							}
						}
						?>
					/>
				</td>
			</tr>

	<?php 
		endforeach; 
	?>

		</table>	

	<?php 
		echo submit_button(); 
	?>
	</form>

</div>