<?php
class HTML {
	
	public static function beginForm($options, $buffered = false){
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
		$method = isset($options['method']) ? $options['method'] : 'post';
		$charset = isset($options['charset']) ? $options['charset'] : 'utf-8';
		$action = isset($options['action']) ? $options['action'] : '#foo';
		$enctype = isset($options['enctype']) ? $options['enctype'] : 'multipart/form-data';
		$result = '<form'.$class.' method="'.$method.'" accept-charset="'.$charset.'" action="'.$action.'" enctype="'.$enctype.'">'."\n";
		if ($buffered)
			return $result;
		else 
			echo $result;
	}
	
	public static function endForm(){
		echo '</form>'."\n";
	}	
	
	public static function submit ($options, $buffered = false) {
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$result = '<input type="submit" name="'.$name.'" value="'.$value.'" />';
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}

	public static function href($options, $buffered = false){
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : ''; 
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$rel = isset($options['rel']) ? ' rel="'.$options['rel'].'"' : '';
		$result = '<a href="' .$options['href']. '"' .$class.$id.$rel. '>' .$options['title']. '</a>';
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}	
	
	public static function hidden ($options, $buffered = false) {
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$result = '<input type="hidden"'.$class.$id.' name="'.$options['name'].'" value="'.$options['value'].'" />';
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}	
	
	public static function checkbox ($options, $buffered = false) {
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$checked = isset($options['checked']) ? ' checked="checked"' : '';
		
		$result = '<input type="checkbox"'.$class.$id.$checked.' name="'.$options['name'].'" value="'.$options['value'].'" />';
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}	
	
	public static function text ($options, $buffered = false) {
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$value = isset($options['value']) ? ' value="'.$options['value'].'"' : '';
		$size = isset($options['size']) ? ' size="'.$options['size'].'"' : '';
		$maxlen = isset($options['maxlength']) ? ' maxlength="'.$options['maxlength'].'"' : '';
		$result = '<input type="text"'.$class.$id.' name="'.$options['name'].'"'.$value.$size.$maxlen.' />';
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}		

	public static function file ($options, $buffered = false) {
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$name = isset($options['name']) ? ' name="'.$options['name'].'"' : '';
		$size = isset($options['size']) ? ' size="'.$options['size'].'"' : '';
		$result = '<input type="file"'.$class.$id.$name.$size.' />';
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}		
	
	public static function select ($options, $buffered = false) {
		$class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
		$id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$name = isset($options['name']) ? ' name="'.$options['name'].'"' : '';
		$selected = isset($options['selected']) ? $options['selected'] : ''; 
		$result = '<select '.$class.$id.$name.'>'."\n";
		foreach($options['values'] as $key => $item) {
			if ( $selected==$key ) 
				$is_selected = ' selected="selected"';
			else
				$is_selected = '';
			$result .= '<option'.$is_selected.' value="'.$key.'">'.forum_htmlencode($item).'</option>'."\n";			
		}
		$result .= '</select>'."\n";
		if ($buffered)
			return $result;
		else 
			echo $result;		
	}
	
	
	
	
}