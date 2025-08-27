<?php

/**
 * Message field class
 *
 * @package notification
 */
namespace Netdust\Services\Settings\CoreFields;

/**
 * Message class
 */
class Message
{
	/**
	 * Message field
	 *
	 * @param  $field  .
	 * @return void
	 */
	public function input($field)
	{
		if ($field->addon('code')) {
			echo '<pre><code>';
		}

		$message = $field->addon('message');

		echo wp_kses_post(
			is_callable($message)
				? $message()
				: $message
		);

		if (!$field->addon('code')) {
			return;
		}

		echo '</code></pre>';
	}

	/**
	 * Sanitize input value
	 *
	 * @param string $value Saved value.
	 * @return string        Sanitized text
	 */
	public function sanitize($value)
	{
		return $value;
	}
}
