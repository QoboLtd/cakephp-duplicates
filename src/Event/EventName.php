<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Qobo\Duplicates\Event;

use MyCLabs\Enum\Enum;

/**
 * Event Name enum
 */
class EventName extends Enum
{
    const DUPLICATE_AFTER_FIND = 'Duplicates.Model.Duplicates.afterFind';
}
