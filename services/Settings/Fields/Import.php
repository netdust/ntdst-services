<?php

/**
 * Import field class
 *
 * @package notification
 */

namespace Netdust\Services\Settings\Fields;

use BracketSpace\Notification\Core\Templates;

/**
 * Import class
 */
class Import
{
	/**
	 * Field markup.
	 *
	 * @param \BracketSpace\Notification\Utils\Settings\Field $field Field instance.
	 * @return void
	 */
	public function input($field)
	{
		Templates::render('import/notifications');
	}
}
