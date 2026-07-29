<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGroupedToStatuspages extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        if (!$this->hasTable('statuspages')) {
            return;
        }

        $table = $this->table('statuspages');

        if (!$table->hasColumn('grouped')) {
            $table->addColumn('grouped', 'boolean', [
                'default' => 0,
                'limit'   => null,
                'null'    => false,
                'after'   => 'public',
            ]);
            $table->update();
        }
    }
}
