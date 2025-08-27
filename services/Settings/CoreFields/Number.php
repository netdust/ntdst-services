<?php

/**
 * Number field class
 *
 * @package notification
 */

namespace Netdust\Services\Settings\CoreFields;

/**
 * Number class
 */
class Number
{
	/**
	 * Number field
	 * Accepts addons: min, max, step
	 *
	 * @param  $field  .
	 * @return void
	 */
	public function input($field)
	{
		printf(
		//phpcs:ignore Generic.Files.LineLength.TooLong
			'<label><input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" step="%s" class="widefat"></label>',
			esc_attr($field->inputId()),
			esc_attr($field->inputName()),
			esc_attr($field->value()),
			esc_attr($field->addon('min')),
			esc_attr($field->addon('max')),
			esc_attr($field->addon('step'))
		);
	}

	/**
	 * Sanitize input value
	 *
	 * @param string $value saved value.
	 * @return int|float     sanitized number
	 */
	public function sanitize($value)
	{
		if (!is_numeric($value)) {
			return 0;
		}

		return floatval($value);
	}
}
