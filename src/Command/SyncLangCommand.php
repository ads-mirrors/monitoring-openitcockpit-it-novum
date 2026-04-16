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

declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Parser\PoFileParser;

/**
 * SyncLang command.
 */
class SyncLangCommand extends Command {
    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'sync_lang';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string {
        return 'sync_lang';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string {
        return 'Synchronize a given language file with the default language.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser)
            ->setDescription(static::getDescription());

        $parser->addOption('lang', [
            'short'   => 'l',
            'help'    => 'The language you want to add missing keys',
            'default' => 'de_DE',
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $language = $args->getOption('lang');

        $defaultPo = ROOT . DS . 'resources' . DS . 'locales' . DS . 'default.pot';
        $langPo = ROOT . DS . 'resources' . DS . 'locales' . DS . $language . DS . 'default.po';

        $newPo = ROOT . DS . 'resources' . DS . 'locales' . DS . 'NEW_' . $language . '.po';

        $DefaultParser = new PoFileParser();
        $defaults = $DefaultParser->parse($defaultPo);

        $LanguageParser = new PoFileParser();
        $lang = $LanguageParser->parse($langPo);

        // Grab existing values
        $newLanguageKeyValue = [];
        $missingKeysInLanguage = [];
        foreach ($defaults as $key => $value) {
            // Only the keys are relevant, as in the "default.pot" all values are empty.
            if (isset($lang[$key])) {
                // Key exists in language, lets does it also has a value?
                if (!empty($lang[$key]['_context'][''])) {
                    // We have a value
                    $newLanguageKeyValue[$key] = $lang[$key]['_context'][''];
                    continue;
                }
            }

            // Value is missing in language (or empty)
            $missingKeysInLanguage[$key] = $key;
        }

        $io->out(sprintf(
            'Language %s has %s keys - %s keys are new or currently empty',
            $language,
            sizeof($newLanguageKeyValue),
            sizeof($missingKeysInLanguage)
        ));

        $fp = fopen($newPo, 'w+');

        // Add already translated and still existing fields
        foreach ($newLanguageKeyValue as $key => $val) {
            fwrite($fp, sprintf("msgid \"%s\"\n", str_replace('"', '\"', (string)$key)));
            fwrite($fp, sprintf("msgstr \"%s\"\n", str_replace('"', '\"', $val)));
            fwrite($fp, "\n");
        }

        // Add new or missing fields
        foreach ($missingKeysInLanguage as $key => $val) {
            fwrite($fp, sprintf("msgid \"%s\"\n", str_replace('"', '\"', $key)));
            fwrite($fp, sprintf("msgstr \"\"\n")); // Missing or empty translation
            fwrite($fp, "\n");
        }

        fclose($fp);

        $io->out(sprintf('New translation file %s created', $newPo));
    }
}
