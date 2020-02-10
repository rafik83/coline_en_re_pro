<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Fulldon\IntersaBundle\Event;

final class LogVar
{
    /**
     * The event createLog is launched on every action

     *
     * @var string
     */

    const CREATE = 'createLog';
    const _LOG_TYPE_INFO_DONATEUR_ =1; //donateur add
    const _LOG_TYPE_INFO_DON_ = 2; // don add
    const _LOG_TYPE_INFO_RF_ = 3; // rf add
    const _LOG_TYPE_INFO_PND_ = 4; //pnd
    const _DONATEUR_MOD_ = 5;
    const _DON_MOD_ = 6;
    const _DUPLICATA_ADD_ = 7;
    const _EMAIL_SENT_ = 8;

}