<?php
namespace Sleek\Oembed;

####################
# Nicer video embeds
add_action('after_setup_theme', function () {
	# Just responsive video (div.video around iframe)
	if (get_theme_support('sleek/oembed/responsive_video')) {
		add_filter('embed_oembed_html', function($html) {
			return '<div class="video">' . $html . '</div>';
		}, 99, 1);

		add_filter('acf/format_value/type=oembed', function ($value) {
			return apply_filters('embed_oembed_html', $value);
		}, 99, 1);
	}

	# Store oembed data on the iframe and enable YouTube JS API
	add_filter('oembed_dataparse', function ($return, $data, $url) {
		unset($data->html);

		$args = [];
		$atts = [
			'loading="lazy"',
			'data-oembed-url="' . $url . '"',
			"data-oembed='" . json_encode($data) . "'"
		];

		if ($data->provider_name === 'YouTube') {
			$args['enablejsapi'] = 1;
			$atts[] = 'data-youtube-id="' . \Sleek\Utils\get_youtube_id($return) . '"';
		}

		$return = \Sleek\Utils\add_iframe_args($return, $args, implode(' ', $atts));

		return $return;
	}, 10, 3);
});
