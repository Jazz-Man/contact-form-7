<?php

namespace JazzMan\ContactForm7\Admin;

abstract class WPCF7_WelcomePanelColumn {
	public function print_content(): void {
		$icon = sprintf(
			'<span class="dashicons dashicons-%s" aria-hidden="true"></span>',
			esc_attr($this->icon())
		);

		$title = sprintf(
			'<h3>%1$s %2$s</h3>',
			$icon,
			$this->title()
		);

		$content = $this->content();

		if (is_array($content)) {
			$content = implode("\n\n", $content);
		}

		$content = wp_kses_post($content);
		$content = wptexturize($content);
		$content = convert_chars($content);
		$content = wpautop($content);

		echo "\n";
		echo '<div class="welcome-panel-column">';
		echo $title;
		echo $content;
		echo '</div>';
	}

	abstract protected function icon();

	abstract protected function title();

	abstract protected function content();
}
