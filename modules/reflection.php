<?php

add_action('wpcf7_init', 'wpcf7_add_form_tag_reflection', 10, 0);

function wpcf7_add_form_tag_reflection(): void {
    wpcf7_add_form_tag(
        'reflection',
        'wpcf7_reflection_form_tag_handler',
        [
            'name-attr' => true,
            'display-block' => true,
            'not-for-mail' => true,
        ]
    );
}

function wpcf7_reflection_form_tag_handler(WPCF7_FormTag $tag): string {
    if (empty($tag->name)) {
        return '';
    }

    $content = '';

    $values = (array) wpcf7_get_hangover($tag->name);

    if ($values && !wpcf7_get_validation_error($tag->name)) {
        $values = array_map(
            function ($val) use ($tag) {
                return sprintf(
                    '<output name="%1$s">%2$s</output>',
                    esc_attr($tag->name),
                    esc_html($val)
                );
            },
            $values
        );

        $content = implode('', $values);
    }

    return sprintf(
        '<fieldset %1$s>%2$s</fieldset>',
        wpcf7_format_atts([
            'data-reflection-of' => $tag->name,
            'class' => $tag->get_class_option(
                wpcf7_form_controls_class($tag->type)
            ),
            'id' => $tag->get_id_option(),
        ]),
        $content
    );
}
