<?php

namespace JazzMan\ContactForm7\Admin;

use JazzMan\ContactForm7\WPCF7_ConfigValidator;
use JazzMan\ContactForm7\WPCF7_ContactForm;
use WP_List_Table;


if (!class_exists('WP_List_Table')) {
	require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class WPCF7_Contact_Form_List_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct([
			'singular' => 'post',
			'plural' => 'posts',
			'ajax' => false,
		]);
	}

	public static function define_columns() {
		return [
			'cb' => '<input type="checkbox" />',
			'title' => __('Title', 'contact-form-7'),
			'shortcode' => __('Shortcode', 'contact-form-7'),
			'author' => __('Author', 'contact-form-7'),
			'date' => __('Date', 'contact-form-7'),
		];
	}

	public function prepare_items(): void {
		$current_screen = get_current_screen();
		$per_page = $this->get_items_per_page('wpcf7_contact_forms_per_page');

		$args = [
			'posts_per_page' => $per_page,
			'orderby' => 'title',
			'order' => 'ASC',
			'offset' => ($this->get_pagenum() - 1) * $per_page,
		];

		if (!empty($_REQUEST['s'])) {
			$args['s'] = $_REQUEST['s'];
		}

		if (!empty($_REQUEST['orderby'])) {
			if ('title' == $_REQUEST['orderby']) {
				$args['orderby'] = 'title';
			} elseif ('author' == $_REQUEST['orderby']) {
				$args['orderby'] = 'author';
			} elseif ('date' == $_REQUEST['orderby']) {
				$args['orderby'] = 'date';
			}
		}

		if (!empty($_REQUEST['order'])) {
			if ('asc' == strtolower($_REQUEST['order'])) {
				$args['order'] = 'ASC';
			} elseif ('desc' == strtolower($_REQUEST['order'])) {
				$args['order'] = 'DESC';
			}
		}

		$this->items = WPCF7_ContactForm::find($args);

		$total_items = WPCF7_ContactForm::count();
		$total_pages = ceil($total_items / $per_page);

		$this->set_pagination_args([
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		]);
	}

	public function get_columns() {
		return get_column_headers(get_current_screen());
	}

	/**
	 * @param \JazzMan\ContactForm7\WPCF7_ContactForm $item
	 *
	 * @return string|void
	 */
	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item->id()
		);
	}

	/**
	 * @param \JazzMan\ContactForm7\WPCF7_ContactForm $item
	 *
	 * @return string
	 */
	public function column_title($item) {
		$edit_link = add_query_arg(
			[
				'post' => absint($item->id()),
				'action' => 'edit',
			],
			menu_page_url('wpcf7', false)
		);

		$output = sprintf(
			'<a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url($edit_link),
			esc_attr(sprintf(
			/* translators: %s: title of contact form */
				__('Edit &#8220;%s&#8221;', 'contact-form-7'),
				$item->title()
			)),
			esc_html($item->title())
		);

		$output = sprintf('<strong>%s</strong>', $output);

		if (wpcf7_validate_configuration()
		    && current_user_can('wpcf7_edit_contact_form', $item->id())) {
			$config_validator = new WPCF7_ConfigValidator($item);
			$config_validator->restore();

			if ($count_errors = $config_validator->count_errors()) {
				$error_notice = sprintf(
					_n(
					/* translators: %s: number of errors detected */
						'%s configuration error detected',
						'%s configuration errors detected',
						$count_errors,
						'contact-form-7'
					),
					number_format_i18n($count_errors)
				);

				$output .= sprintf(
					'<div class="config-error"><span class="icon-in-circle" aria-hidden="true">!</span> %s</div>',
					$error_notice
				);
			}
		}

		return $output;
	}

	public function column_author($item) {
		$post = get_post($item->id());

		if (!$post) {
			return;
		}

		$author = get_userdata($post->post_author);

		if (false === $author) {
			return;
		}

		return esc_html($author->display_name);
	}

	/**
	 * @param \JazzMan\ContactForm7\WPCF7_ContactForm $item
	 *
	 * @return string
	 */
	public function column_shortcode($item) {
		$shortcodes = [$item->shortcode()];

		$output = '';

		foreach ($shortcodes as $shortcode) {
			$output .= "\n".'<span class="shortcode"><input type="text"'
			           .' onfocus="this.select();" readonly="readonly"'
			           .' value="'.esc_attr($shortcode).'"'
			           .' class="large-text code" /></span>';
		}

		return trim($output);
	}

	/**
	 * @param \JazzMan\ContactForm7\WPCF7_ContactForm $item
	 *
	 * @return string
	 */
	public function column_date($item) {
		$datetime = get_post_datetime($item->id());

		if (false === $datetime) {
			return '';
		}

		return sprintf(
		/* translators: 1: date, 2: time */
			__('%1$s at %2$s', 'contact-form-7'),
			/* translators: date format, see https://www.php.net/date */
			$datetime->format(__('Y/m/d', 'contact-form-7')),
			/* translators: time format, see https://www.php.net/date */
			$datetime->format(__('g:i a', 'contact-form-7'))
		);
	}

	protected function get_sortable_columns() {
		return [
			'title' => ['title', true],
			'author' => ['author', false],
			'date' => ['date', false],
		];
	}

	protected function get_bulk_actions() {
		return [
			'delete' => __('Delete', 'contact-form-7'),
		];
	}

	protected function column_default($item, $column_name) {
		return '';
	}

	/**
	 * @param \JazzMan\ContactForm7\WPCF7_ContactForm $item
	 * @param string $column_name
	 * @param string $primary
	 *
	 * @return string
	 */
	protected function handle_row_actions($item, $column_name, $primary) {
		if ($column_name !== $primary) {
			return '';
		}

		$edit_link = add_query_arg(
			[
				'post' => absint($item->id()),
				'action' => 'edit',
			],
			menu_page_url('wpcf7', false)
		);

		$actions = [
			'edit' => wpcf7_link($edit_link, __('Edit', 'contact-form-7')),
		];

		if (current_user_can('wpcf7_edit_contact_form', $item->id())) {
			$copy_link = add_query_arg(
				[
					'post' => absint($item->id()),
					'action' => 'copy',
				],
				menu_page_url('wpcf7', false)
			);

			$copy_link = wp_nonce_url(
				$copy_link,
				'wpcf7-copy-contact-form_'.absint($item->id())
			);

			$actions = array_merge($actions, [
				'copy' => wpcf7_link($copy_link, __('Duplicate', 'contact-form-7')),
			]);
		}

		return $this->row_actions($actions);
	}
}
