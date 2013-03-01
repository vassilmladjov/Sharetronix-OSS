<?php
	class tableCreator
	{
		public $form_enctype 		= '';
		public $form_action 		= '';
		public $form_description 	= '';
		public $form_title 			= '';
		public $form_placeholder 	= '';
		public $max_input_length 	= 50;
		public $input_autocomplete	= 'autocomplete="off"';
		
		public function __construct()
		{
			$this->setPlaceholder('input_table_additional_field');
		}
		
		public function setPlaceholder( $name )
		{
			$this->form_placeholder = '{%'. $name .'%}';
		}
		
		public function createTableInput( $rows, $cssClass='' )
		{
			global $C;
		
			$html =  !empty($this->form_title)? '<h3>'. $this->form_title .'</h3>' : '';
			$html .= !empty($this->form_description)? '<div class="form-description">'.$this->form_description.'</div>' : '';
			$html .= '<form action="'.( !empty($this->action)? $C->SITE_URL.$this->action : '' ).'" method="POST" '.$this->form_enctype.' >';
			$html .= '<table class="form-container '.$cssClass.'">';
		
			foreach( $rows as $r ){
				$html .= $r;
			}
		
			$html .= $this->form_placeholder.
					'</table></form>';
		
			return $html;
		}
		
		public function inputField( $row_name, $form_name, $form_value, $max_length=50 )
		{
			return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><input type="text" id="'. $form_name .'" name="'. $form_name .'" value="'. $form_value .'" maxlength="'. $this->max_input_length .'" '.$this->input_autocomplete.' /></td>
				</tr>';
		}
		public function fileField( $row_name, $form_name, $form_value='' )
		{
			return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><input type="file" id="'. $form_name .'" name="'. $form_name .'" value="'. $form_value .'" /></td>
				</tr>';
		}
		public function hiddenField( $form_name, $form_value='' )
		{
			return '<input type="hidden" id="'.$form_name.'" name="'. $form_name .'" value="'. $form_value .'" />';
		}
		public function passField( $row_name, $form_name, $form_value='' )
		{
			return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><input type="password" id="'. $form_name .'" name="'. $form_name .'" value="'. $form_value .'" '.$this->input_autocomplete.' /></td>
				</tr>';
		}
		public function textField( $row_name, $row_content, $cssClass='' )
		{
			return '<tr class="'.$cssClass.'">
					<td class="field-title">'. $row_name .'</td>
					<td>'. $row_content .'</td>
				</tr>';
		}
		
		public function textArea( $row_name, $form_name, $form_value = '' )
		{
			return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><textarea id="'. $form_name .'" name="'. $form_name .'" >'. $form_value .'</textarea></td>
				</tr>';
		}
		
		public function selectField( $row_name, $form_name, $option_elements, $selected = '' )
		{
			$html ='<tr>
				<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
				<td>
					<select id="'. $form_name .'" name="'. $form_name .'" >';
		
			foreach($option_elements as $k=>$v) {
				$html .= '<option value="'.$k.'"'. ( ($k==$selected)?' selected="selected"':'' ) .'>'. htmlspecialchars($v) .'</option>';
			}
			$html .= '</select>
				</td></tr>';
		
			return $html;
		}
		
		public function checkBox( $row_name, $checkbox_elements)
		{
			//check for selected
			$html = '<tr><td class="field-title">'. $row_name .'</td><td>';
		
			foreach( $checkbox_elements as $v ){
				$html .= '<label><input type="checkbox" name="'.$v[0].'" value="'.$v[1].'" '.($v[1] == $v[3]? 'checked' : '').' /> <span>'.$v[2].'</span></label>';
			}
			$html .= '</td></tr>';
				
			return $html;
		}
		
		public function radioButton( $table_row_name, $radio_btns_name, $radio_buttons, $radio_btn_selected = '' )
		{
			//check for selected
			$html = '<tr><td class="field-title">'. $table_row_name .'</td><td>';
		
			foreach( $radio_buttons as $name=>$description ){
				$html .= '<label class="field-container"><input type="radio" name="'. $radio_btns_name .'" value="'. $name .'" '. ( ($name == $radio_btn_selected)? 'checked' : '' ) .' /> <span>'. $description .'</span></label>';
			}
			$html .= '<div class="clear"></div></td></tr>';
				
			return $html;
		}
		
		public function submitButton( $name, $value )
		{
			return '<tr>
					<td></td>
					<td><button type="submit" name="'.$name.'" class="btn blue"><span>'.$value.'</span></button></td>
				</tr>';
		}
	}