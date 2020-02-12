<?php
namespace Sleek\Gallery;

##############################
# Remove gallery inline styles
# https://css-tricks.com/snippets/wordpress/remove-gallery-inline-styling/
add_filter('use_default_gallery_style', '__return_false');

############################
# Replace the [caption] HTML
add_filter('img_caption_shortcode', function ($empty, $atts, $content) {
	$atts = shortcode_atts([
		'id' => '',
		'align' => 'alignnone',
		'width' => '',
		'caption' => ''
	], $atts);

	$html = '<figure class="' . esc_attr($atts['align']) . '">';
	$html .= do_shortcode($content);
	$html .= '<figcaption>' . esc_attr($atts['caption']) . '</ficaption>';
	$html .= '</figure>';

	return $html;
}, 10, 3);

##################################################
# Completely rewrite WP's ludicrous gallery output
# https://stackoverflow.com/questions/14585538/customise-the-wordpress-gallery-html-layout
add_filter('post_gallery', function ($string, $attr) {
	$images = get_posts([
		'post_type' => 'attachment',
		'include' => $attr['ids']
	]);

	# FFS... When 3 cols are used WP doesn't set this attribute
	if (!isset($attr['columns'])) {
		$attr['columns'] = 3;
	}

	# If no size is set - that means thumbnail...
	if (!isset($attr['size'])) {
		$attr['size'] = 'thumbnail';
	}

	$html = '<div class="gallery gallery--cols-' . $attr['columns'] . '">';

	foreach ($images as $image) {
		$html .= '<figure>';

		# If no link is set that means we should link to media page
		if (!isset($attr['link'])) {
			$html .= '<a href="' . get_permalink($image->ID) . '">';
		}
		# Link to file
		elseif ($attr['link'] == 'file') {
			$html .= '<a href="' . wp_get_attachment_image_src($image->ID, 'full')[0] . '">';
		}

		# Add the actual image
		$html .= wp_get_attachment_image($image->ID, $attr['size']);

		# Close link
		if (!isset($attr['link']) or $attr['link'] != 'none') {
			$html .= '</a>';
		}

		# Add potential caption
		if ($image->post_excerpt) {
			$html .= '<figcaption>' . get_the_excerpt($image) . '</figcaption>';
		}

		$html .= '</figure>';
	}

	$html .= '</div>';

	return $html;
}, 10, 2);

###################################################
# Wrap all images in the_content in figure elements
# TODO

####################
# Nicer video embeds
add_action('after_setup_theme', function () {
	if (get_theme_support('sleek-oembed')) {
		# Wrap oembeds in figure with thumbnail and title
		add_filter('oembed_dataparse', function ($return, $data, $url) {
			if (is_admin()) {
				return $return;
			}

			$html = strtolower($data->provider_name) === 'youtube' ? \Sleek\Utils\add_iframe_args($return, ['enablejsapi' => '1']) : $return;
			$return = '<figure class="video-embed video-embed--' . strtolower($data->provider_name) . '"><div class="embed">';
			$return .= '<div class="video">' . $html . '</div>';
			$return .= (isset($data->thumbnail_url) and !empty($data->thumbnail_url)) ? '<div class="thumbnail"><img src="' . $data->thumbnail_url . '" loading="lazy"></div>' : '';
			$return .= '</div>';
			$return .= (isset($data->title) and !empty($data->title)) ? "<figcaption>{$data->title}</figcaption>" : '';
			$return .= '</figure>';

			return $return;
		}, 10, 3);

		# Use YouTube/Vimeo API:s to play video on click of thumbnail
		add_action('wp_footer', function () {
			?>
			<script src="https://www.youtube.com/iframe_api" async defer></script>
			<script>
				function onYouTubeIframeAPIReady() {
					var states = {
						'-1': 'unstarted',
						'0': 'ended',
						'1': 'playing',
						'2': 'paused',
						'3': 'buffering',
						'5': 'video-cued'
					};

					document.querySelectorAll('figure.video-embed--youtube').forEach(function (el) {
						var iframe = el.querySelector('iframe');
						var thumbnail = el.querySelector('.thumbnail');
						var player = new YT.Player(iframe, {
							events: {
								onReady: function (e) {
									el.classList.add('video-embed--state-' + (states[e.data] || 'unknown'));
								},
								onStateChange: function (e) {
									for (var [key, value] of Object.entries(states)) {
										el.classList.remove('video-embed--state-' + value);
									}

									el.classList.add('video-embed--state-' + (states[e.data] || 'unknown'));
								}
							}
						});

						thumbnail.addEventListener('click', function (e) {
							player.playVideo();
						});
					});
				}
			</script>

			<script src="https://player.vimeo.com/api/player.js"></script>
			<script>
				document.querySelectorAll('figure.video-embed--vimeo').forEach(function (el) {
					var iframe = el.querySelector('iframe');
					var thumbnail = el.querySelector('.thumbnail');
					var player = new Vimeo.Player(iframe);

					player.on('play', function () {
						el.classList.add('video-embed--state-playing');
					});

					player.on('ended', function () {
						el.classList.remove('video-embed--state-playing');
					});

					player.on('pause', function () {
						el.classList.remove('video-embed--state-playing');
					});

					thumbnail.addEventListener('click', function (e) {
						player.play();
					});
				});
			</script>
			<?php
		});
	}
});
