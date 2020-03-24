<?php
namespace Sleek\Oembed;

####################
# Nicer video embeds
add_action('after_setup_theme', function () {
	# Just responsive video (div.video around iframe)
	if (get_theme_support('sleek/oembed/responsive_video')) {
		add_filter('oembed_dataparse', function ($return, $data, $url) {
			if (
				(strtolower($data->provider_name) === 'youtube' and !get_theme_support('sleek/oembed/youtube')) or
				(strtolower($data->provider_name) === 'vimeo' and !get_theme_support('sleek/oembed/vimeo'))
			) {
				return '<div class="video">' . $return . '</div>';
			}

			return $return;
		});
	}

	# With API
	if (get_theme_support('sleek/oembed/youtube') or get_theme_support('sleek/oembed/vimeo')) {
		# Wrap oembeds in figure with thumbnail and title
		# NOTE: This is cached by WP
		add_filter('oembed_dataparse', function ($return, $data, $url) {
			# Only do this for YouTube and Vimeo
			if (strtolower($data->provider_name) === 'youtube' or strtolower($data->provider_name) === 'vimeo') {
				$html = strtolower($data->provider_name) === 'youtube' ? \Sleek\Utils\add_iframe_args($return, ['enablejsapi' => '1']) : $return;
				$return = '<figure class="video-embed video-embed--' . strtolower($data->provider_name) . '"><div class="embed">';
				$return .= '<div class="video">' . $html . '</div>';
				$return .= (isset($data->thumbnail_url) and !empty($data->thumbnail_url)) ? '<div class="thumbnail"><img src="' . $data->thumbnail_url . '" loading="lazy"></div>' : '';
				$return .= '</div>';
				$return .= (isset($data->title) and !empty($data->title)) ? "<figcaption>{$data->title}</figcaption>" : '';
				$return .= '</figure>';
			}

			return $return;
		}, 10, 3);

		# Unstyle stuff in the admin
		add_action('admin_head', function () {
			?>
			<style>
				figure.video-embed {
					margin-left: 0;
					margin-right: 0;
				}

				figure.video-embed div.thumbnail {
					display: none;
				}

				figure.video-embed figcaption {
					display: none;
				}
			</style>
			<?php
		});

		# Unstyle stuff in the WYSIWYG
		# NOTE: So impressed this is comma delimited instead of an array 😂
		add_filter('mce_css', function ($stylesheets) {
			$stylesheets .= ',' . get_stylesheet_directory_uri() . '/vendor/powerbuoy/sleek-gallery/oembed.css';

			return $stylesheets;
		});

		# Use YouTube/Vimeo API:s to play video on click of thumbnail
		add_action('wp_footer', function () {
			if (get_theme_support('sleek/oembed/youtube')) {
				?>
				<script src="https://www.youtube.com/iframe_api" async defer></script>
				<script>
					function onYouTubeIframeAPIReady() {
						var states = {
							'??': 'unknown',
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

							iframe.sleekYTPlayer = new YT.Player(iframe, {
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
				<?php
			}

			if (get_theme_support('sleek/oembed/vimeo')) {
				?>
				<script src="https://player.vimeo.com/api/player.js"></script>
				<script>
					document.querySelectorAll('figure.video-embed--vimeo').forEach(function (el) {
						var iframe = el.querySelector('iframe');
						var thumbnail = el.querySelector('.thumbnail');

						iframe.sleekVimeoPlayer = new Vimeo.Player(iframe);

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
			}
		});
	}
});
