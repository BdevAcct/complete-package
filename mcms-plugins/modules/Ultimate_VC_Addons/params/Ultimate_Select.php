<?php
if(!class_exists('Ultimate_Select_Param'))
{
	class Ultimate_Select_Param
	{
		function __construct()
		{
			if(defined('MCMSB_VC_VERSION') && version_compare(MCMSB_VC_VERSION, 4.8) >= 0) {
				if(function_exists('vc_add_shortcode_param'))
				{
					vc_add_shortcode_param('ult_select2' , array($this, 'select2_param'));
				}
			}
			else {
				if(function_exists('add_shortcode_param'))
				{
					add_shortcode_param('ult_select2' , array($this, 'select2_param'));
				}
			}
		}

		function select2_param($settings, $value){
			$param_name = isset($settings['param_name']) ? $settings['param_name'] : '';
			$type = isset($settings['type']) ? $settings['type'] : '';
			$class = isset($settings['class']) ? $settings['class'] : '';
			$json = isset($settings['json']) ? $settings['json'] : '';
			$jsonIterator = json_decode($json,true);
			$selector = '<select name="'.esc_attr( $param_name ).'" class="mcmsb_vc_param_value ' . esc_attr( $param_name ) . ' ' . esc_attr( $type ) . ' ' . esc_attr( $class ) . '">';
			foreach ($jsonIterator as $key => $val) {
				if(is_array($val)) {
					$labels = str_replace('_',' ', $key);
					$selector .= '<optgroup label="'.ucwords( esc_attr( $labels ) ).'">';
					foreach($val as $label => $style){
						$label = str_replace('_',' ', $label);
						if($style == $value)
							$selector .= '<option selected value="'.esc_attr( $style ).'">'.esc_html__($label,"ultimate_vc").'</option>';
						else
							$selector .= '<option value="'.esc_attr( $style ).'">'.esc_html__($label,"ultimate_vc").'</option>';
					}
				} else {
					if($val == $value)
						$selector .= "<option selected value=".esc_attr( $val ).">".esc_html__($key,"ultimate_vc")."</option>";
					else
						$selector .= "<option value=".esc_attr( $val ).">".esc_html__($key,"ultimate_vc")."</option>";
				}
			}
			$selector .= '<select>';

			$output = '';
			$output .= '<div class="select2_option" style="width: 45%; float: left;">';
			$output .= $selector;
			$output .= '</div>';
			return $output;
		}

	}
}

if(class_exists('Ultimate_Select_Param'))
{
	$Ultimate_Select_Param = new Ultimate_Select_Param();
}
