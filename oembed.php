<?php
namespace Sleek\Oembed;

####################
# Nicer video embeds
add_action('after_setup_theme', function () {
	# Just responsive video (div.video around iframe)
	if (get_theme_support('sleek/oembed/responsive_video')) {
		add_filter('embed_oembed_html', function ($html) {
			if ($html) {
				return '<div class="video">' . $html . '</div>';
			}

			return $html;
		}, 99, 1);
	}

	# Replace src with data-src
	if (get_theme_support('sleek/oembed/data_src')) {
		add_filter('embed_oembed_html', function ($html) {
			return str_replace('src="', 'data-src="', $html);
		});
	}

	# Make sure ACF video runs through the WP filter
	add_filter('acf/format_value/type=oembed', function ($value) {
		return apply_filters('embed_oembed_html', $value);
	}, 99, 1);

	# Store oembed data on the iframe and enable YouTube JS API
	add_filter('oembed_dataparse', function ($return, $data, $url) {
		unset($data->html);

		$args = [];
		$atts = [
			'loading="lazy"',
			'data-oembed-url="' . $url . '"',
			"data-oembed='" . json_encode($data, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS) . "'"
		];

		if ($data->provider_name === 'YouTube') {
			$args['enablejsapi'] = 1;
			$atts[] = 'data-youtube-id="' . \Sleek\Utils\get_youtube_id($return) . '"';
		}

		$return = \Sleek\Utils\add_iframe_args($return, $args, implode(' ', $atts));

		return $return;
	}, 10, 3);
});
