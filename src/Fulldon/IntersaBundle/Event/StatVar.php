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

final class StatVar
{
    /**
     * The event createLog is launched on every action

     *
     * @var string
     */
    const CREATE = 'createStat';
    const _STAT_TYPE_SAISIE_DONATEUR_NEW_ ="saisie_donateur_new";
    const _STAT_TYPE_SAISIE_DONATEUR_OLD_ ="saisie_donateur_old";
    const _STAT_TYPE_SAISIE_PRELEVEMENT_NEW_ ="saisie_prelevement_new";
    const _STAT_TYPE_SAISIE_PRELEVEMENT_OLD_ ="saisie_prelevement_old";
    const _STAT_TYPE_LOT_VERIFIED_ ="lot_verified";
    const _STAT_TYPE_LOT_GENERATED_ ="lot_generated";
    const _STAT_TYPE_ANOMALIE_CORRECTION_ ="anomalie_correction";
    const _STAT_TYPE_DUPLICATA_SENDED_ ="duplicata_sended";
    const _STAT_TYPE_DOUBLON_CREATED_ ="doublon_created";
    const _STAT_TYPE_DOUBLON_MERGED_ ="doublon_merged";
}