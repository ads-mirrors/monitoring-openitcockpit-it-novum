<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

namespace App\itnovum\openITCOCKPIT\Export;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomWorksheet extends Worksheet {
    /**
     * Fill worksheet from values in array.
     *
     * @param mixed[]|mixed[][] $source Source array
     * @param mixed $nullValue Value in source array that stands for blank cell
     * @param string $startCell Insert array starting from this cell address as the top left coordinate
     * @param bool $strictNullComparison Apply strict comparison when testing for null values in the array
     *
     * @return $this
     */
    public function fromArrayWithCenteredValues(array $source, mixed $nullValue = null, string $startCell = 'A1', bool $strictNullComparison = false, array $centeredValues = []): static {
        //    Convert a 1-D array to 2-D (for ease of looping)
        if (!is_array(end($source))) {
            $source = [$source];
        }
        /** @var mixed[][] $source */
        // start coordinate
        [$startColumn, $startRow] = Coordinate::coordinateFromString($startCell);
        $startRow = (int)$startRow;

        // Loop through $source
        if ($strictNullComparison) {
            foreach ($source as $rowData) {
                /** @var string */
                $currentColumn = $startColumn;
                foreach ($rowData as $cellValue) {
                    if ($cellValue !== $nullValue) {
                        if (!empty($centeredValues) && in_array($cellValue, $centeredValues, true)) {
                            $this->getStyle($currentColumn . $startRow)
                                ->getAlignment()
                                ->setHorizontal('center');
                            if ($cellValue === '✓') {
                                $this->getStyle($currentColumn . $startRow)
                                    ->getFont()
                                    ->setBold(true)
                                    ->getColor()
                                    ->setARGB(Color::COLOR_DARKGREEN);
                            }
                        }
                        $this->getCell($currentColumn . $startRow)->setValue($cellValue);
                    }
                    StringHelper::stringIncrement($currentColumn);
                }
                ++$startRow;
            }
        } else {
            foreach ($source as $rowData) {
                $currentColumn = $startColumn;
                foreach ($rowData as $cellValue) {
                    if ($cellValue != $nullValue) {
                        if (!empty($centeredValues) && in_array($cellValue, $centeredValues, true)) {
                            $this->getStyle($currentColumn . $startRow)->getAlignment()->setHorizontal('center');
                            if ($cellValue === '✓') {
                                $this->getStyle($currentColumn . $startRow)
                                    ->getFont()
                                    ->setBold(true)
                                    ->getColor()
                                    ->setARGB(Color::COLOR_DARKGREEN);
                            }

                        }
                        $this->getCell($currentColumn . $startRow)->setValue($cellValue);
                    }
                    StringHelper::stringIncrement($currentColumn);
                }
                ++$startRow;
            }
        }

        return $this;
    }
}
