<?php
if ( ! defined( 'BASED_TREE_URI' ) ) {
	die( '-1' );
}

/**
 * @var $this MCMSBakeryShortCode_VC_Custom_heading
 */

extract( $this->getAttributes( $atts ) );
extract( $this->getStyles( $el_class, $css, $google_fonts_data, $font_container_data, $atts ) );

$settings = get_option( 'mcmsb_js_google_fonts_subsets' );
if ( is_array( $settings ) && ! empty( $settings ) ) {
	$subsets = '&subset=' . implode( ',', $settings );
} else {
	$subsets = '';
}

$link = vc_gitem_create_link( $atts );
if ( ! empty( $link ) ) {
	$text = '<' . $link . '>' . $text . '</a>';
}

if ( ( ! isset( $atts['use_myskin_fonts'] ) || 'yes' !== $atts['use_myskin_fonts'] ) && ! empty( $google_fonts_data ) && isset( $google_fonts_data['values']['font_family'] ) ) {
	mcms_enqueue_style( 'vc_google_fonts_' . vc_build_safe_css_class( $google_fonts_data['values']['font_family'] ), '//fonts.googleapis.com/css?family=' . $google_fonts_data['values']['font_family'] . $subsets );
}

if ( ! empty( $styles ) ) {
	$style = 'style="' . esc_attr( implode( ';', $styles ) ) . '"';
} else {
	$style = '';
}

$output = '';
if ( apply_filters( 'vc_custom_heading_template_use_wrapper', false ) ) {
	$output .= '<div class="' . esc_attr( $css_class ) . '" >';
	$output .= '<' . $font_container_data['values']['tag'] . ' ' . $style . ' >';
	$output .= $text;
	$output .= '</' . $font_container_data['values']['tag'] . '>';
	$output .= '</div>';
} else {
	$output .= '<' . $font_container_data['values']['tag'] . ' ' . $style . ' class="' . esc_attr( $css_class ) . '">';
	$output .= $text;
	$output .= '</' . $font_container_data['values']['tag'] . '>';
}

echo $output;
