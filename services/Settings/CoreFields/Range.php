<?php

/**
 * Range field class
 *
 * @package notification
 */

namespace Netdust\Services\Settings\CoreFields;

/**
 * Range class
 */
class Range
{
	/**
	 * Range field
	 * Accepts addons: min, max, step
	 *
	 * @param  $field  .
	 * @return void
	 */
	public function input($field)
	{
		printf(
		//phpcs:ignore Generic.Files.LineLength.TooLong
			'<label><input type="range" id="%s" name="%s" value="%s" min="%s" max="%s" step="%s" class="widefat"></label>',
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
	 * @param mixed $value Saved value.
	 * @return float        Sanitized number
	 */
	public function sanitize($value)
	{
		if (!is_numeric($value)) {
			return 0;
		}

		return floatval($value);
	}
}
