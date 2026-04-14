<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

namespace App\itnovum\openITCOCKPIT\Core\MonitoringEngine;

class ShellCharacters {

    /**
     * This function will remove potential dangerous shell characters from a given string
     * As we do not know who the strings will be used later by Naemon, we can not escape the characters and have to remove them entirely.
     * See ITC-3685 for more details
     * @param string $str
     * @return string
     */
    public static function removeDangerous(string $str = ''): string {
        return str_replace(['`', '$', '\\', '"', '\'', ';', '&', '|', '<', '>', '*', '?', '~', '!'], '', $str);
    }

}
