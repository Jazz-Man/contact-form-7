<?php
/**
 ** A base module for the following types of tags:
 ** 	[date] and [date*]		# Date.
 */

/* form_tag handler */

add_action('wpcf7_init', 'wpcf7_add_form_tag_date', 10, 0);

function wpcf7_add_form_tag_date(): void {
    wpcf7_add_form_tag(
        ['date', 'date*'],
        'wpcf7_date_form_tag_handler',
        [
            'name-attr' => true,
        ]
    );
}

function wpcf7_date_form_tag_handler(WPCF7_FormTag $tag): string {
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = wpcf7_get_validation_error($tag->name);

    $class = wpcf7_form_controls_class($tag->type);

    $class .= ' wpcf7-validates-as-date';

    if ($validation_error) {
        $class .= ' wpcf7-not-valid';
    }

    $atts = [];

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['min'] = $tag->get_date_option('min');
    $atts['max'] = $tag->get_date_option('max');
    $atts['step'] = $tag->get_option('step', 'int', true);
    $atts['readonly'] = $tag->has_option('readonly');

    if ($tag->is_required()) {
        $atts['aria-required'] = 'true';
    }

    if ($validation_error) {
        $atts['aria-invalid'] = 'true';
        $atts['aria-describedby'] = wpcf7_get_validation_error_reference(
            $tag->name
        );
    } else {
        $atts['aria-invalid'] = 'false';
    }

    $value = (string) reset($tag->values);

    if ($tag->has_option('placeholder')
    || $tag->has_option('watermark')) {
        $atts['placeholder'] = $value;
        $value = '';
    }

    $value = $tag->get_default_option($value);

    if ($value) {
        $datetime_obj = date_create_immutable(
            preg_replace('/[_]+/', ' ', $value),
            wp_timezone()
        );

        if ($datetime_obj) {
            $value = $datetime_obj->format('Y-m-d');
        }
    }

    $value = wpcf7_get_hangover($tag->name, $value);

    $atts['value'] = $value;
    $atts['type'] = $tag->basetype;
    $atts['name'] = $tag->name;

    return sprintf(
        '<span class="wpcf7-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
        esc_attr($tag->name),
        wpcf7_format_atts($atts),
        $validation_error
    );
}

add_action(
    'wpcf7_swv_create_schema',
    'wpcf7_swv_add_date_rules',
    10,
    2
);

function wpcf7_swv_add_date_rules(WPCF7_SWV_Schema $schema, WPCF7_ContactForm $contact_form): void {
    $tags = $contact_form->scan_form_tags([
        'basetype' => ['date'],
    ]);

    foreach ($tags as $tag) {
        if ($tag->is_required()) {
            $schema->add_rule(
                wpcf7_swv_create_rule('required', [
                    'field' => $tag->name,
                    'error' => wpcf7_get_message('invalid_required'),
                ])
            );
        }

        $schema->add_rule(
            wpcf7_swv_create_rule('date', [
                'field' => $tag->name,
                'error' => wpcf7_get_message('invalid_date'),
            ])
        );

        $min = $tag->get_date_option('min');
        $max = $tag->get_date_option('max');

        if (false !== $min) {
            $schema->add_rule(
                wpcf7_swv_create_rule('mindate', [
                    'field' => $tag->name,
                    'threshold' => $min,
                    'error' => wpcf7_get_message('date_too_early'),
                ])
            );
        }

        if (false !== $max) {
            $schema->add_rule(
                wpcf7_swv_create_rule('maxdate', [
                    'field' => $tag->name,
                    'threshold' => $max,
                    'error' => wpcf7_get_message('date_too_late'),
                ])
            );
        }
    }
}

/* Messages */

add_filter('wpcf7_messages', 'wpcf7_date_messages', 10, 1);

function wpcf7_date_messages($messages) {
    return array_merge($messages, [
        'invalid_date' => [
            'description' => __('Date format that the sender entered is invalid', 'contact-form-7'),
            'default' => __('Please enter a date in YYYY-MM-DD format.', 'contact-form-7'),
        ],

        'date_too_early' => [
            'description' => __('Date is earlier than minimum limit', 'contact-form-7'),
            'default' => __('This field has a too early date.', 'contact-form-7'),
        ],

        'date_too_late' => [
            'description' => __('Date is later than maximum limit', 'contact-form-7'),
            'default' => __('This field has a too late date.', 'contact-form-7'),
        ],
    ]);
}

/* Tag generator */

add_action('wpcf7_admin_init', 'wpcf7_add_tag_generator_date', 19, 0);

function wpcf7_add_tag_generator_date(): void {
    $tag_generator = WPCF7_TagGenerator::get_instance();
    $tag_generator->add(
        'date',
        __('date', 'contact-form-7'),
        'wpcf7_tag_generator_date'
    );
}

function wpcf7_tag_generator_date($contact_form, $args = ''): void {
    $args = wp_parse_args($args, []);
    $type = 'date';

    $description = __('Generate a form-tag for a date input field. For more details, see %s.', 'contact-form-7');

    $desc_link = wpcf7_link(__('https://contactform7.com/date-field/', 'contact-form-7'), __('Date field', 'contact-form-7'));

    ?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf(esc_html($description), $desc_link); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html(__('Required field', 'contact-form-7')); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr($args['content'].'-name'); ?>"><?php echo esc_html(__('Name', 'contact-form-7')); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'].'-name'); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr($args['content'].'-values'); ?>"><?php echo esc_html(__('Default value', 'contact-form-7')); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr($args['content'].'-values'); ?>" /><br />
	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html(__('Use this text as the placeholder of the field', 'contact-form-7')); ?></label></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html(__('Range', 'contact-form-7')); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html(__('Range', 'contact-form-7')); ?></legend>
		<label>
		<?php echo esc_html(__('Min', 'contact-form-7')); ?>
		<input type="date" name="min" class="date option" />
		</label>
		&ndash;
		<label>
		<?php echo esc_html(__('Max', 'contact-form-7')); ?>
		<input type="date" name="max" class="date option" />
		</label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr($args['content'].'-id'); ?>"><?php echo esc_html(__('Id attribute', 'contact-form-7')); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'].'-id'); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr($args['content'].'-class'); ?>"><?php echo esc_html(__('Class attribute', 'contact-form-7')); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'].'-class'); ?>" /></td>
	</tr>
</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr($args['content'].'-mailtag'); ?>"><?php echo sprintf(esc_html(__('To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'contact-form-7')), '<strong><span class="mail-tag"></span></strong>'); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($args['content'].'-mailtag'); ?>" /></label></p>
</div>
<?php
}
