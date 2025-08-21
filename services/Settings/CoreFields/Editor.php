<?php

/**
 * Editor field class
 *
 * @package notification
 */

namespace Netdust\Services\Settings\CoreFields;

use Netdust\Logger\Logger;

/**
 * Editor class
 */
class Editor
{
	/**
	 * Editor field
	 *
	 * @param \BracketSpace\Notification\Utils\Settings\Field $field Field instance.
	 * @return void
	 */
	public function input($field)
	{
		$wpautop = $field->addon('wpautop')
			? $field->addon('wpautop')
			: true;
		$mediaButtons = $field->addon('media_buttons')
			? $field->addon('media_buttons')
			: false;
		$textareaRows = $field->addon('textarea_rows')
			? $field->addon('textarea_rows')
			: 10;
		$teeny = $field->addon('teeny')
			? $field->addon('teeny')
			: false;

		$settings = [
			'textarea_name' => $field->inputName(),
			'editor_css' => null,
			'wpautop' => $wpautop,
			'media_buttons' => $mediaButtons,
			'textarea_rows' => $textareaRows,
			'teeny' => $teeny,
            'tinymce'       => [
                'wpautop' => $wpautop,
                'forced_root_block' => 'p', // <- this one is key
            ],
		];

		wp_editor($field->value(), $field->inputId(), $settings);
	}

	/**
	 * Sanitize input value
	 *
	 * @param string $value Saved value.
	 * @return string        Sanitized content
	 */
	public function sanitize($value)
	{
		return wp_kses_post($value);
	}
}
