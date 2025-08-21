<?php

/**
 * Text field class
 *
 * @package notification
 */

namespace Netdust\Services\Settings\CoreFields;

/**
 * Text class
 */
class Text
{
	/**
	 * Text field
	 *
	 * @param \BracketSpace\Notification\Utils\Settings\Field $field Field instance.
	 * @return void
	 */
	public function input($field)
	{
		printf(
			'<label><input type="text" id="%s" name="%s" value="%s" class="widefat"></label>',
			esc_attr($field->inputId()),
			esc_attr($field->inputName()),
			esc_attr($field->value())
		);
	}

	/**
	 * Sanitize input value
	 *
	 * @param string $value Saved value.
	 * @return string        Sanitized text
	 */
	public function sanitize($value)
	{
		return sanitize_text_field($value);
	}
}
